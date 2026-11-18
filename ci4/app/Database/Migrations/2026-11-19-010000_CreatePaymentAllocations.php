<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One payment can pay several things (the plan contribution AND a package
 * instalment). Each slice is a row here, so a receipt can say exactly what the
 * money was applied to and a package instalment is linked to its avail.
 *
 * Existing payments are NOT backfilled: a payment with no rows here counts 100%
 * as plan contributions, exactly as before. Only the package slices
 * (avail_down_payment / avail_instalment) are taken out of the plan's own
 * contributions by PlanLedgerService.
 */
class CreatePaymentAllocations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('payment_allocations')) {
            return;
        }

        $this->forge->addField([
            'allocation_id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'payment_id'          => ['type' => 'INT', 'constraint' => 11],
            'target_type'         => ['type' => 'ENUM', 'constraint' => ['plan_contribution', 'avail_down_payment', 'avail_instalment']],
            'plan_id'             => ['type' => 'INT', 'constraint' => 11],
            'avail_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'instalment_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'contribution_number' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'amount'              => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'created_at'          => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('allocation_id', true);
        $this->forge->addKey('payment_id');
        $this->forge->addKey('plan_id');
        $this->forge->addKey('avail_id');
        $this->forge->addKey('instalment_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'payment_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('avail_id', 'package_avails', 'avail_id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('instalment_id', 'package_avail_instalments', 'instalment_id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('payment_allocations', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('payment_allocations', true);
    }
}
