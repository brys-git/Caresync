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
     * Send a notification with type classification. Always creates the
     * in-app record; $sendEmail/$sendSms (panel brief section 9: "deliver
     * notifications via email and/or SMS") additionally push it out over
     * those channels. Both default to false so every existing call site
     * keeps its current in-app-only behavior unless it opts in.
     *
     * @param int $userId
     * @param string $message
     * @param string $type
     * @return bool Whether the in-app notification was created (email/SMS
     *              outcomes don't affect this - see sendEmail()/sendSms()
     *              in App\Services\SmsService, which degrade silently).
     */
    public function notify(int $userId, string $message, string $type = 'general', bool $sendEmail = false, bool $sendSms = false): bool
    {
        if ($userId <= 0 || empty($message)) {
            return false;
        }

        $validTypes = ['payment_approved', 'payment_rejected', 'service_approved', 'service_rejected', 'registration_pending', 'service_completed', 'general'];

        if (!in_array($type, $validTypes, true)) {
            $type = 'general';
        }

        $message = trim($message);

        try {
            $created = (bool) db_connect()->table('notifications')->insert([
                'user_id' => $userId,
                'message' => $message,
                'type' => $type,
                // No 'status' column exists on notifications - unread/read is
                // tracked via is_read (defaults to 0) + read_at, not a string.
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'NotificationService::notify - ' . $e->getMessage());

            return false;
        }

        if ($sendEmail || $sendSms) {
            $user = db_connect()->table('users')
                ->select('email, contact_number, first_name')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if ($user) {
                if ($sendEmail && ! empty($user['email'])) {
                    $this->sendEmail((string) $user['email'], (string) ($user['first_name'] ?? ''), $message);
                }

                if ($sendSms && ! empty($user['contact_number'])) {
                    (new SmsService())->send((string) $user['contact_number'], $message);
                }
            }
        }

        return $created;
    }

    /**
     * Emails a notification. No-ops (logged) if SMTP isn't configured -
     * see the EMAIL section of .env - rather than failing the caller.
     */
    private function sendEmail(string $toAddress, string $firstName, string $message): void
    {
        try {
            $email = \Config\Services::email();
            $greetingName = $firstName !== '' ? $firstName : 'there';

            $email->setTo($toAddress);
            $email->setSubject('CareSync Notification');
            $email->setMessage("Hi {$greetingName},\n\n{$message}\n\n- CareSync");

            if (! $email->send(false)) {
                log_message('error', 'NotificationService::sendEmail - failed to send to ' . $toAddress . ': ' . $email->printDebugger(['headers']));
            }
        } catch (\Throwable $e) {
            log_message('error', 'NotificationService::sendEmail - ' . $e->getMessage());
        }
    }

    /**
     * Get notifications for a user
     *
     * @param int $userId
     * @param string $type Filter by type (optional)
     * @param string $status Filter by read status: 'unread' or 'read' (optional).
     *                       Kept as the public string API callers already use;
     *                       mapped internally to the real is_read column.
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

        if ($status === 'unread') {
            $builder->where('is_read', 0);
        } elseif ($status === 'read') {
            $builder->where('is_read', 1);
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
                ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
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
                ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
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
