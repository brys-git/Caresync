<?php

namespace App\Services;

/**
 * Money a collector collected in the field, from the moment it is collected until the office confirms it.
 *
 *   collected  status 'pending' + payments.awaiting_remittance = 1. The client has paid, so the plan ledger, overdue,
 *              grace and lapse already count it (see PaymentService::countedSql). Nothing is "verified" yet.
 *   confirmed  the Branch Admin confirms the remittance that contains it: status 'paid', then the same steps every
 *              other verified payment goes through (coverage, cycle, allocation) run, and the client is told.
 *
 * This is the one place that does both, so RemittanceService and the controllers never repeat the steps.
 */
class PaymentVerificationService
{
    /** A collector's collection counts at once for the ledger (the row itself stays 'pending'). */
    public function countCollected(int $paymentId, int $planId, int $monthsCovered): void
    {
        (new MembershipService())->applyMembershipCoverage($planId, max(1, $monthsCovered));
        (new CycleService())->afterPaymentVerified($paymentId, $planId);
    }

    /** Tells the client their collector took the payment and what happens next. */
    public function notifyCollected(array $payment): void
    {
        $userId = $this->clientUserId((int) $payment['plan_id']);
        if ($userId <= 0) {
            return;
        }
        $ref = trim((string) ($payment['official_receipt_number'] ?? '')) !== '' ? ' (OR ' . $payment['official_receipt_number'] . ')' : (trim((string) ($payment['reference_number'] ?? '')) !== '' ? ' (ref ' . $payment['reference_number'] . ')' : '');
        (new NotificationService())->notify($userId, 'Your payment of ' . cs_peso((float) $payment['amount']) . $ref . ' was collected. ' . PaymentService::COLLECTED_LABEL . '.', 'payment_pending');
    }

    /**
     * The office confirms a collected payment: it becomes 'paid' through the normal verification path.
     * Runs inside the caller's transaction (RemittanceService::confirm); throws on any problem so the whole
     * remittance rolls back.
     */
    public function confirmCollected(int $paymentId, int $verifierId, string $remarks): void
    {
        $db = db_connect();
        $p  = $db->query('SELECT * FROM payments WHERE payment_id = ? FOR UPDATE', [$paymentId])->getRowArray();
        if (! $p || $p['status'] !== 'pending' || (int) $p['awaiting_remittance'] !== 1) {
            throw new \RuntimeException('Payment #' . $paymentId . ' is not waiting for confirmation.');
        }

        $db->table('payments')->where('payment_id', $paymentId)->update([
            'status'              => 'paid',
            'awaiting_remittance' => 0,
            'verified_by'         => $verifierId,
            'verified_at'         => date('Y-m-d H:i:s'),
            'remarks'             => $remarks,
        ]);

        $planId = (int) $p['plan_id'];
        (new MembershipService())->applyMembershipCoverage($planId, max(1, (int) ($p['months_covered'] ?? 1)));
        (new CycleService())->afterPaymentVerified($paymentId, $planId);

        $userId = $this->clientUserId($planId);
        if ($userId > 0) {
            $ref = trim((string) ($p['official_receipt_number'] ?? '')) !== '' ? ' (OR ' . $p['official_receipt_number'] . ')' : '';
            (new NotificationService())->notify($userId, 'Your payment of ' . cs_peso((float) $p['amount']) . $ref . ' is confirmed by the office.', 'payment_approved');
        }
        (new ActivityLogService())->log($verifierId, 'approved', 'payment', $paymentId, 'Collected payment confirmed with its remittance', ['status' => 'pending'], ['status' => 'paid']);
    }

    /** A collected payment that is rejected before it reaches a remittance: it stops counting. */
    public function rejectCollected(int $paymentId, int $verifierId, string $reason): void
    {
        $db = db_connect();
        $p  = $db->table('payments')->where('payment_id', $paymentId)->get(1)->getRowArray();
        if (! $p) {
            return;
        }
        $db->table('payments')->where('payment_id', $paymentId)->update([
            'status' => 'cancelled', 'awaiting_remittance' => 0, 'verified_by' => $verifierId, 'verified_at' => date('Y-m-d H:i:s'),
            'remarks' => 'Collected payment rejected by branch admin' . ($reason !== '' ? ': ' . $reason : ''),
        ]);
        (new ClaimAvailService())->onPaymentReversed($paymentId, (int) $p['plan_id']);
        (new PlanLedgerService())->sync((int) $p['plan_id'], true);
    }

    private function clientUserId(int $planId): int
    {
        return (int) (db_connect()->table('plans p')->select('ph.user_id')->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')->where('p.plan_id', $planId)->get(1)->getRowArray()['user_id'] ?? 0);
    }
}
