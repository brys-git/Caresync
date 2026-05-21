<?php

namespace App\Controllers\PlanHolder;

use App\Controllers\BaseController;
use App\Services\MembershipService;
use CodeIgniter\Exceptions\PageNotFoundException;

class PaymentController extends BaseController
{
    public function index(): string
    {
        [$userId, $planHolder] = $this->getCurrentPlanHolderContext();
        $status = strtolower(trim((string) ($this->request->getGet('status') ?? '')));

        if (! in_array($status, ['', 'paid', 'pending', 'cancelled'], true)) {
            $status = '';
        }

        $plan = db_connect()->table('plans p')
            ->select('p.plan_id, p.monthly_fee, p.start_date, p.status AS plan_status, p.months_paid, p.remaining_balance, p.package_id')
            ->where('p.plan_holder_id', (int) $planHolder['plan_holder_id'])
            ->orderBy("CASE WHEN p.status = 'active' THEN 1 ELSE 2 END", 'ASC', false)
            ->orderBy('p.plan_id', 'DESC')
            ->get()
            ->getRowArray();

        if ($plan) {
            $plan['program_name'] = MembershipService::PROGRAM_NAME;
        }

        $payments = [];
        $summary = [
            'total_paid' => 0.0,
            'remaining_balance' => (float) ($plan['remaining_balance'] ?? 0),
            'months_paid' => (int) ($plan['months_paid'] ?? 0),
            'expected_months' => 12,
            'progress_percent' => 0,
        ];

        if ($plan) {
            $paymentsBuilder = db_connect()->table('payments pay')
                ->select('pay.payment_id, pay.plan_id, pay.amount, pay.payment_date, pay.payment_method, pay.reference_number, pay.status, pay.remarks, pay.received_by, pay.branch_id, rb.first_name AS receiver_first_name, rb.last_name AS receiver_last_name, b.branch_name')
                ->join('users rb', 'rb.user_id = pay.received_by', 'left')
                ->join('branches b', 'b.branch_id = pay.branch_id', 'left')
                ->where('pay.plan_id', (int) $plan['plan_id'])
                ->orderBy('pay.payment_date', 'DESC')
                ->orderBy('pay.payment_id', 'DESC');

            if ($status !== '') {
                $paymentsBuilder->where('pay.status', $status);
            }

            $payments = $paymentsBuilder->get()->getResultArray();

            $totalPaid = (float) (db_connect()->table('payments')
                ->select('COALESCE(SUM(amount), 0) AS total_paid', false)
                ->where('plan_id', (int) $plan['plan_id'])
                ->where('status', 'paid')
                ->get()
                ->getRowArray()['total_paid'] ?? 0);

            $monthlyFee = (float) ($plan['monthly_fee'] ?? 0);
            $remainingBalance = (float) ($plan['remaining_balance'] ?? 0);
            $monthsPaid = (int) ($plan['months_paid'] ?? 0);

            $computedMonths = 12;
            if ($monthlyFee > 0) {
                $computedMonths = (int) ceil(($totalPaid + max(0, $remainingBalance)) / $monthlyFee);
            }

            $expectedMonths = max(12, $computedMonths, $monthsPaid);
            $progressPercent = $expectedMonths > 0
                ? min(100, round(($monthsPaid / $expectedMonths) * 100, 2))
                : 0;

            $summary = [
                'total_paid' => $totalPaid,
                'remaining_balance' => $remainingBalance,
                'months_paid' => $monthsPaid,
                'expected_months' => $expectedMonths,
                'progress_percent' => $progressPercent,
            ];
        }

        return view('plan_holder/payments/index', [
            'role_layout' => 'layouts/plan_holder',
            'plan_holder' => $planHolder,
            'plan' => $plan,
            'payments' => $payments,
            'selected_status' => $status,
            'summary' => $summary,
        ]);
    }

    public function details(int $paymentId)
    {
        [$userId, $planHolder] = $this->getCurrentPlanHolderContext();

        $payment = db_connect()->table('payments pay')
            ->select('pay.payment_id, pay.plan_id, pay.amount, pay.payment_date, pay.payment_method, pay.reference_number, pay.status, pay.remarks, pay.received_by, pay.branch_id, rb.first_name AS receiver_first_name, rb.last_name AS receiver_last_name, b.branch_name')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users rb', 'rb.user_id = pay.received_by', 'left')
            ->join('branches b', 'b.branch_id = pay.branch_id', 'left')
            ->where('pay.payment_id', $paymentId)
            ->where('ph.user_id', $userId)
            ->get()
            ->getRowArray();

        if (! $payment) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Payment record not found.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'payment' => $payment,
        ]);
    }

    private function getCurrentPlanHolderContext(): array
    {
        $userId = (int) session('user_id');
        $roleId = (int) session('role_id');

        if ($userId <= 0 || $roleId !== 4) {
            throw PageNotFoundException::forPageNotFound();
        }

        $planHolder = db_connect()->table('plan_holders')
            ->select('plan_holders.plan_holder_id, plan_holders.user_id, plan_holders.unique_identifier, plan_holders.branch_id, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = plan_holders.user_id', 'left')
            ->where('user_id', $userId)
            ->orderBy('plan_holders.plan_holder_id', 'DESC')
            ->get()
            ->getRowArray();

        if (! $planHolder) {
            throw PageNotFoundException::forPageNotFound();
        }

        return [$userId, $planHolder];
    }
}
