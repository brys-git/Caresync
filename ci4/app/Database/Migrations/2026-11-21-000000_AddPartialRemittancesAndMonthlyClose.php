<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Partial remittances and the monthly close.
 *
 *   payments.awaiting_remittance  1 = a collector collected this money (cash, or a GCash reference they checked on their own
 *                                 GCash account) and the office has not confirmed it yet. The status stays 'pending' - it only
 *                                 becomes 'paid' when the Branch Admin confirms the remittance that contains it. While it is 1
 *                                 the money already counts for the plan ledger / overdue / grace / lapse (the client has paid).
 *   payments.collected_at         when the collector collected it (used to flag money not remitted within remittanceMaxDays).
 *   remittances.cash_amount / gcash_amount   the gross split by how it was collected.
 *   collection_marks              "Not collected this month" with a reason, per collector / plan holder / month.
 *   monthly_collection_summaries  one row when a collector closes a month (members, collected, not collected + reasons, money).
 *
 * Additive only: nothing is changed or removed.
 */
class AddPartialRemittancesAndMonthlyClose extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('awaiting_remittance', 'payments')) {
            $this->forge->addColumn('payments', [
                'awaiting_remittance' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                'collected_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->db->query('ALTER TABLE payments ADD INDEX idx_payments_awaiting (awaiting_remittance, received_by)');
        }

        if ($this->db->tableExists('remittances') && ! $this->db->fieldExists('cash_amount', 'remittances')) {
            $this->forge->addColumn('remittances', [
                'cash_amount'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'gcash_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            ]);
        }

        if (! $this->db->tableExists('collection_marks')) {
            $this->forge->addField([
                'mark_id'           => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'collector_user_id' => ['type' => 'INT', 'constraint' => 11],
                'branch_id'         => ['type' => 'INT', 'constraint' => 11],
                'plan_holder_id'    => ['type' => 'INT', 'constraint' => 11],
                'month'             => ['type' => 'DATE'],
                'reason_code'       => ['type' => 'VARCHAR', 'constraint' => 30],
                'reason_note'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'marked_at'         => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('mark_id', true);
            $this->forge->addUniqueKey(['collector_user_id', 'plan_holder_id', 'month'], 'uq_collection_marks');
            $this->forge->addKey(['branch_id', 'month']);
            $this->forge->createTable('collection_marks', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->tableExists('monthly_collection_summaries')) {
            $this->forge->addField([
                'summary_id'          => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'collector_user_id'   => ['type' => 'INT', 'constraint' => 11],
                'branch_id'           => ['type' => 'INT', 'constraint' => 11],
                'month'               => ['type' => 'DATE'],
                'members_assigned'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'collected_count'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'not_collected_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'not_collected_json'  => ['type' => 'TEXT', 'null' => true],
                'gross_amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'cash_amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'gcash_amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'commission_amount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'net_amount'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'remittance_count'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'closed_at'           => ['type' => 'DATETIME', 'null' => true],
                'closed_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
            $this->forge->addKey('summary_id', true);
            $this->forge->addUniqueKey(['collector_user_id', 'month'], 'uq_monthly_summary');
            $this->forge->addKey(['branch_id', 'month']);
            $this->forge->createTable('monthly_collection_summaries', true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $this->forge->dropTable('monthly_collection_summaries', true);
        $this->forge->dropTable('collection_marks', true);
        if ($this->db->fieldExists('cash_amount', 'remittances')) {
            $this->forge->dropColumn('remittances', ['cash_amount', 'gcash_amount']);
        }
        if ($this->db->fieldExists('awaiting_remittance', 'payments')) {
            $this->db->query('ALTER TABLE payments DROP INDEX idx_payments_awaiting');
            $this->forge->dropColumn('payments', ['awaiting_remittance', 'collected_at']);
        }
    }
}
