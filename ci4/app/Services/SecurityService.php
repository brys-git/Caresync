<?php

namespace App\Services;

use Exception;

/**
 * SecurityService
 * 
 * Manages rate limiting, session tracking, and security-related operations.
 * Prevents abuse and tracks user sessions for security auditing.
 */
class SecurityService
{
    protected $db;
    protected $settingsService;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->settingsService = new SettingsService();
    }

    /**
     * Check if an IP address is rate limited for a specific action
     * 
     * @param string $ipAddress IP address to check
     * @param string $action Action being attempted
     * @return bool True if rate limited, false otherwise
     */
    public function isRateLimited(string $ipAddress, string $action = 'login'): bool
    {
        $rateLimit = $this->db->table('rate_limits')
            ->where('ip_address', $ipAddress)
            ->where('action', $action)
            ->where('is_blocked', 1)
            ->first();

        if (!$rateLimit) {
            return false;
        }

        // Check if block has expired
        if ($rateLimit->blocked_until && strtotime($rateLimit->blocked_until) < time()) {
            // Block has expired, remove it
            $this->db->table('rate_limits')
                ->where('ip_address', $ipAddress)
                ->where('action', $action)
                ->update(['is_blocked' => 0, 'blocked_until' => null]);
            return false;
        }

        return true;
    }

    /**
     * Record an attempt for an IP and action
     * 
     * @param string $ipAddress IP address
     * @param string $action Action being attempted
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $lockoutMinutes Minutes to lock out after max attempts
     * @return int Current attempt count
     */
    public function recordAttempt(
        string $ipAddress,
        string $action = 'login',
        int $maxAttempts = 5,
        int $lockoutMinutes = 15
    ): int {
        try {
            $existing = $this->db->table('rate_limits')
                ->where('ip_address', $ipAddress)
                ->where('action', $action)
                ->first();

            if ($existing) {
                // Increment attempt count
                $newCount = $existing->attempt_count + 1;
                $isBlocked = 0;
                $blockedUntil = null;

                // Check if should lock out
                if ($newCount >= $maxAttempts) {
                    $isBlocked = 1;
                    $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
                }

                $this->db->table('rate_limits')
                    ->where('ip_address', $ipAddress)
                    ->where('action', $action)
                    ->update([
                        'attempt_count' => $newCount,
                        'last_attempt' => date('Y-m-d H:i:s'),
                        'is_blocked' => $isBlocked,
                        'blocked_until' => $blockedUntil,
                    ]);

                return $newCount;
            } else {
                // Create new rate limit entry
                $this->db->table('rate_limits')->insert([
                    'ip_address' => $ipAddress,
                    'action' => $action,
                    'attempt_count' => 1,
                    'first_attempt' => date('Y-m-d H:i:s'),
                    'last_attempt' => date('Y-m-d H:i:s'),
                    'is_blocked' => 0,
                    'blocked_until' => null,
                ]);

                return 1;
            }
        } catch (Exception $e) {
            log_message('error', 'SecurityService::recordAttempt failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Reset rate limit attempts for an IP and action
     * 
     * @param string $ipAddress IP address
     * @param string $action Action
     * @return bool Success status
     */
    public function resetAttempts(string $ipAddress, string $action = 'login'): bool
    {
        try {
            return $this->db->table('rate_limits')
                ->where('ip_address', $ipAddress)
                ->where('action', $action)
                ->update([
                    'attempt_count' => 0,
                    'is_blocked' => 0,
                    'blocked_until' => null,
                ]) !== false;
        } catch (Exception $e) {
            log_message('error', 'SecurityService::resetAttempts failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new user session
     * 
     * @param int $userId User ID
     * @param string $ipAddress IP address
     * @param string|null $userAgent User agent string
     * @return string|false Session token or false on failure
     */
    public function createSession(
        int $userId,
        string $ipAddress,
        ?string $userAgent = null
    ) {
        try {
            $sessionToken = bin2hex(random_bytes(32));
            $settingsService = new SettingsService();
            $sessionTimeout = $settingsService->get('session_timeout_minutes', 30);
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$sessionTimeout} minutes"));

            $this->db->table('user_sessions')->insert([
                'user_id' => $userId,
                'session_token' => $sessionToken,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => date('Y-m-d H:i:s'),
                'last_activity' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
                'is_active' => 1,
            ]);

            return $sessionToken;
        } catch (Exception $e) {
            log_message('error', 'SecurityService::createSession failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate a session token
     * 
     * @param string $sessionToken Session token to validate
     * @param string|null $ipAddress Optional IP address to verify
     * @return array|false Session data or false if invalid
     */
    public function validateSession(string $sessionToken, ?string $ipAddress = null)
    {
        try {
            $session = $this->db->table('user_sessions')
                ->where('session_token', $sessionToken)
                ->where('is_active', 1)
                ->first();

            if (!$session) {
                return false;
            }

            // Check if expired
            if (strtotime($session->expires_at) < time()) {
                $this->db->table('user_sessions')
                    ->where('session_id', $session->session_id)
                    ->update(['is_active' => 0]);
                return false;
            }

            // Check IP if provided (optional)
            if ($ipAddress && $session->ip_address !== $ipAddress) {
                log_message('warning', "Session IP mismatch: {$session->ip_address} vs {$ipAddress}");
                // Don't fail but log it
            }

            // Update last activity
            $this->db->table('user_sessions')
                ->where('session_id', $session->session_id)
                ->update(['last_activity' => date('Y-m-d H:i:s')]);

            return $session;
        } catch (Exception $e) {
            log_message('error', 'SecurityService::validateSession failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Expire a session
     * 
     * @param string $sessionToken Session token to expire
     * @return bool Success status
     */
    public function expireSession(string $sessionToken): bool
    {
        try {
            return $this->db->table('user_sessions')
                ->where('session_token', $sessionToken)
                ->update(['is_active' => 0]) !== false;
        } catch (Exception $e) {
            log_message('error', 'SecurityService::expireSession failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cleanup expired sessions
     * 
     * @return int Number of sessions cleaned up
     */
    public function cleanupExpiredSessions(): int
    {
        try {
            return $this->db->table('user_sessions')
                ->where('expires_at <', date('Y-m-d H:i:s'))
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        } catch (Exception $e) {
            log_message('error', 'SecurityService::cleanupExpiredSessions failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all active sessions for a user
     * 
     * @param int $userId User ID
     * @return array Active sessions
     */
    public function getUserSessions(int $userId): array
    {
        return $this->db->table('user_sessions')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->orderBy('last_activity', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Expire all sessions for a user
     * 
     * @param int $userId User ID
     * @return int Number of sessions expired
     */
    public function expireAllUserSessions(int $userId): int
    {
        try {
            return $this->db->table('user_sessions')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        } catch (Exception $e) {
            log_message('error', 'SecurityService::expireAllUserSessions failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Block an IP address
     * 
     * @param string $ipAddress IP address to block
     * @param string $action Action to block
     * @param int $minutes Duration of block
     * @return bool Success status
     */
    public function blockIP(string $ipAddress, string $action = 'login', int $minutes = 60): bool
    {
        try {
            $existing = $this->db->table('rate_limits')
                ->where('ip_address', $ipAddress)
                ->where('action', $action)
                ->first();

            if ($existing) {
                return $this->db->table('rate_limits')
                    ->where('ip_address', $ipAddress)
                    ->where('action', $action)
                    ->update([
                        'is_blocked' => 1,
                        'blocked_until' => date('Y-m-d H:i:s', strtotime("+{$minutes} minutes")),
                    ]) !== false;
            } else {
                return $this->db->table('rate_limits')->insert([
                    'ip_address' => $ipAddress,
                    'action' => $action,
                    'attempt_count' => 0,
                    'is_blocked' => 1,
                    'blocked_until' => date('Y-m-d H:i:s', strtotime("+{$minutes} minutes")),
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'SecurityService::blockIP failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unblock an IP address
     * 
     * @param string $ipAddress IP address to unblock
     * @param string $action Action to unblock
     * @return bool Success status
     */
    public function unblockIP(string $ipAddress, string $action = 'login'): bool
    {
        try {
            return $this->db->table('rate_limits')
                ->where('ip_address', $ipAddress)
                ->where('action', $action)
                ->update([
                    'is_blocked' => 0,
                    'blocked_until' => null,
                ]) !== false;
        } catch (Exception $e) {
            log_message('error', 'SecurityService::unblockIP failed: ' . $e->getMessage());
            return false;
        }
    }
}
