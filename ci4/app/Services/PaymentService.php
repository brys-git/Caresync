<?php

namespace App\Services;

use App\Services\ApprovalService;
use App\Services\MembershipService;

class PaymentService
{
    /**
     * Advance-payment discount schedule (percent off), keyed by months covered.
     *
     * This is the single source of truth for the advance-payment pricing. Both the
     * client advance-payment page and the staff/branch-admin cash recording flow
     * compute the discounted total through {@see advancePaymentBreakdown()}, and the
     * server re-validates the submitted amount against it, so a client or staff
     * member can never bypass or change the discount by editing a request value.
     *
     * Adjust these percentages in one place to change the whole system. A month
     * count not listed here gets no discount (0%).
     */
    public const ADVANCE_DISCOUNTS = [
        1 => 0,
        3 => 2,
        6 => 5,
        12 => 10,
    ];

    /**
     * Compute the advance-payment price breakdown for N months.
     *
     * @return array{months:int, monthly_fee:float, subtotal:float, discount_percent:float, discount_amount:float, total:float, has_discount:bool}
     */
    public static function advancePaymentBreakdown(float $monthlyFee, int $months): array
    {
        $months = max(1, $months);
        $discountPercent = (float) (self::ADVANCE_DISCOUNTS[$months] ?? 0);
        $subtotal = round($monthlyFee * $months, 2);
        $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        $total = round($subtotal - $discountAmount, 2);

        return [
            'months' => $months,
            'monthly_fee' => $monthlyFee,
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'has_discount' => $discountPercent > 0 && $discountAmount > 0,
        ];
    }

    /**
     * Discounted total due for an advance payment of N months.
     */
    public static function advanceTotal(float $monthlyFee, int $months): float
    {
        return self::advancePaymentBreakdown($monthlyFee, $months)['total'];
    }

    public function getBranchPlanHolderChoices(int $branchId): array
    {
        if ($branchId <= 0) {
            return [];
        }

        $rows = db_connect()->table('plans p')
            ->select('ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name, p.plan_id, p.status AS plan_status')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('ph.status', 'active')
            ->where('p.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->orderBy('p.plan_id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $name = trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')));
            $memberId = trim((string) ($row['unique_identifier'] ?? ''));
            $planId = (int) ($row['plan_id'] ?? 0);

            $row['client_name'] = $name !== '' ? $name : 'N/A';
            $row['label'] = $row['client_name']
                . ($memberId !== '' ? ' (' . $memberId . ')' : '')
                . ($planId > 0 ? ' - Plan #' . $planId : ' - No Plan Yet');
        }
        unset($row);

        return $rows;
    }

