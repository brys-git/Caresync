<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registration overhaul — coordinator assignment + government ID verification + GCash.
 *
 * plan_holders
 *   coordinator_user_id : FK to users (role 2/3) — the assigned coordinator.
 *   id_document_path    : secure stored path of the uploaded government ID (never web-accessible).
 *   id_type             : supported government ID type (see IdVerificationService::supportedIds()).
 *   id_number           : ID number entered by the applicant.
 *   id_match_score      : OCR/name/DOB match confidence (0-100) recomputed server-side.
 *   id_verification_status : pending|verified|rejected — system-level verification state.
 *   id_verified_at/by   : audit trail for staff confirmation.
 *
 * users
 *   gcash_number/gcash_name : coordinator's GCash account (roles 2/3 only), resolved
 *                             server-side for the client's initial payment.
 *
 * Uses the tableExists/fieldExists guard style of
 * 2026-08-07-100000_AddApplicationIdToServices so it is safe to re-run.
 */
class RegistrationCoordinatorGcashIdVerification extends Migration
{
    public function up(): void
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('plan_holders')) {
            $this->addPlanHolderColumns($db);
        }

        if ($db->tableExists('users')) {
            $this->addUserColumns($db);
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('plan_holders')) {
            $this->dropPlanHolderColumns($db);
        }

        if ($db->tableExists('users')) {
            $this->dropUserColumns($db);
        }
    }

    private function addPlanHolderColumns($db): void
    {
        $fieldNames = $db->getFieldNames('plan_holders');

        if (! in_array('coordinator_user_id', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `coordinator_user_id` INT(11) UNSIGNED NULL AFTER `coordinator`, ADD KEY `ix_plan_holders_coordinator` (`coordinator_user_id`)');
        }
        if (! in_array('id_document_path', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `id_document_path` VARCHAR(500) NULL AFTER `coordinator_user_id`');
        }
        if (! in_array('id_type', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `id_type` VARCHAR(50) NULL AFTER `id_document_path`');
        }
        if (! in_array('id_number', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `id_number` VARCHAR(100) NULL AFTER `id_type`');
        }
        if (! in_array('id_match_score', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `id_match_score` DECIMAL(5,2) NULL AFTER `id_number`');
        }
        if (! in_array('id_verification_status', $fieldNames, true)) {
            $db->query("ALTER TABLE `plan_holders` ADD COLUMN `id_verification_status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending' AFTER `id_match_score`");
        }
        if (! in_array('id_verified_at', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `id_verified_at` DATETIME NULL AFTER `id_verification_status`');
        }
        if (! in_array('id_verified_by', $fieldNames, true)) {
            $db->query('ALTER TABLE `plan_holders` ADD COLUMN `id_verified_by` INT(11) UNSIGNED NULL AFTER `id_verified_at`');
        }
    }

    private function dropPlanHolderColumns($db): void
    {
        $fieldNames = $db->getFieldNames('plan_holders');

        foreach ([
            'id_verified_by',
            'id_verified_at',
            'id_verification_status',
            'id_match_score',
            'id_number',
            'id_type',
            'id_document_path',
            'coordinator_user_id',
        ] as $column) {
            if (in_array($column, $fieldNames, true)) {
                $db->query('ALTER TABLE `plan_holders` DROP COLUMN `' . $column . '`');
            }
        }

        try {
            $db->query('ALTER TABLE `plan_holders` DROP KEY `ix_plan_holders_coordinator`');
        } catch (\Throwable $e) {
            // Index may already be gone — ignore.
        }
    }

    private function addUserColumns($db): void
    {
        $fieldNames = $db->getFieldNames('users');

        if (! in_array('gcash_number', $fieldNames, true)) {
            $db->query('ALTER TABLE `users` ADD COLUMN `gcash_number` VARCHAR(50) NULL AFTER `contact_number`');
        }
        if (! in_array('gcash_name', $fieldNames, true)) {
            $db->query('ALTER TABLE `users` ADD COLUMN `gcash_name` VARCHAR(100) NULL AFTER `gcash_number`');
        }
    }

    private function dropUserColumns($db): void
    {
        $fieldNames = $db->getFieldNames('users');

        if (in_array('gcash_name', $fieldNames, true)) {
            $db->query('ALTER TABLE `users` DROP COLUMN `gcash_name`');
        }
        if (in_array('gcash_number', $fieldNames, true)) {
            $db->query('ALTER TABLE `users` DROP COLUMN `gcash_number`');
        }
    }
}
