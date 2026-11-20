<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * GCash verification review screen.
 *
 *   payments.paid_at ...            what the client entered with the screenshot (when they paid, how much GCash shows,
 *                                   the sender's name), and WHOSE GCash account they paid (the collector, or NULL = the
 *                                   branch account) - that owner is the one who verifies it.
 *   payments.proof_requested_at     the verifier asked for a clearer screenshot (the payment stays pending).
 *   payments.rejection_code/reason  why a payment was rejected, shown to the client.
 *   payments.proof_image            (existing column) the screenshot, stored under writable/uploads/payment-proofs.
 *   payment_reviews                 audit: who decided, when, the checklist, the reason, the amounts.
 *
 * Additive: nothing existing is changed or removed.
 */
class AddGcashReview extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('paid_at', 'payments')) {
            $this->forge->addColumn('payments', [
                'paid_at'                => ['type' => 'DATETIME', 'null' => true],
                'sender_name'            => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'amount_sent'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
                'receiving_gcash_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'receiving_gcash_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'proof_requested_at'     => ['type' => 'DATETIME', 'null' => true],
                'proof_request_note'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'rejection_code'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'rejection_reason'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            ]);
            $this->db->query('ALTER TABLE payments ADD INDEX idx_payments_gcash_queue (payment_method, status, receiving_gcash_user_id)');
        }

        if (! $this->db->tableExists('payment_reviews')) {
            $this->forge->addField([
                'review_id'       => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'payment_id'      => ['type' => 'INT', 'constraint' => 11],
                'decision'        => ['type' => 'ENUM', 'constraint' => ['verified', 'different_amount', 'proof_requested', 'rejected'], 'null' => false],
                'decided_by'      => ['type' => 'INT', 'constraint' => 11],
                'decided_at'      => ['type' => 'DATETIME'],
                'checklist_json'  => ['type' => 'TEXT', 'null' => true],
                'last4_ok'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
                'reason_code'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'reason_note'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'amount_expected' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
                'amount_actual'   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
                'warnings_json'   => ['type' => 'TEXT', 'null' => true],
            ]);
            $this->forge->addKey('review_id', true);
            $this->forge->addKey(['payment_id', 'decided_at']);
            $this->forge->createTable('payment_reviews', true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $this->forge->dropTable('payment_reviews', true);
        if ($this->db->fieldExists('paid_at', 'payments')) {
            $this->db->query('ALTER TABLE payments DROP INDEX idx_payments_gcash_queue');
            $this->forge->dropColumn('payments', ['paid_at', 'sender_name', 'amount_sent', 'receiving_gcash_user_id', 'receiving_gcash_number', 'proof_requested_at', 'proof_request_note', 'rejection_code', 'rejection_reason']);
        }
    }
}
