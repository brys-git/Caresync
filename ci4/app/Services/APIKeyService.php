<?php

namespace App\Services;

use Exception;

/**
 * APIKeyService
 * 
 * Manages API key generation, validation, and revocation.
 * Supports IP whitelisting and usage tracking.
 */
class APIKeyService
{
    protected $db;
    protected $auditService;
    protected $securityService;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->auditService = new AuditService();
        $this->securityService = new SecurityService();
    }

    /**
     * Generate a new API key for a user
     * 
     * @param int $userId User ID
     * @param string|null $name Descriptive name for the key
     * @param string|null $expiresAt Expiration date (YYYY-MM-DD format)
     * @param array|null $ipWhitelist Array of allowed IP addresses
     * @return array|false [key_id, api_key, api_secret] or false
     */
    public function generateKey(
        int $userId,
        ?string $name = null,
        ?string $expiresAt = null,
        ?array $ipWhitelist = null
    ) {
        try {
            // Generate cryptographically secure keys
            $apiKey = 'key_' . bin2hex(random_bytes(32));
            $apiSecret = bin2hex(random_bytes(64));

            $data = [
                'user_id' => $userId,
                'api_key' => hash('sha256', $apiKey), // Store hashed key
                'api_secret' => hash('sha256', $apiSecret), // Store hashed secret
                'name' => $name ?? 'API Key ' . date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt ? date('Y-m-d', strtotime($expiresAt)) : null,
                'is_active' => 1,
                'ip_whitelist' => $ipWhitelist ? json_encode($ipWhitelist) : null,
            ];

            $this->db->table('api_keys')->insert($data);
            $keyId = $this->db->insertID();

            // Log the creation
            $this->auditService->logChange(
                'api_keys',
                $keyId,
                'INSERT',
                null,
                ['user_id' => $userId, 'name' => $name],
                $userId,
                null,
                "Generated new API key: {$name}"
            );

            return [
                'key_id' => $keyId,
                'api_key' => $apiKey, // Return unhashed version only once
                'api_secret' => $apiSecret, // Return unhashed version only once
            ];
        } catch (Exception $e) {
            log_message('error', 'APIKeyService::generateKey failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate an API key and secret pair
     * 
     * @param string $apiKey API key provided
     * @param string $apiSecret API secret provided
     * @param string|null $ipAddress IP address making the request
     * @return array|false Key data if valid, false otherwise
     */
    public function validateKey(
        string $apiKey,
        string $apiSecret,
        ?string $ipAddress = null
    ) {
        try {
            $hashedKey = hash('sha256', $apiKey);
            $hashedSecret = hash('sha256', $apiSecret);

            $key = $this->db->table('api_keys')
                ->where('api_key', $hashedKey)
                ->where('api_secret', $hashedSecret)
                ->where('is_active', 1)
                ->first();

            if (!$key) {
                $this->securityService->recordAttempt(
                    $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'api_auth',
                    10,
                    30
                );
                return false;
            }

            // Check if expired
            if ($key->expires_at && strtotime($key->expires_at) < time()) {
                return false;
            }

            // Check IP whitelist
            if ($key->ip_whitelist && $ipAddress) {
                $whitelist = json_decode($key->ip_whitelist, true);
                if (!in_array($ipAddress, $whitelist)) {
                    log_message('warning', "API key {$key->key_id} IP whitelist violation: {$ipAddress}");
                    return false;
                }
            }

            // Update last used
            $this->db->table('api_keys')
                ->where('key_id', $key->key_id)
                ->update(['last_used' => date('Y-m-d H:i:s')]);

            // Reset rate limit for this IP
            $this->securityService->resetAttempts(
                $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'api_auth'
            );

            return $key;
        } catch (Exception $e) {
            log_message('error', 'APIKeyService::validateKey failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get API keys for a user
     * 
     * @param int $userId User ID
     * @param bool $activeOnly Show only active keys
     * @return array API keys (without secrets)
     */
    public function getUserKeys(int $userId, bool $activeOnly = true): array
    {
        $query = $this->db->table('api_keys')
            ->where('user_id', $userId);

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        $keys = $query->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        // Remove sensitive data
        foreach ($keys as &$key) {
            unset($key['api_key']);
            unset($key['api_secret']);
        }

        return $keys;
    }

    /**
     * Revoke an API key
     * 
     * @param int $keyId Key ID
     * @param int|null $userId User ID making the change
     * @param string|null $reason Reason for revocation
     * @return bool Success status
     */
    public function revokeKey(
        int $keyId,
        ?int $userId = null,
        ?string $reason = null
    ): bool {
        try {
            $existing = $this->db->table('api_keys')
                ->where('key_id', $keyId)
                ->first();

            if (!$existing) {
                return false;
            }

            $this->db->table('api_keys')
                ->where('key_id', $keyId)
                ->update(['is_active' => 0]);

            // Log the revocation
            $this->auditService->logChange(
                'api_keys',
                $keyId,
                'UPDATE',
                ['is_active' => 1],
                ['is_active' => 0],
                $userId,
                null,
                "Revoked API key" . ($reason ? ": {$reason}" : "")
            );

            return true;
        } catch (Exception $e) {
            log_message('error', 'APIKeyService::revokeKey failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update IP whitelist for a key
     * 
     * @param int $keyId Key ID
     * @param array $ipAddresses Array of allowed IP addresses
     * @param int|null $userId User ID making the change
     * @return bool Success status
     */
    public function updateIPWhitelist(
        int $keyId,
        array $ipAddresses,
        ?int $userId = null
    ): bool {
        try {
            $existing = $this->db->table('api_keys')
                ->where('key_id', $keyId)
                ->first();

            if (!$existing) {
                return false;
            }

            $newWhitelist = json_encode($ipAddresses);

            $this->db->table('api_keys')
                ->where('key_id', $keyId)
                ->update(['ip_whitelist' => $newWhitelist]);

            // Log the change
            $this->auditService->logChange(
                'api_keys',
                $keyId,
                'UPDATE',
                ['ip_whitelist' => $existing->ip_whitelist],
                ['ip_whitelist' => $newWhitelist],
                $userId,
                null,
                "Updated IP whitelist"
            );

            return true;
        } catch (Exception $e) {
            log_message('error', 'APIKeyService::updateIPWhitelist failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cleanup expired keys
     * 
     * @return int Number of keys deactivated
     */
    public function cleanupExpiredKeys(): int
    {
        try {
            return $this->db->table('api_keys')
                ->where('expires_at <', date('Y-m-d'))
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        } catch (Exception $e) {
            log_message('error', 'APIKeyService::cleanupExpiredKeys failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get API key usage statistics
     * 
     * @param int $keyId Key ID
     * @return array Usage statistics
     */
    public function getKeyStatistics(int $keyId): array
    {
        $key = $this->db->table('api_keys')
            ->where('key_id', $keyId)
            ->first();

        if (!$key) {
            return [];
        }

        return [
            'key_id' => $key->key_id,
            'name' => $key->name,
            'created_at' => $key->created_at,
            'last_used' => $key->last_used,
            'is_active' => $key->is_active,
            'expires_at' => $key->expires_at,
            'days_until_expiry' => $key->expires_at ? round((strtotime($key->expires_at) - time()) / 86400) : null,
        ];
    }
}
