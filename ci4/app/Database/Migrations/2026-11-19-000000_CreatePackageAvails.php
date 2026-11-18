<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Avail = a plan holder gets a package OTHER than the one in their own plan.
 * The package price is added to their balance, with a down payment and fixed
 * monthly instalments (ClaimAvailService owns the rules; Config\CareSync holds the numbers).
 *
 * package_avails            one row per approved avail: package, price, the down-payment
 *                           rate and amount USED, the waiver (who/when/why), the term, the
 *                           monthly instalment and the status. The rate and term are stored
 *                           here so later setting changes never change an existing avail.
 * package_avail_instalments the schedule (due date, amount, paid amount). It is written
 *                           once the down payment is verified or the waiver is approved.
 *
 * Nothing here has ON DELETE CASCADE: deleting a plan or a claim must never silently
 * delete money records.
 */
class CreatePackageAvails extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('package_avails')) {
            $this->forge->addField([
                'avail_id'              => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'application_id'        => ['type' => 'INT', 'constraint' => 11],
                'plan_holder_id'        => ['type' => 'INT', 'constraint' => 11],
                'plan_id'               => ['type' => 'INT', 'constraint' => 11],
                'package_id'            => ['type' => 'INT', 'constraint' => 11],
                'branch_id'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'service_id'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'cycle_number'          => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'package_price'         => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                // Rate / days / term as they were when the avail was approved.
                'down_payment_rate'     => ['type' => 'DECIMAL', 'constraint' => '5,4', 'default' => 0],
                'down_payment_amount'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'down_payment_status'   => ['type' => 'ENUM', 'constraint' => ['due', 'paid', 'waived'], 'default' => 'due'],
                'down_payment_due_by'   => ['type' => 'DATE', 'null' => true],
                'down_payment_payment_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'down_payment_paid_at'  => ['type' => 'DATETIME', 'null' => true],
                'waived_by'             => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'waived_at'             => ['type' => 'DATETIME', 'null' => true],
                'waiver_reason'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                // The amount paid in instalments: price - down payment (the full price when waived).
                'financed_amount'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'term_months'           => ['type' => 'SMALLINT', 'constraint' => 5],
                'monthly_instalment'    => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'first_due_date'        => ['type' => 'DATE', 'null' => true],
                'status'                => ['type' => 'ENUM', 'constraint' => ['awaiting_down_payment', 'active', 'settled', 'cancelled'], 'default' => 'awaiting_down_payment'],
                'cancelled_reason'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'cancelled_at'          => ['type' => 'DATETIME', 'null' => true],
                'approved_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'approved_at'           => ['type' => 'DATETIME', 'null' => true],
                'created_at'            => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
                'updated_at'            => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            ]);
            $this->forge->addKey('avail_id', true);
            $this->forge->addUniqueKey('application_id');
            $this->forge->addKey('plan_id');
            $this->forge->addKey('plan_holder_id');
            $this->forge->addKey(['status', 'down_payment_due_by']);
            $this->forge->addForeignKey('application_id', 'service_applications', 'application_id', 'RESTRICT', 'RESTRICT');
            $this->forge->addForeignKey('plan_id', 'plans', 'plan_id', 'RESTRICT', 'RESTRICT');
            $this->forge->addForeignKey('plan_holder_id', 'plan_holders', 'plan_holder_id', 'RESTRICT', 'RESTRICT');
            $this->forge->createTable('package_avails', true, ['ENGINE' => 'InnoDB']);
            $this->db->query('ALTER TABLE package_avails MODIFY updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        if (! $this->db->tableExists('package_avail_instalments')) {
            $this->forge->addField([
                'instalment_id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'avail_id'      => ['type' => 'INT', 'constraint' => 11],
                'number'        => ['type' => 'SMALLINT', 'constraint' => 5],
                'due_date'      => ['type' => 'DATE'],
                'amount'        => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'paid_amount'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'paid_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('instalment_id', true);
            $this->forge->addUniqueKey(['avail_id', 'number']);
            $this->forge->addKey('due_date');
            $this->forge->addForeignKey('avail_id', 'package_avails', 'avail_id', 'RESTRICT', 'RESTRICT');
            $this->forge->createTable('package_avail_instalments', true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $this->forge->dropTable('package_avail_instalments', true);
        $this->forge->dropTable('package_avails', true);
    }
}
