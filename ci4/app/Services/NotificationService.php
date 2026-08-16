<?php

namespace App\Services;

/**
 * NotificationService
 * 
 * Enhanced notification handling with types for filtering and display.
 * Supports: payment_approved, payment_rejected, service_approved, service_rejected,
 *           registration_pending, service_completed, general
 */
class NotificationService
{
    /**
     * Send a notification with type classification
     * 
     * @param int $userId
     * @param string $message
     * @param string $type
     * @return bool
     */
    public function notify(int $userId, string $message, string $type = 'general'): bool
    {
        if ($userId <= 0 || empty($message)) {
            return false;
        }

        $validTypes = [
            'payment_approved',
            'payment_rejected',
            'payment_pending',
            'service_approved',
            'service_rejected',
            'service_pending',
            'service_balance_created',
            'registration_pending',
            'service_completed',
            'general',
        ];

        if (!in_array($type, $validTypes, true)) {
            $type = 'general';
        }

        try {
            return (bool) db_connect()->table('notifications')->insert([
                'user_id' => $userId,
                'message' => trim($message),
                'type' => $type,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'NotificationService::notify - ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Map a legacy status string to the new is_read field.
     *
     * @param string $status
     * @return int|null
     */
    private function mapStatusToReadFlag(string $status): ?int
    {
        $status = strtolower(trim($status));

        if ($status === 'unread') {
            return 0;
        }

        if ($status === 'read') {
            return 1;
        }

        return null;
    }

    /**
     * Get notifications for a user
     * 
     * @param int $userId
     * @param string $type Filter by type (optional)
     * @param string $status Filter by status (optional)
     * @return array
     */
    public function getNotifications(int $userId, string $type = '', string $status = ''): array
    {
        if ($userId <= 0) {
            return [];
        }

        $builder = db_connect()->table('notifications')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC');

        if (!empty($type)) {
            $builder->where('type', $type);
        }

        if (!empty($status)) {
            $readFlag = $this->mapStatusToReadFlag($status);
            if ($readFlag !== null) {
                $builder->where('is_read', $readFlag);
            }
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get unread notification count
     * 
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return (int) db_connect()->table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    /**
     * Mark notification as read
     * 
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        if ($notificationId <= 0) {
            return false;
        }

        try {
            return (bool) db_connect()->table('notifications')
                ->where('notification_id', $notificationId)
                ->update(['is_read' => 1]);
        } catch (\Throwable $e) {
            log_message('error', 'NotificationService::markAsRead - ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        try {
            return (bool) db_connect()->table('notifications')
                ->where('user_id', $userId)
                ->update(['is_read' => 1]);
        } catch (\Throwable $e) {
            log_message('error', 'NotificationService::markAllAsRead - ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Delete a notification
     * 
     * @param int $notificationId
     * @return bool
     */
    public function delete(int $notificationId): bool
    {
        if ($notificationId <= 0) {
            return false;
        }

        try {
            return (bool) db_connect()->table('notifications')
                ->where('notification_id', $notificationId)
                ->delete();
        } catch (\Throwable $e) {
            log_message('error', 'NotificationService::delete - ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get notifications grouped by type
     * 
     * @param int $userId
     * @return array
     */
    public function getGroupedByType(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $notifications = $this->getNotifications($userId);
        $grouped = [];

        foreach ($notifications as $notification) {
            $type = $notification['type'] ?? 'general';
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $notification;
        }

        return $grouped;
    }

    /**
     * Send batch notifications
     * 
     * @param array $userIds
     * @param string $message
     * @param string $type
     * @return int Number of notifications sent
     */
    public function notifyMultiple(array $userIds, string $message, string $type = 'general'): int
    {
        $count = 0;

        foreach ($userIds as $userId) {
            if ($this->notify((int) $userId, $message, $type)) {
                $count++;
            }
        }

        return $count;
    }
}
