<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Records that a collector's commission for a period was actually PAID.
 * (Commission itself is still worked out from payments by CommissionService;
 * this table only remembers what the branch handed over, and when.)
 */
class CreateCommissionPayouts extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('commission_payouts')) {
            return;
        }

        $this->forge->addField([
            'payout_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => false, 'auto_increment' => true],
            'collector_user_id'  => ['type' => 'INT', 'constraint' => 11],
            'branch_id'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'period_start'       => ['type' => 'DATE'],
            'period_end'         => ['type' => 'DATE'],
            'total_collected'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'commission_amount'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'paid_at'            => ['type' => 'DATETIME'],
            'paid_by_user_id'    => ['type' => 'INT', 'constraint' => 11],
            'reference'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('payout_id', true);
        // The same collector can never be paid twice for the same period.
        $this->forge->addUniqueKey(['collector_user_id', 'period_start', 'period_end'], 'uq_commission_payout_period');
        $this->forge->addKey(['branch_id', 'period_start']);
        $this->forge->createTable('commission_payouts');
    }

    public function down()
    {
        $this->forge->dropTable('commission_payouts', true);
    }
}