    public function getBranchMonitoringPayments(int $branchId): array
    {
        if ($branchId <= 0) {
            return [];
        }

        return db_connect()->table('payments pay')
            ->select('pay.payment_id, pay.plan_id, pay.amount, pay.months_covered, pay.payment_date, pay.payment_method, pay.reference_number, pay.official_receipt_number, pay.remarks, pay.status, pay.branch_id, b.branch_name, p.monthly_fee, p.months_paid, p.remaining_balance, p.next_due_date, ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = pay.branch_id', 'left')
            ->where('pay.branch_id', $branchId)
            ->orderBy('pay.payment_date', 'DESC')
            ->orderBy('pay.payment_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getBranchPlanOptions(int $branchId): array
    {
        if ($branchId <= 0) {
            return [];
        }

        return db_connect()->table('plans p')
            ->select('p.plan_id, p.monthly_fee, p.months_paid, p.remaining_balance, p.status AS plan_status, ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->where('ph.status', 'active')
            ->where('p.status', 'active')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getBranchPlans(int $branchId): array
    {
        $db = db_connect();
        $columns = array_map(static fn ($field) => $field->name, $db->getFieldData('plans'));
        $hasTotalPlanAmount = in_array('total_plan_amount', $columns, true);

        $rows = $db->table('plans p')
            ->select('p.plan_id, p.monthly_fee, p.months_paid, p.remaining_balance, p.status AS plan_status, ph.plan_holder_id, ph.branch_id, u.first_name, u.last_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $planId = (int) ($row['plan_id'] ?? 0);
            $monthlyFee = (float) ($row['monthly_fee'] ?? 0);
            $monthsPaid = (int) ($row['months_paid'] ?? 0);
            $remaining = (float) ($row['remaining_balance'] ?? 0);

            $totalAmount = $monthlyFee;
            if ($hasTotalPlanAmount && $planId > 0) {
                $rawTotal = (float) ($db->table('plans')
                    ->select('total_plan_amount')
                    ->where('plan_id', $planId)
                    ->get()
                    ->getRowArray()['total_plan_amount'] ?? 0);

                if ($rawTotal > 0) {
                    $totalAmount = $rawTotal;
                }
            }

            if ($totalAmount < $remaining) {
                $totalAmount = $remaining;
            }

            $paidAmount = max(0, round($totalAmount - $remaining, 2));
            if ($monthlyFee > 0 && $monthsPaid > 0 && $paidAmount <= 0) {
                $paidAmount = round($monthlyFee * $monthsPaid, 2);
                if ($totalAmount < $paidAmount) {
                    $totalAmount = $paidAmount;
                }
            }

            $row['client_name'] = trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')));
            $row['total_amount'] = $totalAmount;
            $row['paid_amount'] = $paidAmount;
        }
        unset($row);

        return $rows;
    }

    public function getPaymentHistory(int $planId): array
    {
        return db_connect()->table('payments pay')
            ->select('pay.payment_id, pay.plan_id, pay.amount, pay.months_covered, pay.payment_date, pay.payment_method, pay.reference_number, pay.official_receipt_number, pay.remarks, pay.status, pay.created_at, rb.first_name AS receiver_first_name, rb.last_name AS receiver_last_name')
            ->join('users rb', 'rb.user_id = pay.received_by', 'left')
            ->where('pay.plan_id', $planId)
            ->orderBy('pay.payment_date', 'DESC')
            ->orderBy('pay.payment_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function recordPayment(array $data): int
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $plan = $this->getPlanWithBranch((int) $data['plan_id']);
            if (! $plan) {
                throw new \RuntimeException('Selected plan does not exist.');
            }

            $amount = (float) $data['amount'];
            $monthsCovered = max(1, (int) ($data['months_covered'] ?? 1));

            $saved = $db->table('payments')->insert([
                'plan_id' => (int) $data['plan_id'],
                'amount' => number_format($amount, 2, '.', ''),
                'months_covered' => $monthsCovered,
                'payment_date' => (string) $data['payment_date'],
                'payment_method' => (string) $data['payment_method'],
                'reference_number' => $this->nullable($data['reference_number'] ?? null),
                'official_receipt_number' => $this->nullable($data['official_receipt_number'] ?? null),
                'received_by' => (int) $data['received_by'],
                'branch_id' => (int) $plan['branch_id'],
                'remarks' => $this->nullable($data['remarks'] ?? null),
                'status' => (string) $data['status'],
            ]);

            if (! $saved) {
                throw new \RuntimeException('Failed to record payment.');
            }

            $paymentId = (int) $db->insertID();
            $this->recomputePlan((int) $data['plan_id']);
            // NEW STATUS: 'verified' (was 'paid')
            if ((string) ($data['status'] ?? '') === 'verified') {
                $this->updateNextDueDate((int) $data['plan_id'], $monthsCovered);
                (new MembershipService())->applyMembershipCoverage((int) $data['plan_id'], $monthsCovered);
                (new ApprovalService())->approveInitialPayment($paymentId);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to complete payment transaction.');
            }

            $db->transCommit();

            return $paymentId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function updatePayment(int $paymentId, array $data): void
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $payment = $this->getPaymentById($paymentId);
            if (! $payment) {
                throw new \RuntimeException('Payment record was not found.');
            }

            $payload = [
                'amount' => number_format((float) $data['amount'], 2, '.', ''),
                'payment_date' => (string) $data['payment_date'],
                'payment_method' => (string) $data['payment_method'],
                'reference_number' => $this->nullable($data['reference_number'] ?? null),
                'official_receipt_number' => $this->nullable($data['official_receipt_number'] ?? null),
                'remarks' => $this->nullable($data['remarks'] ?? null),
                'status' => (string) $data['status'],
            ];

            if (isset($data['months_covered'])) {
                $payload['months_covered'] = max(1, (int) $data['months_covered']);
            }

            $updated = $db->table('payments')
                ->where('payment_id', $paymentId)
                ->update($payload);

            if (! $updated) {
                throw new \RuntimeException('Failed to update payment record.');
            }

            $this->recomputePlan((int) $payment['plan_id']);

            // NEW STATUS: 'verified' (was 'paid')
            if ((string) ($data['status'] ?? '') === 'verified') {
                $monthsCovered = max(1, (int) ($data['months_covered'] ?? (int) ($payment['months_covered'] ?? 1)));
                $this->updateNextDueDate((int) $payment['plan_id'], $monthsCovered);
                (new MembershipService())->applyMembershipCoverage((int) $payment['plan_id'], $monthsCovered);
                (new ApprovalService())->approveInitialPayment($paymentId);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to complete payment update transaction.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function recomputePlan(int $planId): void
    {
        $db = db_connect();

        $plan = $db->table('plans')
            ->select('plan_id, monthly_fee, remaining_balance')
            ->where('plan_id', $planId)
            ->get()
            ->getRowArray();

        if (! $plan) {
            throw new \RuntimeException('Plan record was not found for recomputation.');
        }

        $totalPaid = (float) ($db->table('payments')
            ->select('COALESCE(SUM(amount), 0) AS total_paid', false)
            ->where('plan_id', $planId)
            ->whereIn('status', ['paid', 'verified'])
            ->get()
            ->getRowArray()['total_paid'] ?? 0);

        $monthlyFee = (float) ($plan['monthly_fee'] ?? 0);
        $totalPlanAmount = $this->getTotalPlanAmount($plan);

        $remainingBalance = max(0, round($totalPlanAmount - $totalPaid, 2));
        $monthsPaid = $monthlyFee > 0 ? (int) floor($totalPaid / $monthlyFee) : 0;

        $status = $monthsPaid > 0 ? 'active' : 'inactive';

        $db->table('plans')
            ->where('plan_id', $planId)
            ->update([
                'remaining_balance' => number_format($remainingBalance, 2, '.', ''),
                'months_paid' => $monthsPaid,
                'status' => $status,
            ]);
    }

    public function getPlanWithBranch(int $planId): ?array
    {
        $row = db_connect()->table('plans p')
            ->select('p.plan_id, p.plan_holder_id, p.monthly_fee, p.remaining_balance, p.status, ph.branch_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('p.plan_id', $planId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getTotalPaidForPlan(int $planId): float
    {
        if ($planId <= 0) {
            return 0.0;
        }

        return (float) db_connect()->table('payments')
            ->select('COALESCE(SUM(amount), 0) AS total_paid', false)
            ->where('plan_id', $planId)
            ->whereIn('status', ['paid', 'verified'])
            ->get()
            ->getRowArray()['total_paid'] ?? 0;
    }

    public function getPaymentById(int $paymentId): ?array
    {
        $row = db_connect()->table('payments')
            ->where('payment_id', $paymentId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function getTotalPlanAmount(array $plan): float
    {
        $db = db_connect();
        $columns = array_map(static fn ($field) => $field->name, $db->getFieldData('plans'));

        if (in_array('total_plan_amount', $columns, true)) {
            $value = (float) ($db->table('plans')
                ->select('total_plan_amount')
                ->where('plan_id', (int) $plan['plan_id'])
                ->get()
                ->getRowArray()['total_plan_amount'] ?? 0);

            if ($value > 0) {
                return $value;
            }
        }

        return (float) ($plan['monthly_fee'] ?? 0);
    }

    private function nullable($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function updateNextDueDate(int $planId, int $monthsCovered): void
    {
        $plan = db_connect()->table('plans')
            ->select('plan_id, start_date, next_due_date')
            ->where('plan_id', $planId)
            ->get()
            ->getRowArray();

        if (! $plan) {
            return;
        }

        $baseDate = (string) ($plan['next_due_date'] ?? ($plan['start_date'] ?? date('Y-m-d')));
        if ($baseDate === '') {
            return;
        }

        $newDueDate = date('Y-m-d', strtotime('+' . max(1, $monthsCovered) . ' months', strtotime($baseDate)));

        db_connect()->table('plans')
            ->where('plan_id', $planId)
            ->update(['next_due_date' => $newDueDate]);
    }
}
