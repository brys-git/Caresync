<?php

namespace App\Controllers\Staff;

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
        $this->ensureStaffAccess();

        $branchId = (int) session('branch_id');
        $branchIssue = null;

        if ($branchId <= 0) {
            $payments = [];
            $plans = [];
            $branchIssue = 'No branch is assigned to your staff account. Please contact the branch admin.';
        } else {
            $payments = $this->paymentService->getBranchMonitoringPayments($branchId);
            $plans = $this->paymentService->getBranchPlanOptions($branchId);
        }

        return view('staff/payments/index', [
            'payments' => $payments,
            'plans' => $plans,
            'selected_payment' => null,
            'active_tab' => 'monitoring',
            'branch_issue' => $branchIssue,
            'role_layout' => 'layouts/staff',
        ]);
    }

    public function create(): string
    {
        $this->ensureStaffAccess();

        $branchId = (int) session('branch_id');
        $branchIssue = null;

        if ($branchId <= 0) {
            $payments = [];
            $plans = [];
            $branchIssue = 'No branch is assigned to your staff account. Please contact the branch admin.';
        } else {
            $payments = $this->paymentService->getBranchMonitoringPayments($branchId);
            $plans = $this->paymentService->getBranchPlanOptions($branchId);
        }

        return view('staff/payments/index', [
            'payments' => $payments,
            'plans' => $plans,
            'selected_payment' => null,
            'active_tab' => 'record',
            'branch_issue' => $branchIssue,
            'role_layout' => 'layouts/staff',
        ]);
    }

    public function store()
    {
        $this->ensureStaffAccess();

        $branchId = (int) session('branch_id');

        if ($branchId <= 0) {
            return redirect()->to('/staff/payments/record')->with('error', 'Branch information is missing.');
        }

        $rules = [
            'plan_id' => 'required|is_natural_no_zero',
            'amount' => 'required|decimal|greater_than[0]',
            'payment_date' => 'required|valid_date',
            'payment_method' => 'required|in_list[cash,gcash]',
            'reference_number' => 'permit_empty|max_length[100]',
            'remarks' => 'permit_empty|max_length[500]',
            'status' => 'permit_empty|in_list[paid,pending,cancelled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/staff/payments/record')
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
                'status' => (string) ($this->request->getPost('status') ?: 'paid'),
                'received_by' => (int) session('user_id'),
                'branch_id' => $branchId,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/staff/payments/record')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->to('/staff/payments')->with('success', 'Payment recorded successfully.');
    }

    public function edit(int $id): string
    {
        $this->ensureStaffAccess();

        $branchId = (int) session('branch_id');
        if (! $this->canAccessPayment($id, $branchId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $payment = $this->paymentService->getPaymentById($id);
        if (! $payment) {
            throw PageNotFoundException::forPageNotFound();
        }

        $payments = $branchId > 0 ? $this->paymentService->getBranchMonitoringPayments($branchId) : [];
        $plans = $branchId > 0 ? $this->paymentService->getBranchPlanOptions($branchId) : [];

        return view('staff/payments/index', [
            'payments' => $payments,
            'plans' => $plans,
            'selected_payment' => $payment,
            'active_tab' => 'update',
            'branch_issue' => null,
            'role_layout' => 'layouts/staff',
        ]);
    }

    public function update(int $id)
    {
        $this->ensureStaffAccess();

        $branchId = (int) session('branch_id');
        if (! $this->canAccessPayment($id, $branchId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $payment = $this->paymentService->getPaymentById($id);
        if (! $payment) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'amount' => 'required|decimal|greater_than[0]',
            'payment_method' => 'required|in_list[cash,gcash]',
            'remarks' => 'permit_empty|max_length[500]',
            'status' => 'required|in_list[paid,pending,cancelled]',
            'reference_number' => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/staff/payments/edit/' . $id)
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        try {
            $this->paymentService->updatePayment($id, [
                'amount' => (string) $this->request->getPost('amount'),
                'payment_date' => (string) ($payment['payment_date'] ?? date('Y-m-d')),
                'payment_method' => (string) $this->request->getPost('payment_method'),
                'reference_number' => (string) $this->request->getPost('reference_number'),
                'remarks' => (string) $this->request->getPost('remarks'),
                'status' => (string) $this->request->getPost('status'),
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/staff/payments/edit/' . $id)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->to('/staff/payments')->with('success', 'Payment updated successfully.');
    }

    private function ensureStaffAccess(): void
    {
        $roleId = (int) session('role_id');
        $roleName = (string) session('role');

        if ($roleId !== 3 && strtolower($roleName) !== 'staff') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
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
