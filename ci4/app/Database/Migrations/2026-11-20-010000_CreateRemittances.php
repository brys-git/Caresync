<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Part E: a collector REMITS the cash they collected to the branch and keeps a commission.
 *
 *   remittances            one row per remittance: the gross collected, the commission (rate and what it applied to are
 *                          stored AS THEY WERE, so a later change of the setting never rewrites history), the net to hand
 *                          over, and where it stands (submitted -> confirmed by the Branch Admin, or discrepancy).
 *   payments.remittance_id the payments that went into a remittance (NULL = not remitted yet). A payment can be in
 *                          one remittance only.
 *
 * Additive: nothing existing is changed or removed. (commission_payouts, the old "Mark as paid" table, is left alone.)
 */
class CreateRemittances extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('remittances')) {
            $this->forge->addField([
                'remittance_id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'branch_id'            => ['type' => 'INT', 'constraint' => 11],
                'collector_user_id'    => ['type' => 'INT', 'constraint' => 11],
                'status'               => ['type' => 'ENUM', 'constraint' => ['submitted', 'confirmed', 'discrepancy', 'cancelled'], 'default' => 'submitted'],
                'payment_count'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'gross_amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'commission_rate'      => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
                'commission_applies_to' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'plan_only'],
                'commission_base'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'commission_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'net_amount'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'collector_note'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'submitted_at'         => ['type' => 'DATETIME', 'null' => true],
                'confirmed_by'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'confirmed_at'         => ['type' => 'DATETIME', 'null' => true],
                'received_amount'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
                'discrepancy_note'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'discrepancy_by'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'discrepancy_at'       => ['type' => 'DATETIME', 'null' => true],
                'created_at'           => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addKey('remittance_id', true);
            $this->forge->addKey(['branch_id', 'status']);
            $this->forge->addKey('collector_user_id');
            $this->forge->createTable('remittances', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->fieldExists('remittance_id', 'payments')) {
            $this->forge->addColumn('payments', [
                'remittance_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
            $this->db->query('ALTER TABLE payments ADD INDEX idx_payments_remittance (remittance_id)');
            $this->db->query('ALTER TABLE payments ADD CONSTRAINT payments_remittance_id_foreign FOREIGN KEY (remittance_id) REFERENCES remittances (remittance_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('remittance_id', 'payments')) {
            $this->db->query('ALTER TABLE payments DROP FOREIGN KEY payments_remittance_id_foreign');
            $this->db->query('ALTER TABLE payments DROP INDEX idx_payments_remittance');
            $this->forge->dropColumn('payments', 'remittance_id');
        }
        $this->forge->dropTable('remittances', true);
    }
}
