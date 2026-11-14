<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Add Branch" feature support.
 *
 *  branches.branch_code            unique, system generated (KKM-CAL-001)
 *  branches.branch_type            main | branch
 *  branches.address_region / email / operating_hours / manager_contact_number
 *  branches.manager_user_id        the assigned manager (an existing users row);
 *                                  the older manager_* name columns stay and are
 *                                  copied from that user so existing readers keep working
 *  branches.created_by / updated_by  audit trail (created_at / updated_at already exist)
 *  branch_manager_history          who managed which branch and when, so changing
 *                                  the manager never rewrites anything
 */
class ExpandBranches extends Migration
{
    public function up()
    {
        $columns = [
            'branch_code'            => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'branch_id'],
            'branch_type'            => ['type' => 'ENUM', 'constraint' => ['main', 'branch'], 'default' => 'branch', 'after' => 'branch_name'],
            'address_region'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email'                  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'operating_hours'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'manager_user_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'manager_contact_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'created_by'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_by'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ];

        $missing = [];
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'branches')) {
                $missing[$name] = $definition;
            }
        }
        if ($missing !== []) {
            $this->forge->addColumn('branches', $missing);
        }

        // Backfill codes for branches that already exist.
        $rows = $this->db->table('branches')->select('branch_id, address_city')->where('branch_code', null)->orderBy('branch_id', 'ASC')->get()->getResultArray();
        foreach ($rows as $row) {
            $prefix = 'KKM-' . self::cityCode((string) $row['address_city']) . '-';
            $n = 1;
            do {
                $code = $prefix . str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
            } while ($this->db->table('branches')->where('branch_code', $code)->countAllResults() > 0);
            $this->db->table('branches')->where('branch_id', $row['branch_id'])->update(['branch_code' => $code]);
        }

        $hasIndex = $this->db->query("SHOW INDEX FROM branches WHERE Key_name = 'uniq_branch_code'")->getNumRows() > 0;
        if (! $hasIndex) {
            $this->db->query('ALTER TABLE branches ADD UNIQUE KEY uniq_branch_code (branch_code)');
        }

        $this->forge->addField([
            'history_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11],
            'manager_user_id' => ['type' => 'INT', 'constraint' => 11],
            'assigned_at'     => ['type' => 'DATETIME'],
            'assigned_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'ended_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('history_id', true);
        $this->forge->addKey('branch_id');
        $this->forge->createTable('branch_manager_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('branch_manager_history', true);

        if ($this->db->query("SHOW INDEX FROM branches WHERE Key_name = 'uniq_branch_code'")->getNumRows() > 0) {
            $this->db->query('ALTER TABLE branches DROP INDEX uniq_branch_code');
        }

        foreach (['branch_code', 'branch_type', 'address_region', 'email', 'operating_hours', 'manager_user_id', 'manager_contact_number', 'created_by', 'updated_by'] as $column) {
            if ($this->db->fieldExists($column, 'branches')) {
                $this->forge->dropColumn('branches', $column);
            }
        }
    }

    /** First three letters of the city, ignoring "City of" / "City" words. */
    public static function cityCode(string $city): string
    {
        $clean = preg_replace('/\b(city|of|municipality)\b/i', '', $city);
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $clean));

        return str_pad(substr($letters, 0, 3), 3, 'X');
    }
}
