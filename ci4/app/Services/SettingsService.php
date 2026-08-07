<?php

namespace App\Services;

use Exception;

/**
 * SettingsService
 * 
 * Manages system settings with caching and change tracking.
 * Centralizes access to configuration values stored in the database.
 */
class SettingsService
{
    protected $db;
    protected $cache;
    protected $auditService;
    protected $cacheKey = 'system_settings';
    protected $cacheDuration = 3600; // 1 hour

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->cache = \Config\Services::cache();
        $this->auditService = new AuditService();
    }

    /**
     * Get a setting value by key
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value or default
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * Get all settings (with caching)
     * 
     * @return array All settings as key-value pairs
     */
    public function getAll(): array
    {
        // Try to get from cache first (cache returns null on a miss)
        $cached = $this->cache->get($this->cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        // Fetch from database
        $results = $this->db->table('system_settings')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        $settings = [];
        foreach ($results as $row) {
            // Cast value to appropriate type
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['data_type']
            );
        }

        // Cache for 1 hour
        $this->cache->save($this->cacheKey, $settings, $this->cacheDuration);

        return $settings;
    }

    /**
     * Get settings by category
     * 
     * @param string $category Setting category
     * @return array Settings for the category
     */
    public function getByCategory(string $category): array
    {
        $results = $this->db->table('system_settings')
            ->where('category', $category)
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['data_type']
            );
        }

        return $settings;
    }

    /**
     * Get settings by data type
     * 
     * @param string $dataType Data type (string, integer, boolean, json)
     * @return array Settings of the specified type
     */
    public function getByDataType(string $dataType): array
    {
        $results = $this->db->table('system_settings')
            ->where('data_type', $dataType)
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $this->castValue(
                $row['setting_value'],
                $row['data_type']
            );
        }

        return $settings;
    }

    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value New value
     * @param int|null $userId User ID making the change
     * @param string|null $ipAddress IP address
     * @return bool Success status
     */
    public function set(
        string $key,
        $value,
        ?int $userId = null,
        ?string $ipAddress = null
    ): bool {
        try {
            // Get existing value for audit
            $existing = $this->db->table('system_settings')
                ->where('setting_key', $key)
                ->first();

            $valueString = is_array($value) || is_object($value)
                ? json_encode($value)
                : (string)$value;

            if ($existing) {
                // Update existing setting
                $this->db->table('system_settings')
                    ->where('setting_key', $key)
                    ->update([
                        'setting_value' => $valueString,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                // Log the change
                $this->auditService->logChange(
                    'system_settings',
                    $key,
                    'UPDATE',
                    ['setting_value' => $existing->setting_value],
                    ['setting_value' => $valueString],
                    $userId,
                    $ipAddress,
                    "Changed setting: {$key}"
                );
            } else {
                // Insert new setting
                $this->db->table('system_settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $valueString,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Log the change
                $this->auditService->logChange(
                    'system_settings',
                    $key,
                    'INSERT',
                    null,
                    ['setting_value' => $valueString],
                    $userId,
                    $ipAddress,
                    "Created setting: {$key}"
                );
            }

            // Invalidate cache
            $this->cache->delete($this->cacheKey);

            return true;
        } catch (Exception $e) {
            log_message('error', 'SettingsService::set failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set multiple settings at once
     * 
     * @param array $settings Key-value pairs of settings
     * @param int|null $userId User ID making the changes
     * @param string|null $ipAddress IP address
     * @return int Number of settings updated/created
     */
    public function setMultiple(
        array $settings,
        ?int $userId = null,
        ?string $ipAddress = null
    ): int {
        $count = 0;
        foreach ($settings as $key => $value) {
            if ($this->set($key, $value, $userId, $ipAddress)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Delete a setting
     * 
     * @param string $key Setting key
     * @param int|null $userId User ID making the change
     * @param string|null $ipAddress IP address
     * @return bool Success status
     */
    public function delete(
        string $key,
        ?int $userId = null,
        ?string $ipAddress = null
    ): bool {
        try {
            $existing = $this->db->table('system_settings')
                ->where('setting_key', $key)
                ->first();

            if ($existing) {
                $this->db->table('system_settings')
                    ->where('setting_key', $key)
                    ->delete();

                // Log the deletion
                $this->auditService->logChange(
                    'system_settings',
                    $key,
                    'DELETE',
                    ['setting_value' => $existing->setting_value],
                    null,
                    $userId,
                    $ipAddress,
                    "Deleted setting: {$key}"
                );

                // Invalidate cache
                $this->cache->delete($this->cacheKey);

                return true;
            }
        } catch (Exception $e) {
            log_message('error', 'SettingsService::delete failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Get payment settings
     * 
     * @return array Payment configuration
     */
    public function getPaymentSettings(): array
    {
        return $this->getByCategory('payment');
    }

    /**
     * Get service settings
     * 
     * @return array Service configuration
     */
    public function getServiceSettings(): array
    {
        return $this->getByCategory('service');
    }

    /**
     * Get security settings
     * 
     * @return array Security configuration
     */
    public function getSecuritySettings(): array
    {
        return $this->getByCategory('security');
    }

    /**
     * Get system settings
     * 
     * @return array System configuration
     */
    public function getSystemSettings(): array
    {
        return $this->getByCategory('system');
    }

    /**
     * Reload cache
     * 
     * @return void
     */
    public function reloadCache(): void
    {
        $this->cache->delete($this->cacheKey);
        $this->getAll(); // Re-populate cache
    }

    /**
     * Cast setting value to appropriate type
     * 
     * @param string $value Raw setting value
     * @param string $dataType Data type
     * @return mixed Casted value
     */
    protected function castValue(string $value, string $dataType)
    {
        return match ($dataType) {
            'integer' => (int)$value,
            'boolean' => $value === '1' || strtolower($value) === 'true',
            'json' => json_decode($value, true),
            'decimal' => (float)$value,
            default => $value,
        };
    }
}
