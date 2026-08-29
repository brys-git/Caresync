<?php

namespace App\Services;

use CodeIgniter\I18n\Time;
use Exception;

/**
 * AuditService
 * 
 * Centralized audit logging for all data changes in the system.
 * Tracks INSERT, UPDATE, DELETE operations with old/new values.
 */
class AuditService
{
    protected $db;
    protected $auditModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Log a data change to the audit trail
     * 
     * @param string $tableName Name of the table being modified
     * @param int|string $recordId Primary key of the record
     * @param string $action INSERT, UPDATE, or DELETE
     * @param array $oldValues Original values (null for INSERT)
     * @param array $newValues New values (null for DELETE)
     * @param int|null $userId ID of user making the change
     * @param string|null $ipAddress IP address of requester
     * @param string|null $description Human-readable description
     * @return bool Success status
     */
    public function logChange(
        string $tableName,
        $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $description = null
    ): bool {
        try {
            $data = [
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => strtoupper($action),
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'changed_by' => $userId,
                'ip_address' => $ipAddress,
                'description' => $description,
                'changed_at' => date('Y-m-d H:i:s'),
            ];

            return $this->db->table('audit_logs')->insert($data);
        } catch (Exception $e) {
            log_message('error', 'AuditService::logChange failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a payment transaction status change
     * 
     * @param int $paymentId Payment ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param string|null $reason Reason for change
     * @param int|null $userId User ID making the change
     * @param string|null $ipAddress IP address
     * @return bool Success status
     */
    public function logPaymentTransition(
        int $paymentId,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): bool {
        try {
            $data = [
                'payment_id' => $paymentId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'changed_by' => $userId,
                'ip_address' => $ipAddress,
                'transitioned_at' => date('Y-m-d H:i:s'),
            ];

            return $this->db->table('payment_transactions')->insert($data);
        } catch (Exception $e) {
            log_message('error', 'AuditService::logPaymentTransition failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a service status change
     * 
     * @param int $serviceId Service ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param string|null $notes Additional notes
     * @param int|null $userId User ID making the change
     * @param string|null $ipAddress IP address
     * @return bool Success status
     */
    public function logServiceTransition(
        int $serviceId,
        string $oldStatus,
        string $newStatus,
        ?string $notes = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): bool {
        try {
            $data = [
                'service_id' => $serviceId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'changed_by' => $userId,
                'ip_address' => $ipAddress,
                'logged_at' => date('Y-m-d H:i:s'),
            ];

            return $this->db->table('service_logs')->insert($data);
        } catch (Exception $e) {
            log_message('error', 'AuditService::logServiceTransition failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log email delivery attempt
     * 
     * @param string $recipient Email recipient
     * @param string $subject Email subject
     * @param string $status 'sent', 'failed', 'bounced'
     * @param string|null $errorMessage Error message if failed
     * @param int|null $userId User ID related to email
     * @return bool Success status
     */
    public function logEmailDelivery(
        string $recipient,
        string $subject,
        string $status,
        ?string $errorMessage = null,
        ?int $userId = null
    ): bool {
        try {
            $data = [
                'recipient' => $recipient,
                'subject' => $subject,
                'status' => $status,
                'error_message' => $errorMessage,
                'user_id' => $userId,
                'sent_at' => date('Y-m-d H:i:s'),
            ];

            return $this->db->table('email_logs')->insert($data);
        } catch (Exception $e) {
            log_message('error', 'AuditService::logEmailDelivery failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit logs for a specific record
     * 
     * @param string $tableName Table name
     * @param int|string $recordId Record ID
     * @param int $limit Number of records to return
     * @return array Audit logs
     */
    public function getRecordHistory(string $tableName, $recordId, int $limit = 50): array
    {
        return $this->db->table('audit_logs')
            ->where('table_name', $tableName)
            ->where('record_id', $recordId)
            ->orderBy('changed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get audit logs for a specific user
     * 
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Audit logs
     */
    public function getUserActivity(int $userId, int $limit = 100): array
    {
        return $this->db->table('audit_logs')
            ->where('changed_by', $userId)
            ->orderBy('changed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Get audit logs for a date range
     * 
     * @param string $startDate Start date (YYYY-MM-DD format)
     * @param string $endDate End date (YYYY-MM-DD format)
     * @param string|null $tableName Specific table filter
     * @param int $limit Number of records to return
     * @return array Audit logs
     */
    public function getAuditsByDateRange(
        string $startDate,
        string $endDate,
        ?string $tableName = null,
        int $limit = 200
    ): array {
        $builder = $this->db->table('audit_logs')
            ->whereDate('changed_at', '>=', $startDate)
            ->whereDate('changed_at', '<=', $endDate);

        if ($tableName) {
            $builder->where('table_name', $tableName);
        }

        return $builder->orderBy('changed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Clean up old audit logs (retention policy)
     * 
     * @param int $daysToKeep Number of days to keep (default: 90)
     * @return int Number of rows deleted
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        return $this->db->table('audit_logs')
            ->where('changed_at <', $cutoffDate)
            ->delete();
    }
}
