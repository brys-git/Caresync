<?php

/**
 * Activity Logging Helper
 * Provides convenient functions for logging activities throughout the application.
 */

if (!function_exists('log_activity')) {
    /**
     * Log an activity
     * 
     * @param int $userId
     * @param string $action
     * @param string $module
     * @param int $targetId
     * @param string $description
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return bool
     */
    function log_activity(
        int $userId,
        string $action,
        string $module,
        int $targetId,
        string $description = '',
        ?array $oldValues = null,
        ?array $newValues = null
    ): bool {
        $service = new \App\Services\ActivityLogService();

        return $service->log($userId, $action, $module, $targetId, $description, $oldValues, $newValues);
    }
}

if (!function_exists('get_activity_logs')) {
    /**
     * Get activity logs for a record
     * 
     * @param string $module
     * @param int $targetId
     * @return array
     */
    function get_activity_logs(string $module, int $targetId): array
    {
        $service = new \App\Services\ActivityLogService();

        return $service->getRecordLogs($module, $targetId);
    }
}

if (!function_exists('notify_user')) {
    /**
     * Send a notification to a user
     * 
     * @param int $userId
     * @param string $message
     * @param string $type
     * @return bool
     */
    function notify_user(int $userId, string $message, string $type = 'general'): bool
    {
        $service = new \App\Services\NotificationService();

        return $service->notify($userId, $message, $type);
    }
}

if (!function_exists('get_user_notifications')) {
    /**
     * Get notifications for a user
     * 
     * @param int $userId
     * @param string $type
     * @param string $status
     * @return array
     */
    function get_user_notifications(int $userId, string $type = '', string $status = ''): array
    {
        $service = new \App\Services\NotificationService();

        return $service->getNotifications($userId, $type, $status);
    }
}

if (!function_exists('get_unread_notification_count')) {
    /**
     * Get unread notification count for a user
     * 
     * @param int $userId
     * @return int
     */
    function get_unread_notification_count(int $userId): int
    {
        $service = new \App\Services\NotificationService();

        return $service->getUnreadCount($userId);
    }
}

if (!function_exists('get_active_membership')) {
    /**
     * Get active membership for a plan holder
     * 
     * @param int $planHolderId
     * @return array|null
     */
    function get_active_membership(int $planHolderId): ?array
    {
        $service = new \App\Services\MembershipService();

        return $service->getMembershipDetails($planHolderId);
    }
}

if (!function_exists('is_active_member')) {
    /**
     * Check if user is an active member
     * 
     * @param int $userId
     * @param int $planHolderId
     * @return bool
     */
    function is_active_member(int $userId, int $planHolderId): bool
    {
        $service = new \App\Services\MembershipService();

        return $service->isActiveMember($userId, $planHolderId);
    }
}
