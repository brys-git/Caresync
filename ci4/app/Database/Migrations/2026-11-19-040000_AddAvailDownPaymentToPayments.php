<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A package down payment is its own kind of payment: payment_type 'avail_down_payment',
 * linked to its avail by payments.avail_id. It is applied only to that down payment and
 * never counts as a plan contribution. Both changes are additive: every existing payment
 * keeps its type and has avail_id NULL.
 */
class AddAvailDownPaymentToPayments extends Migration
{
    public function up()
    {
        $type = $this->db->query("SELECT column_type FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'payments' AND column_name = 'payment_type'")->getRowArray();
        if ($type && ! str_contains((string) $type['column_type'], 'avail_down_payment')) {
            $this->db->query("ALTER TABLE payments MODIFY payment_type ENUM('initial_registration','monthly_contribution','service_payment','addon_payment','reinstatement_fee','avail_down_payment') NOT NULL DEFAULT 'monthly_contribution'");
        }

        if (! $this->db->fieldExists('avail_id', 'payments')) {
            $this->forge->addColumn('payments', [
                'avail_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            ]);
            $this->db->query('ALTER TABLE payments ADD INDEX idx_payments_avail (avail_id)');
            if ($this->db->tableExists('package_avails')) {
                $this->db->query('ALTER TABLE payments ADD CONSTRAINT payments_avail_id_foreign FOREIGN KEY (avail_id) REFERENCES package_avails (avail_id) ON DELETE RESTRICT ON UPDATE RESTRICT');
            }
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('avail_id', 'payments')) {
            $this->db->query('ALTER TABLE payments DROP FOREIGN KEY payments_avail_id_foreign');
            $this->db->query('ALTER TABLE payments DROP INDEX idx_payments_avail');
            $this->forge->dropColumn('payments', 'avail_id');
        }
        // The enum value is left in place on purpose: removing it could fail if a row already uses it.
    }
}
