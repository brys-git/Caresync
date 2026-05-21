<?php

use App\Models\NotificationModel;

if (! function_exists('add_notification')) {
    function add_notification(int $userId, string $message): bool
    {
        if ($userId <= 0 || trim($message) === '') {
            return false;
        }

        $notificationModel = new NotificationModel();

        return (bool) $notificationModel->insert([
            'user_id' => $userId,
            'message' => $message,
            'status' => 'unread',
        ]);
    }
}
