<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\PlanModel;
use App\Services\ActivityLogService;
use App\Services\MembershipService;
use App\Services\NotificationService;

class Payments extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');
        $membershipService = new MembershipService();
        $plans = in_array($roleId, [2, 3], true)
            ? $membershipService->getBranchPlans($branchId)
            : $membershipService->getBranchPlans(0);

        $paymentsBuilder = $db->table('payments pay')
            ->select('pay.payment_id, pay.plan_id, pay.amount, pay.payment_date, pay.payment_method, pay.reference_number, pay.branch_id, pay.remarks, pay.status, pay.created_at, p.monthly_fee, ph.unique_identifier, u.first_name, u.last_name, rb.first_name AS receiver_first_name, rb.last_name AS receiver_last_name')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('users rb', 'rb.user_id = pay.received_by', 'inner')
            ->orderBy('pay.payment_date', 'DESC')
            ->orderBy('pay.payment_id', 'DESC');

        if (in_array($roleId, [2, 3], true) && $branchId > 0) {
            $paymentsBuilder->where('pay.branch_id', $branchId);
        }

        $payments = $paymentsBuilder->get()->getResultArray();

        return view('payments/index', [
            'plans' => $plans,
            'payments' => $payments,
            'role_layout' => $this->resolveLayoutView(),
        ]);
    }

    public function store()
    {
        $rules = [
            'plan_id' => 'required|is_natural_no_zero',
            'amount' => 'required|decimal|greater_than[0]',
            'payment_date' => 'required|valid_date',
            'payment_method' => 'required|in_list[cash,gcash]',
            'status' => 'required|in_list[paid,pending,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $paymentMethod = (string) $this->request->getPost('payment_method');
        $referenceNumber = trim((string) $this->request->getPost('reference_number'));

        if ($paymentMethod === 'gcash' && $referenceNumber === '') {
            return redirect()->back()->withInput()->with('error', 'Reference number is required for GCash payments.');
        }

        $db = db_connect();
        $roleId = (int) session('role_id');
        $sessionBranchId = (int) session('branch_id');
        $planId = (int) $this->request->getPost('plan_id');

        $planRow = $db->table('plans p')
            ->select('p.plan_id, p.monthly_fee, p.remaining_balance, p.months_paid, p.status, ph.branch_id, ph.user_id AS plan_holder_user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('p.plan_id', $planId)
            ->get()
            ->getRowArray();

        if (! $planRow) {
            return redirect()->back()->withInput()->with('error', 'Selected plan was not found.');
        }

        $planBranchId = (int) $planRow['branch_id'];

        if (in_array($roleId, [2, 3], true) && $sessionBranchId > 0 && $planBranchId !== $sessionBranchId) {
            return redirect()->back()->withInput()->with('error', 'You can only record payments for your assigned branch.');
        }

        $db->transBegin();

        try {
            $paymentModel = new PaymentModel();
            $planModel = new PlanModel();
            $notificationService = new NotificationService();
            $activityLogService = new ActivityLogService();
            $amount = (float) $this->request->getPost('amount');
            $status = (string) $this->request->getPost('status');

            $paymentId = (int) $paymentModel->insert([
                'plan_id' => $planId,
                'amount' => number_format($amount, 2, '.', ''),
                'payment_date' => (string) $this->request->getPost('payment_date'),
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber === '' ? null : $referenceNumber,
                'received_by' => (int) session('user_id'),
                'branch_id' => $planBranchId,
                'remarks' => trim((string) $this->request->getPost('remarks')),
                'status' => $status,
            ], true);

            if ($paymentId <= 0) {
                throw new \RuntimeException('Failed to record payment.');
            }

            if ($status === 'paid') {
                $totalPaid = (float) ($db->table('payments')
                    ->select('COALESCE(SUM(amount), 0) AS total_paid', false)
                    ->where('plan_id', $planId)
                    ->where('status', 'paid')
                    ->get()
                    ->getRowArray()['total_paid'] ?? 0);

                $monthlyFee = (float) $planRow['monthly_fee'];
                $monthsPaid = $monthlyFee > 0 ? (int) floor($totalPaid / $monthlyFee) : 0;
                $remainingBalance = max(0, round($monthlyFee - $totalPaid, 2));
                $nextStatus = $remainingBalance <= 0 ? 'completed' : (string) $planRow['status'];

                $updated = $planModel->update($planId, [
                    'months_paid' => $monthsPaid,
                    'remaining_balance' => number_format($remainingBalance, 2, '.', ''),
                    'status' => $nextStatus,
                ]);

                if (! $updated) {
                    throw new \RuntimeException('Payment recorded but failed to update plan balance.');
                }
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed during payment recording.');
            }

            $db->transCommit();

            $notificationService->notify(
                (int) $planRow['plan_holder_user_id'],
                'A payment has been recorded on your plan.',
                $status === 'paid' ? 'payment_approved' : 'general'
            );

            $activityLogService->log(
                (int) session('user_id'),
                'created',
                'payment',
                $paymentId,
                'Recorded payment for plan #' . $planId,
                null,
                [
                    'plan_id' => $planId,
                    'amount' => number_format($amount, 2, '.', ''),
                    'status' => $status,
                    'payment_type' => 'monthly_contribution',
                ]
            );

            return redirect()->to('/payments')->with('success', 'Payment recorded successfully and payment history updated.');
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function resolveLayoutView(): string
    {
        $role = (int) session()->get('role_id');

        if ($role === 1) {
            return 'layouts/admin';
        }

        if ($role === 2) {
            return 'layouts/branch_admin';
        }

        if ($role === 3) {
            return 'layouts/staff';
        }

        return 'layouts/plan_holder';
    }
}
