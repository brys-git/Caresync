<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\PaymentService;
use CodeIgniter\Exceptions\PageNotFoundException;

class PaymentController extends BaseController
{
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    public function index(): string
    {
        $branchId = (int) session('branch_id');
        $plans = $branchId > 0 ? $this->paymentService->getBranchPlans($branchId) : [];
        $planHolderChoices = $branchId > 0 ? $this->paymentService->getBranchPlanHolderChoices($branchId) : [];

        $planStatuses = [];
        foreach ($plans as $plan) {
            $remaining = (float) ($plan['remaining_balance'] ?? 0);
            $monthsPaid = (int) ($plan['months_paid'] ?? 0);

            if ($remaining <= 0) {
                $planStatuses[(int) $plan['plan_id']] = 'Fully Paid';
            } elseif ($monthsPaid > 0) {
                $planStatuses[(int) $plan['plan_id']] = 'Active';
            } else {
                $planStatuses[(int) $plan['plan_id']] = 'Unpaid';
            }
        }

        $tab = (string) $this->request->getGet('tab');
        if (! in_array($tab, ['monitoring', 'record', 'update'], true)) {
            $tab = 'monitoring';
        }

        $selectedPlanId = (int) $this->request->getGet('plan_id');
        $history = [];
        if ($selectedPlanId > 0 && $this->canAccessPlan($selectedPlanId, $branchId)) {
            $history = $this->paymentService->getPaymentHistory($selectedPlanId);
        }

        $selectedPayment = null;
        $editPaymentId = (int) $this->request->getGet('payment_id');
        if ($editPaymentId > 0) {
            $payment = $this->paymentService->getPaymentById($editPaymentId);
            if ($payment && $this->canAccessPayment((int) $payment['payment_id'], $branchId)) {
                $selectedPayment = $payment;
                $tab = 'update';
            }
        }

        return view('branch_admin/payments/index', [
            'plans' => $plans,
            'plan_holder_choices' => $planHolderChoices,
            'plan_statuses' => $planStatuses,
            'active_tab' => $tab,
            'selected_plan_id' => $selectedPlanId,
            'payment_history' => $history,
            'selected_payment' => $selectedPayment,
            'role_layout' => 'layouts/branch_admin',
        ]);
    }

    public function store()
    {
        $branchId = (int) session('branch_id');

        $rules = [
            'plan_id' => 'required|is_natural_no_zero',
            'amount' => 'required|decimal|greater_than[0]',
            'payment_date' => 'required|valid_date',
            'payment_method' => 'required|in_list[cash,gcash]',
            'status' => 'required|in_list[paid,pending,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/branch-admin/payment-tracking?tab=record')
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $planId = (int) $this->request->getPost('plan_id');
        if (! $this->canAccessPlan($planId, $branchId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $this->paymentService->recordPayment([
                'plan_id' => $planId,
                'amount' => (string) $this->request->getPost('amount'),
                'payment_date' => (string) $this->request->getPost('payment_date'),
                'payment_method' => (string) $this->request->getPost('payment_method'),
                'reference_number' => (string) $this->request->getPost('reference_number'),
                'remarks' => (string) $this->request->getPost('remarks'),
                'status' => (string) $this->request->getPost('status'),
                'received_by' => (int) session('user_id'),
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/branch-admin/payment-tracking?tab=record')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->to('/branch-admin/payment-tracking?tab=monitoring&plan_id=' . $planId)
            ->with('success', 'Payment recorded successfully.');
    }

    public function edit(int $id)
    {
        $branchId = (int) session('branch_id');

        if (! $this->canAccessPayment($id, $branchId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $payment = $this->paymentService->getPaymentById($id);
        if (! $payment) {
            throw PageNotFoundException::forPageNotFound();
        }

        return redirect()->to('/branch-admin/payment-tracking?tab=update&payment_id=' . $id . '&plan_id=' . (int) $payment['plan_id']);
    }

    public function update(int $id)
    {
        $branchId = (int) session('branch_id');
        if (! $this->canAccessPayment($id, $branchId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'amount' => 'required|decimal|greater_than[0]',
            'payment_date' => 'required|valid_date',
            'payment_method' => 'required|in_list[cash,gcash]',
            'status' => 'required|in_list[paid,pending,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/branch-admin/payment-tracking?tab=update&payment_id=' . $id)
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $this->paymentService->updatePayment($id, [
                'amount' => (string) $this->request->getPost('amount'),
                'payment_date' => (string) $this->request->getPost('payment_date'),
                'payment_method' => (string) $this->request->getPost('payment_method'),
                'reference_number' => (string) $this->request->getPost('reference_number'),
                'remarks' => (string) $this->request->getPost('remarks'),
                'status' => (string) $this->request->getPost('status'),
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/branch-admin/payment-tracking?tab=update&payment_id=' . $id)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $payment = $this->paymentService->getPaymentById($id);
        $planId = $payment ? (int) $payment['plan_id'] : 0;

        return redirect()->to('/branch-admin/payment-tracking?tab=monitoring' . ($planId > 0 ? '&plan_id=' . $planId : ''))
            ->with('success', 'Payment updated and plan recomputed successfully.');
    }

    private function canAccessPlan(int $planId, int $branchId): bool
    {
        if ($branchId <= 0 || $planId <= 0) {
            return false;
        }

        $plan = $this->paymentService->getPlanWithBranch($planId);

        return $plan && (int) $plan['branch_id'] === $branchId;
    }

    private function canAccessPayment(int $paymentId, int $branchId): bool
    {
        if ($paymentId <= 0 || $branchId <= 0) {
            return false;
        }

        $payment = $this->paymentService->getPaymentById($paymentId);
        if (! $payment) {
            return false;
        }

        return (int) ($payment['branch_id'] ?? 0) === $branchId;
    }
}
