<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Schema changes needed to import paper-record clients (php spark import:aclan-clients):
 *
 *  users.email                 nullable - imported clients have no email and sign in
 *                              with their username (the unique index allows many NULLs)
 *  beneficiaries.relationship  nullable - some paper records leave it blank
 *  plan_holders.status         + 'deceased'
 *  plan_holders.legacy_reference     where the record came from, e.g. "Imported from Record Profiling 2023, FVA-045"
 *  plan_holders.needs_review / review_notes   staff confirm these details with the client
 *  plan_holders.assigned_collector_id         the collector/staff member who enrolled the client
 */
class PrepareBulkClientImport extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE users MODIFY email VARCHAR(100) NULL');
        $this->db->query('ALTER TABLE beneficiaries MODIFY relationship VARCHAR(50) NULL');
        $this->db->query("ALTER TABLE plan_holders MODIFY status ENUM('active','inactive','deceased') DEFAULT 'active'");

        $columns = [
            'legacy_reference'      => 'VARCHAR(150) NULL',
            'needs_review'          => 'TINYINT(1) NOT NULL DEFAULT 0',
            'review_notes'          => 'TEXT NULL',
            'assigned_collector_id' => 'INT NULL',
        ];
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'plan_holders')) {
                $this->db->query("ALTER TABLE plan_holders ADD COLUMN {$name} {$definition}");
            }
        }
        if ($this->db->query("SHOW INDEX FROM plan_holders WHERE Key_name = 'idx_plan_holders_legacy_ref'")->getNumRows() === 0) {
            $this->db->query('ALTER TABLE plan_holders ADD KEY idx_plan_holders_legacy_ref (legacy_reference)');
        }
    }

    public function down()
    {
        if ($this->db->query("SHOW INDEX FROM plan_holders WHERE Key_name = 'idx_plan_holders_legacy_ref'")->getNumRows() > 0) {
            $this->db->query('ALTER TABLE plan_holders DROP INDEX idx_plan_holders_legacy_ref');
        }
        foreach (['legacy_reference', 'needs_review', 'review_notes', 'assigned_collector_id'] as $column) {
            if ($this->db->fieldExists($column, 'plan_holders')) {
                $this->forge->dropColumn('plan_holders', $column);
            }
        }
        // Restoring NOT NULL / the old enum is only safe when no row uses the new values.
        $this->db->query("UPDATE plan_holders SET status = 'inactive' WHERE status = 'deceased'");
        $this->db->query("ALTER TABLE plan_holders MODIFY status ENUM('active','inactive') DEFAULT 'active'");
        $this->db->query("UPDATE beneficiaries SET relationship = '' WHERE relationship IS NULL");
        $this->db->query('ALTER TABLE beneficiaries MODIFY relationship VARCHAR(50) NOT NULL');
    }
}
