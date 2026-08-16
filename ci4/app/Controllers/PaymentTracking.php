<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\PlanModel;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\MembershipService;
use App\Services\NotificationService;

class PaymentTracking extends BaseController
{
    private ?bool $paymentsHasProofImage = null;

    public function admin(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 1) {
            return redirect()->to('/unauthorized');
        }

        $rows = $this->adminPaymentRows();

        $totalCollections = 0.0;
        $completedCount = 0;
        $pendingCount = 0;
        $methodCounts = [];

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $method = strtolower((string) ($row['payment_method'] ?? 'other'));

            if ($status === 'paid') {
                $totalCollections += (float) ($row['amount'] ?? 0);
                $completedCount++;
            } elseif ($status === 'pending') {
                $pendingCount++;
            }

            $methodCounts[$method] = ($methodCounts[$method] ?? 0) + 1;
        }

        $primaryMethod = 'N/A';
        $primaryMethodPct = 0;
        if (! empty($methodCounts)) {
            arsort($methodCounts);
            $topMethod = array_key_first($methodCounts);
            $primaryMethod = strtoupper($topMethod);
            $totalMethods = array_sum($methodCounts);
            $primaryMethodPct = $totalMethods > 0 ? round(($methodCounts[$topMethod] / $totalMethods) * 100) : 0;
        }

        return view('admin/payment_monitoring/index', [
            'role_layout' => 'layouts/admin',
            'page_title' => null,
            'rows' => $rows,
            'branches' => $this->branchOptions(),
            'filters' => $this->adminFilters(),
            'supports_proof_upload' => $this->supportsProofUpload(),
            'total_collections' => $totalCollections,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'primary_method' => $primaryMethod,
            'primary_method_pct' => $primaryMethodPct,
        ]);
    }

    public function exportCsv()
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 1) {
            return redirect()->to('/unauthorized');
        }

        $rows = $this->adminPaymentRows();
        $filename = 'payment-monitoring-' . date('Ymd-His') . '.csv';

        $headers = [
            'Payment ID',
            'Plan Holder',
            'Unique Identifier',
            'Branch',
            'Months Covered',
            'Amount',
            'Payment Date',
            'Method',
            'Reference Number',
            'Official Receipt Number',
            'Status',
            'Remarks',
        ];

        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $lines[] = implode(',', [
                $this->csv((string) ($row['payment_id'] ?? '')),
                $this->csv(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))),
                $this->csv((string) ($row['unique_identifier'] ?? '')),
                $this->csv((string) ($row['branch_name'] ?? '')),
                $this->csv((string) ((int) ($row['months_covered'] ?? 1))),
                $this->csv(number_format((float) ($row['amount'] ?? 0), 2, '.', '')),
                $this->csv((string) ($row['payment_date'] ?? '')),
                $this->csv(strtoupper((string) ($row['payment_method'] ?? ''))),
                $this->csv((string) ($row['reference_number'] ?? '')),
                $this->csv((string) ($row['official_receipt_number'] ?? '')),
                $this->csv((string) ($row['status'] ?? '')),
                $this->csv((string) ($row['remarks'] ?? '')),
            ]);
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(implode("\r\n", $lines));
    }

    public function branchAdmin(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 2) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $tab = strtolower((string) $this->request->getGet('tab'));
        if (! in_array($tab, ['', 'record', 'monitoring', 'initial'], true)) {
            $tab = '';
        }

        $rows = $this->paymentRows($branchId);

        $totalCollections = 0.0;
        $completedCount = 0;
        $pendingCount = 0;
        $methodCounts = [];

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $method = strtolower((string) ($row['payment_method'] ?? 'other'));

            if ($status === 'paid') {
                $totalCollections += (float) ($row['amount'] ?? 0);
                $completedCount++;
            } elseif ($status === 'pending') {
                $pendingCount++;
            }

            $methodCounts[$method] = ($methodCounts[$method] ?? 0) + 1;
        }

        $primaryMethod = 'N/A';
        $primaryMethodPct = 0;
        if (! empty($methodCounts)) {
            arsort($methodCounts);
            $topMethod = array_key_first($methodCounts);
            $primaryMethod = strtoupper($topMethod);
            $totalMethods = array_sum($methodCounts);
            $primaryMethodPct = $totalMethods > 0 ? round(($methodCounts[$topMethod] / $totalMethods) * 100) : 0;
        }

        return view('branch_admin/payment_tracking/index', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => null,
            'plan_options' => $this->branchPlanOptions($branchId),
            'initial_plan_options' => $this->branchInitialPlanOptions($branchId),
            'rows' => $rows,
            'initial_rows' => $this->initialPaymentRows($branchId),
            'can_approve' => true,
            'selected_status' => (string) $this->request->getGet('status'),
            'active_tab' => $tab,
            'total_collections' => $totalCollections,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'primary_method' => $primaryMethod,
            'primary_method_pct' => $primaryMethodPct,
        ]);
    }

    public function staff(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 3) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $tab = strtolower((string) $this->request->getGet('tab'));
        if (! in_array($tab, ['', 'record', 'monitoring', 'initial'], true)) {
            $tab = '';
        }

        $rows = $this->paymentRows($branchId);

        $totalCollections = 0.0;
        $completedCount = 0;
        $pendingCount = 0;
        $methodCounts = [];

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            $method = strtolower((string) ($row['payment_method'] ?? 'other'));

            if ($status === 'paid') {
                $totalCollections += (float) ($row['amount'] ?? 0);
                $completedCount++;
            } elseif ($status === 'pending') {
                $pendingCount++;
            }

            $methodCounts[$method] = ($methodCounts[$method] ?? 0) + 1;
        }

        $primaryMethod = 'N/A';
        $primaryMethodPct = 0;
        if (! empty($methodCounts)) {
            arsort($methodCounts);
            $topMethod = array_key_first($methodCounts);
            $primaryMethod = strtoupper($topMethod);
            $totalMethods = array_sum($methodCounts);
            $primaryMethodPct = $totalMethods > 0 ? round(($methodCounts[$topMethod] / $totalMethods) * 100) : 0;
        }

        return view('staff/payment_management/index', [
            'role_layout' => 'layouts/staff',
            'page_title' => null,
            'plan_options' => $this->branchPlanOptions($branchId),
            'initial_plan_options' => $this->branchInitialPlanOptions($branchId),
            'rows' => $rows,
            'initial_rows' => $this->initialPaymentRows($branchId),
            'can_approve' => false,
            'selected_status' => (string) $this->request->getGet('status'),
            'active_tab' => $tab,
            'total_collections' => $totalCollections,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'primary_method' => $primaryMethod,
            'primary_method_pct' => $primaryMethodPct,
        ]);
    }

    public function recordCash()
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [2, 3], true)) {
            return redirect()->to('/unauthorized');
        }

        $rules = [
            'plan_id' => 'required|is_natural_no_zero',
            'amount' => 'required|decimal',
            'months_covered' => 'required|is_natural_no_zero',
            'payment_date' => 'required|valid_date[Y-m-d]',
            'payment_method' => 'required|in_list[cash]',
            'official_receipt_number' => $roleId === 2 ? 'required|max_length[100]' : 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $planId = (int) $this->request->getPost('plan_id');
        $amount = (float) $this->request->getPost('amount');
        $monthsCovered = max(1, (int) $this->request->getPost('months_covered'));
        $branchId = (int) session('branch_id');

        $plan = (new PlanModel())->find($planId);
        if (! $plan) {
            return redirect()->back()->withInput()->with('error', 'Plan not found.');
        }

        $monthlyFee = (float) ($plan['monthly_fee'] ?? 0);
        $expectedAmount = $this->calculateAmount($monthlyFee, $monthsCovered);
        if ($expectedAmount <= 0 || abs($expectedAmount - $amount) > 0.01) {
            return redirect()->back()->withInput()->with('error', 'Amount must match the monthly fee multiplied by months covered, less any advance discount.');
        }

        if ($amount > (float) ($plan['remaining_balance'] ?? 0)) {
            return redirect()->back()->withInput()->with('error', 'Payment exceeds remaining balance.');
        }

        $holder = db_connect()->table('plan_holders')
            ->select('branch_id')
            ->where('plan_holder_id', (int) $plan['plan_holder_id'])
            ->get()
            ->getRowArray();

        if (! $holder || (int) $holder['branch_id'] !== $branchId) {
            return redirect()->back()->withInput()->with('error', 'Selected plan does not belong to your branch.');
        }

        // NEW STATUS TERMINOLOGY: verified (was 'paid'), awaiting_verification (was 'pending')
        $status = $roleId === 2 ? 'paid' : 'pending';
        
        $paymentData = [
            'plan_id' => $planId,
            'amount' => $amount,
            'months_covered' => $monthsCovered,
            'payment_date' => (string) $this->request->getPost('payment_date'),
            'payment_method' => 'cash',
            'reference_number' => $this->request->getPost('official_receipt_number') ?: null,
            'received_by' => (int) session('user_id'),
            'branch_id' => $branchId,
            'status' => $status,
            'official_receipt_number' => $this->request->getPost('official_receipt_number') ?: null,
            'remarks' => $status === 'paid' ? 'Recorded at branch counter' : 'Recorded by staff, pending approval',
            'verified_by' => $status === 'paid' ? (int) session('user_id') : null,
            'verified_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
        ];
        
        // Filter to only include columns that exist in the payments table
        $paymentData = $this->filterPaymentData($paymentData);
        
        $paymentId = (int) (new PaymentModel())->insert($paymentData, true);

        if ($paymentId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Unable to record payment.');
        }

        $autoApproved = false;
        if ($status === 'paid') {
            if ($this->isInitialPayment((int) $paymentId, (int) $plan['plan_id'], (int) $plan['plan_holder_id'])) {
                $autoApproved = $this->autoApprovePlanHolderFromInitialPayment($plan, $monthsCovered);
            } else {
                (new MembershipService())->applyMembershipCoverage((int) $plan['plan_id'], $monthsCovered);
            }
        }

        $notificationService = new NotificationService();
        $activityLogService = new ActivityLogService();

        $userId = (int) (db_connect()->table('plan_holders')->select('user_id')->where('plan_holder_id', (int) $plan['plan_holder_id'])->get()->getRowArray()['user_id'] ?? 0);
        if ($userId > 0) {
            if ($status === 'paid') {
                $notificationService->notify($userId, $this->paymentApprovedMessage($monthsCovered, $plan), 'payment_approved');
            } else {
                $notificationService->notify($userId, 'Your cash payment was recorded and is pending branch verification.', 'payment_pending');
            }
        }

        $activityLogService->log(
            (int) session('user_id'),
            'created',
            'payment',
            $paymentId,
            'Recorded cash payment for plan #' . (int) $plan['plan_id'],
            null,
            [
                'plan_id' => (int) $plan['plan_id'],
                'amount' => $amount,
                'payment_method' => 'cash',
                'status' => 'paid',
            ]
        );

        $successMessage = $autoApproved
            ? 'Initial payment approved and registration activated.'
            : 'Cash payment recorded and confirmed.';

        return redirect()->back()->with('success', $successMessage);
    }

    public function approveGcash(int $paymentId)
    {
        return $this->reviewGcash($paymentId, 'paid');
    }

    public function rejectGcash(int $paymentId)
    {
        return $this->reviewGcash($paymentId, 'cancelled');
    }

    /**
     * Serve the plan-holder's uploaded government ID to branch admins/staff.
     *
     * Scope-checked: only the branch that owns the payment may view it, and the
     * file must live under the secure verification upload dir. The document is
     * never served from the public webroot.
     */
    public function idDocument(int $paymentId)
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [2, 3], true)) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');

        $payment = db_connect()->table('payments pay')
            ->select('pay.payment_id, pay.branch_id, ph.id_document_path, ph.id_type')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('pay.payment_id', $paymentId)
            ->get()
            ->getRowArray();

        if (! $payment) {
            return redirect()->back()->with('error', 'Payment record not found.');
        }

        if ((int) ($payment['branch_id'] ?? 0) !== $branchId) {
            return redirect()->back()->with('error', 'This document is outside your branch scope.');
        }

        $path = (string) ($payment['id_document_path'] ?? '');
        if ($path === '') {
            return redirect()->back()->with('error', 'No government ID document is attached for this registration.');
        }

        // Defense in depth: only serve files under the verification upload dir.
        $baseDir = realpath(WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'plan_registration_verification');
        $realPath = realpath($path);
        if ($baseDir === false || $realPath === false || strpos($realPath, $baseDir) !== 0 || ! is_file($realPath)) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        $mime = (string) (function_exists('mime_content_type') ? mime_content_type($realPath) : false);
        $mime = $mime === '' ? 'application/octet-stream' : $mime;

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="government_id_' . (int) $paymentId . '"')
            ->setBody((string) file_get_contents($realPath));
    }

    private function reviewGcash(int $paymentId, string $targetStatus)
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 2) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $paymentModel = new PaymentModel();
        $payment = $paymentModel->find($paymentId);

        if (! $payment) {
            return redirect()->back()->with('error', 'Payment record not found.');
        }

        $currentStatus = strtolower((string) ($payment['status'] ?? ''));
        if (! in_array($currentStatus, ['pending', 'awaiting_verification'], true)) {
            return redirect()->back()->with('error', 'Only pending payments can be reviewed.');
        }

        if ((int) ($payment['branch_id'] ?? 0) !== $branchId) {
            return redirect()->back()->with('error', 'This payment is outside your branch scope.');
        }

        $method = strtolower((string) ($payment['payment_method'] ?? ''));
        
        // PHASE REQUIREMENT: Duplicate GCash reference validation
        if (in_array($targetStatus, ['paid', 'verified'], true) && $method === 'gcash') {
            $refNumber = trim((string) ($payment['reference_number'] ?? ''));
            if ($refNumber === '') {
                return redirect()->back()->with('error', 'Reference number is required before approval.');
            }
            
            // Check for duplicate GCash references in the same plan
            $existingRef = (new PaymentModel())
                ->where('plan_id', (int) $payment['plan_id'])
                ->where('payment_id !=', $paymentId)
                ->where('payment_method', 'gcash')
                ->where('reference_number', $refNumber)
                ->where("(status = 'paid' OR status = 'verified')", null, false)
                ->first();
            
            if ($existingRef) {
                return redirect()->back()->with('error', 'This GCash reference number has already been verified for this plan. Duplicate payment detected.');
            }
        }

        if (in_array($targetStatus, ['paid', 'verified'], true) && $method === 'cash' && trim((string) ($payment['official_receipt_number'] ?? '')) === '') {
            return redirect()->back()->with('error', 'Official receipt number is required before approval.');
        }

        $rejectionReason = trim((string) $this->request->getPost('rejection_reason'));

        if ($targetStatus === 'cancelled' && $rejectionReason === '') {
            return redirect()->back()->with('error', 'Please provide a reason for the rejection.');
        }

        $remarks = $targetStatus === 'verified'
            ? 'GCash verified by branch admin'
            : ('GCash rejected by branch admin' . ($rejectionReason !== '' ? (': ' . $rejectionReason) : ''));

        $paymentModel->update($paymentId, [
            'status' => $targetStatus,
            'received_by' => (int) session('user_id'),
            'verified_by' => (int) session('user_id'),
            'verified_at' => date('Y-m-d H:i:s'),
            'remarks' => $remarks,
        ]);

        $plan = (new PlanModel())->find((int) $payment['plan_id']);
        $autoApproved = false;
        if ($plan && in_array($targetStatus, ['paid', 'verified'], true)) {
            $monthsCovered = max(1, (int) ($payment['months_covered'] ?? 1));
            if ($this->isInitialPayment((int) $payment['payment_id'], (int) $plan['plan_id'], (int) ($plan['plan_holder_id'] ?? 0))) {
                $autoApproved = $this->autoApprovePlanHolderFromInitialPayment($plan, $monthsCovered);
            } else {
                (new MembershipService())->applyMembershipCoverage((int) $plan['plan_id'], $monthsCovered);
            }
        }

        $holder = db_connect()->table('plans p')
            ->select('ph.user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('p.plan_id', (int) $payment['plan_id'])
            ->get()
            ->getRowArray();

        $userId = (int) ($holder['user_id'] ?? 0);
        $notificationService = new NotificationService();
        $activityLogService = new ActivityLogService();
        if ($userId > 0) {
            if ($targetStatus === 'paid') {
                $notificationService->notify($userId, $this->paymentApprovedMessage(max(1, (int) ($payment['months_covered'] ?? 1)), $plan), 'payment_approved');
            } else {
                $notificationService->notify($userId, 'Your payment was rejected. Please verify and resubmit.', 'payment_rejected');
            }
        }

        $activityLogService->log(
            (int) session('user_id'),
            $targetStatus === 'paid' ? 'approved' : 'rejected',
            'payment',
            $paymentId,
            $targetStatus === 'paid' ? 'Approved GCash payment' : 'Rejected GCash payment',
            ['status' => 'pending'],
            ['status' => $targetStatus]
        );

        if ($targetStatus !== 'paid') {
            return redirect()->back()->with('success', 'Payment rejected successfully.');
        }

        $successMessage = $autoApproved
            ? 'Initial payment approved and registration activated.'
            : 'Payment approved successfully.';

        return redirect()->back()->with('success', $successMessage);
    }

    private function calculateAmount(float $monthlyFee, int $monthsCovered): float
    {
        // Advance payments apply the tiered discount (see PaymentService::ADVANCE_DISCOUNTS).
        return \App\Services\PaymentService::advanceTotal($monthlyFee, $monthsCovered);
    }

    private function paymentApprovedMessage(int $monthsCovered, ?array $plan): string
    {
        $suffix = $monthsCovered === 1 ? 'month' : 'months';
        $coverageUntil = $plan ? (string) ($plan['payment_coverage_until'] ?? '') : '';

        if ($coverageUntil !== '') {
            return 'Your advance payment covering ' . $monthsCovered . ' ' . $suffix . ' has been approved. Your membership remains active until ' . date('F d, Y', strtotime($coverageUntil)) . '.';
        }

        return 'Your advance payment covering ' . $monthsCovered . ' ' . $suffix . ' has been approved.';
    }

    private function autoApprovePlanHolderFromInitialPayment(array $plan, int $monthsCovered = 1): bool
    {
        $planHolderId = (int) ($plan['plan_holder_id'] ?? 0);
        
        if ($planHolderId <= 0) {
            return false;
        }

        $branchId = (int) session('branch_id');
        $planHolderModel = new PlanHolderModel();
        $userModel = new UserModel();
        $planModel = new PlanModel();

        $holder = $planHolderModel->find($planHolderId);
        
        if (! $holder || (int) ($holder['branch_id'] ?? 0) !== $branchId) {
            return false;
        }

        if (strtolower((string) ($holder['status'] ?? '')) !== 'inactive') {
            return false;
        }


        $db = db_connect();
        $db->transBegin();

        try {
            $packageData = $this->resolvePackageAndVersion();
            
            $existingPlan = $planModel
                ->where('plan_holder_id', $planHolderId)
                ->orderBy('plan_id', 'DESC')
                ->first();


            if ($existingPlan) {
                $today = date('Y-m-d');
                $coverageUntil = date('Y-m-d', strtotime('+' . max(1, $monthsCovered) . ' months', strtotime($today)));
                // CORRECTED: next_due_date = payment_coverage_until + 1 day (not +1 month)
                $nextDue = date('Y-m-d', strtotime('+1 day', strtotime($coverageUntil)));

                $updateData = [
                    'package_id' => $packageData['package_id'],
                    'monthly_fee' => MembershipService::MONTHLY_FEE,
                    'start_date' => $today,
                    'status' => 'active',
                    'months_paid' => max(1, $monthsCovered),
                ];
                
                // Only include columns if they exist in the plans table
                $planFields = $db->getFieldNames('plans');
                if (in_array('version_id', $planFields, true)) {
                    $updateData['version_id'] = $packageData['version_id'];
                }
                if (in_array('next_due_date', $planFields, true)) {
                    $updateData['next_due_date'] = $nextDue;
                }
                if (in_array('payment_coverage_until', $planFields, true)) {
                    $updateData['payment_coverage_until'] = $coverageUntil;
                }
                if (in_array('overdue_months', $planFields, true)) {
                    $updateData['overdue_months'] = 0;
                }
                if (in_array('membership_state', $planFields, true)) {
                    $updateData['membership_state'] = 'active';
                }
                if (in_array('legacy_remaining_balance', $planFields, true)) {
                    $updateData['legacy_remaining_balance'] = 0;
                }

                $planModel->update((int) $existingPlan['plan_id'], $updateData);
            } else {
                $today = date('Y-m-d');
                $coverageUntil = date('Y-m-d', strtotime('+' . max(1, $monthsCovered) . ' months', strtotime($today)));
                // CORRECTED: next_due_date = payment_coverage_until + 1 day (not +1 month)
                $nextDue = date('Y-m-d', strtotime('+1 day', strtotime($coverageUntil)));

                $insertData = [
                    'plan_holder_id' => $planHolderId,
                    'package_id' => $packageData['package_id'],
                    'monthly_fee' => MembershipService::MONTHLY_FEE,
                    'start_date' => $today,
                    'status' => 'active',
                    'months_paid' => max(1, $monthsCovered),
                ];
                
                // Add optional fields only if they exist
                $planFields = $db->getFieldNames('plans');
                if (in_array('passbook_fee', $planFields, true)) {
                    $insertData['passbook_fee'] = 50;
                }
                if (in_array('version_id', $planFields, true)) {
                    $insertData['version_id'] = $packageData['version_id'];
                }
                if (in_array('next_due_date', $planFields, true)) {
                    $insertData['next_due_date'] = $nextDue;
                }
                if (in_array('payment_coverage_until', $planFields, true)) {
                    $insertData['payment_coverage_until'] = $coverageUntil;
                }
                if (in_array('overdue_months', $planFields, true)) {
                    $insertData['overdue_months'] = 0;
                }
                if (in_array('membership_state', $planFields, true)) {
                    $insertData['membership_state'] = 'active';
                }
                if (in_array('legacy_remaining_balance', $planFields, true)) {
                    $insertData['legacy_remaining_balance'] = 0;
                }

                $planId = (int) $planModel->insert($insertData, true);


                if ($planId <= 0) {
                    throw new \RuntimeException('Unable to create default plan.');
                }
            }

            // Enforce one active plan
            try {
                (new MembershipService())->enforceOneActivePlan($planHolderId);
            } catch (\Throwable $e) {
                // Don't fail the entire transaction for this
            }

            $planHolderModel->update($planHolderId, [
                'status' => 'active',
            ]);

            $userModel->update((int) ($holder['user_id'] ?? 0), [
                'is_plan_holder' => 1,
                'account_status' => 'verified',
                'branch_id' => $branchId,
            ]);

            // Try to send notification, but don't fail if it errors
            try {
                (new NotificationService())->notify((int) ($holder['user_id'] ?? 0), 'Your registration has been approved. Your plan is now active.', 'registration_pending');
            } catch (\Throwable $e) {
            }
            
            // Try to log activity, but don't fail if it errors
            try {
                (new ActivityLogService())->log(
                    (int) session('user_id'),
                    'approved',
                    'plan_holder',
                    $planHolderId,
                    'Auto-approved plan holder after initial payment verification',
                    ['status' => 'inactive'],
                    ['status' => 'active']
                );
            } catch (\Throwable $e) {
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Unable to auto-approve plan holder.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return false;
        }

        return true;
    }

    private function resolvePackageAndVersion(): array
    {
        $db = db_connect();

        $package = $db->table('packages')
            ->select('package_id')
            ->where('package_id', MembershipService::DEFAULT_PACKAGE_ID)
            ->get()
            ->getRowArray();

        if (! $package) {
            $package = $db->table('packages')
                ->select('package_id')
                ->orderBy('package_id', 'ASC')
                ->get()
                ->getRowArray();
        }

        if (! $package) {
            throw new \RuntimeException('No package is configured yet.');
        }

        $packageId = (int) $package['package_id'];
        $version = $db->table('package_versions')
            ->select('version_id')
            ->where('package_id', $packageId)
            ->where('status', 'active')
            ->orderBy('version_id', 'DESC')
            ->get()
            ->getRowArray();

        if (! $version) {
            $version = $db->table('package_versions')
                ->select('version_id')
                ->where('package_id', $packageId)
                ->orderBy('version_id', 'DESC')
                ->get()
                ->getRowArray();
        }

        if (! $version) {
            $db->table('package_versions')->insert([
                'package_id' => $packageId,
                'price' => MembershipService::MONTHLY_FEE,
                'effective_date' => date('Y-m-d'),
                'status' => 'active',
            ]);

            $versionId = (int) $db->insertID();
            if ($versionId <= 0) {
                throw new \RuntimeException('No package version is configured yet.');
            }

            return [
                'package_id' => $packageId,
                'version_id' => $versionId,
            ];
        }

        return [
            'package_id' => $packageId,
            'version_id' => (int) $version['version_id'],
        ];
    }

    private function branchPlanOptions(int $branchId): array
    {
        $plans = (new MembershipService())->getBranchPlans($branchId);

        return array_values(array_filter($plans, static function (array $plan): bool {
            return (string) ($plan['plan_status'] ?? '') === 'active';
        }));
    }

    private function branchInitialPlanOptions(int $branchId): array
    {
        $plans = (new MembershipService())->getBranchPlans($branchId);

        return array_values(array_filter($plans, static function (array $plan): bool {
            return (string) ($plan['plan_status'] ?? '') === 'inactive';
        }));
    }

    private function paymentRows(int $branchId): array
    {
        $status = strtolower(trim((string) $this->request->getGet('status')));
        
        $sql = "SELECT payments.*, users.first_name, users.last_name, plan_holders.unique_identifier, plan_holders.plan_holder_id
            FROM payments
            INNER JOIN plans ON plans.plan_id = payments.plan_id
            INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
            INNER JOIN users ON users.user_id = plan_holders.user_id
            WHERE payments.branch_id = ?
            ORDER BY payments.payment_id DESC";
        
        $db = db_connect();
        $query = $db->query($sql, [$branchId]);
        $result = $query->getResultArray();
        
        if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            return $this->ensurePaymentColumns($result);
        }
        
        // Filter by status after fetching
        $filtered = array_filter($result, function (array $row) use ($status): bool {
            return $row['status'] === $status;
        });
        
        return $this->ensurePaymentColumns($filtered);
    }

    private function initialPaymentRows(int $branchId): array
    {
        $sql = "SELECT payments.*, users.first_name, users.last_name, plan_holders.unique_identifier, plan_holders.plan_holder_id,
                    plan_holders.coordinator, plan_holders.coordinator_user_id,
                    plan_holders.id_type, plan_holders.id_verification_status, plan_holders.id_document_path,
                    plans.monthly_fee, plans.status AS plan_status,
                    cu.first_name AS coordinator_first_name, cu.middle_name AS coordinator_middle_name, cu.last_name AS coordinator_last_name
            FROM payments
            INNER JOIN plans ON plans.plan_id = payments.plan_id
            INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
            INNER JOIN users ON users.user_id = plan_holders.user_id
            LEFT JOIN users cu ON cu.user_id = plan_holders.coordinator_user_id
            WHERE payments.branch_id = ?
            GROUP BY payments.payment_id
            ORDER BY payments.payment_id DESC";

        $db = db_connect();
        $query = $db->query($sql, [$branchId]);
        $rows = $query->getResultArray();

        $filtered = array_filter($rows, function (array $row): bool {
            return $this->isInitialPayment(
                (int) ($row['payment_id'] ?? 0),
                (int) ($row['plan_id'] ?? 0),
                (int) ($row['plan_holder_id'] ?? 0)
            );
        });

        // Re-index array after filtering
        $result = array_values($filtered);

        return $this->ensurePaymentColumns($result);
    }

    private function adminPaymentRows(): array
    {
        $filters = $this->adminFilters();

        $sql = "SELECT payments.*, users.first_name, users.last_name, plan_holders.unique_identifier, branches.branch_name
            FROM payments
            INNER JOIN plans ON plans.plan_id = payments.plan_id
            INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
            INNER JOIN users ON users.user_id = plan_holders.user_id
            LEFT JOIN branches ON branches.branch_id = payments.branch_id
            WHERE 1=1";
        
        $params = [];
        
        if ($filters['status'] !== '') {
            $sql .= " AND payments.status = ?";
            $params[] = $filters['status'];
        }

        if ($filters['payment_method'] !== '') {
            $sql .= " AND payments.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        if ($filters['branch_id'] > 0) {
            $sql .= " AND payments.branch_id = ?";
            $params[] = $filters['branch_id'];
        }

        if ($filters['date_from'] !== '') {
            $sql .= " AND payments.payment_date >= ?";
            $params[] = $filters['date_from'];
        }

        if ($filters['date_to'] !== '') {
            $sql .= " AND payments.payment_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY payments.payment_id DESC";
        
        $db = db_connect();
        $query = $db->query($sql, $params);
        $rows = $query->getResultArray();
        
        return $this->ensurePaymentColumns($rows);
    }

    private function branchOptions(): array
    {
        return db_connect()->table('branches')
            ->select('branch_id, branch_name')
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array{status:string,payment_method:string,branch_id:int,date_from:string,date_to:string}
     */
    private function adminFilters(): array
    {
        $status = strtolower(trim((string) $this->request->getGet('status')));
        if (! in_array($status, ['', 'pending', 'paid', 'cancelled'], true)) {
            $status = '';
        }

        $method = strtolower(trim((string) $this->request->getGet('payment_method')));
        if (! in_array($method, ['', 'cash', 'gcash'], true)) {
            $method = '';
        }

        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo = trim((string) ($this->request->getGet('date_to') ?? ''));

        return [
            'status' => $status,
            'payment_method' => $method,
            'branch_id' => max(0, $branchId),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function csv(string $value): string
    {
        $escaped = str_replace('"', '""', $value);

        return '"' . $escaped . '"';
    }

    private function supportsProofUpload(): bool
    {
        if ($this->paymentsHasProofImage !== null) {
            return $this->paymentsHasProofImage;
        }

        $this->paymentsHasProofImage = db_connect()->fieldExists('proof_image', 'payments');

        return $this->paymentsHasProofImage;
    }

    private function filterPaymentData(array $data): array
    {
        $db = db_connect();
        $fields = $db->getFieldNames('payments');
        if (empty($fields)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($fields));
    }

    private function ensurePaymentColumns(array $rows): array
    {
        // Ensure all expected columns exist with default values
        $defaults = [
            'months_covered' => 1,
            'official_receipt_number' => null,
            'proof_image' => null,
            'remarks' => null,
        ];
        
        return array_map(function (array $row) use ($defaults): array {
            foreach ($defaults as $key => $default) {
                if (!array_key_exists($key, $row)) {
                    $row[$key] = $default;
                }
            }
            return $row;
        }, $rows);
    }

    private function isInitialPayment(int $paymentId, int $planId, int $planHolderId): bool
    {
        if ($paymentId <= 0 || $planId <= 0 || $planHolderId <= 0) {
            return false;
        }

        $holder = db_connect()->table('plan_holders')
            ->select('status')
            ->where('plan_holder_id', $planHolderId)
            ->get()
            ->getRowArray();

        $holderStatus = strtolower((string) ($holder['status'] ?? ''));
        if ($holderStatus === 'inactive') {
            return true;
        }

        $earliestPayment = db_connect()->table('payments')
            ->select('payment_id, plan_id')
            ->where('plan_id', $planId)
            ->orderBy('payment_id', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (! $earliestPayment) {
            return false;
        }

        return (int) ($earliestPayment['payment_id'] ?? 0) === $paymentId;
    }

    public function staffRecordPaymentForm(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 3) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $db = db_connect();

        $clients = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.status AS plan_holder_status, u.first_name, u.last_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('ph.branch_id', $branchId)
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        $approvalQueue = $db->table('payments p')
            ->select('p.payment_id, p.amount, p.payment_method, p.status, p.payment_date, u.first_name, u.last_name, ph.unique_identifier')
            ->join('plans pl', 'pl.plan_id = p.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->where('p.branch_id', $branchId)
            ->orderBy('p.payment_id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $pendingCount = 0;
        foreach ($approvalQueue as $record) {
            if (strtolower((string) ($record['status'] ?? '')) === 'pending') {
                $pendingCount++;
            }
        }

        $program = \App\Services\MembershipService::getProgramInfo();

        return view('staff/record_payment', [
            'role_layout' => 'layouts/staff',
            'page_title' => null,
            'clients' => $clients,
            'approval_queue' => $approvalQueue,
            'monthly_fee' => (float) ($program['monthly_fee'] ?? 240.0),
            'pending_count' => $pendingCount,
        ]);
    }

    public function staffRecordPaymentSave()
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 3) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $clientName = trim((string) $this->request->getPost('client_name'));
        $monthsCovered = max(1, (int) $this->request->getPost('months_covered'));
        $receiptNumber = trim((string) $this->request->getPost('receipt_number'));
        $planHolderId = (int) $this->request->getPost('plan_holder_id');
        $paymentType = strtolower(trim((string) $this->request->getPost('payment_type')));
        $program = \App\Services\MembershipService::getProgramInfo();
        $monthlyFee = (float) ($program['monthly_fee'] ?? 240.0);

        if (empty($clientName) || empty($receiptNumber)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Client name and receipt number are required.');
        }

        if ($planHolderId <= 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Please select a valid client from the dropdown.');
        }

        // Check if receipt already exists in cash_payment_records
        $existing = db_connect()->table('cash_payment_records')
            ->where('receipt_number', $receiptNumber)
            ->get()
            ->getRowArray();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This receipt number already exists. Receipt: ' . esc($receiptNumber));
        }

        // Also check if receipt already exists in payments table (for sync)
        $existingPayment = db_connect()->table('payments')
            ->where('official_receipt_number', $receiptNumber)
            ->get()
            ->getRowArray();

        if ($existingPayment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This receipt number already exists in payment records. Receipt: ' . esc($receiptNumber));
        }

        // Find the plan for this plan holder
        $plan = db_connect()->table('plans')
            ->select('plan_id, plan_holder_id, monthly_fee, status')
            ->where('plan_holder_id', $planHolderId)
            ->orderBy('plan_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$plan) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No active plan found for this client. Please ensure the client has a registered plan.');
        }

        // Record the cash payment (advance payments apply the tiered discount).
        $db = db_connect();
        $amount = \App\Services\PaymentService::advanceTotal($monthlyFee, $monthsCovered);

        // 1. Save to cash_payment_records table (legacy)
        $paymentData = [
            'branch_id' => $branchId,
            'client_name' => $clientName,
            'months_covered' => $monthsCovered,
            'amount' => $amount,
            'receipt_number' => $receiptNumber,
            'recorded_by' => (int) session('user_id'),
            'recorded_date' => date('Y-m-d'),
            'verified' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        log_message('debug', '[StaffCashPaymentSave] Inserting to cash_payment_records: branch=' . $branchId . ' receipt=' . $receiptNumber);

        $inserted = $db->table('cash_payment_records')->insert($paymentData);

        if (!$inserted) {
            $error = $db->error();
            log_message('error', '[StaffCashPaymentSave] cash_payment_records insert failed: ' . json_encode($error));
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record payment. Error: ' . (isset($error['message']) ? $error['message'] : 'Unknown error'));
        }

        // 2. Also save to payments table for client verification sync
        // For staff (role 3), status is 'pending' (needs branch admin approval)
        $paymentDataPayments = [
            'plan_id' => (int) $plan['plan_id'],
            'amount' => $amount,
            'months_covered' => $monthsCovered,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'cash',
            'reference_number' => $receiptNumber,
            'official_receipt_number' => $receiptNumber,
            'received_by' => (int) session('user_id'),
            'branch_id' => $branchId,
            'status' => 'pending',  // Staff records are pending approval
            'remarks' => 'Recorded by staff, pending branch verification',
            'payment_type' => $paymentType === 'initial' ? 'initial_registration' : 'monthly_contribution',
        ];

        // Filter to only include columns that exist in the payments table
        $paymentModel = new \App\Models\PaymentModel();
        $paymentFields = $db->getFieldNames('payments');
        $paymentDataPayments = array_intersect_key($paymentDataPayments, array_flip($paymentFields));

        log_message('debug', '[StaffCashPaymentSave] Inserting to payments table: plan_id=' . $plan['plan_id'] . ' receipt=' . $receiptNumber);

        $paymentId = (int) $paymentModel->insert($paymentDataPayments, true);

        if ($paymentId <= 0) {
            // Rollback cash_payment_records if payments insert fails
            $db->table('cash_payment_records')->where('receipt_number', $receiptNumber)->delete();
            log_message('error', '[StaffCashPaymentSave] payments table insert failed, rolled back cash_payment_records');
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record payment in payment system.');
        }

        log_message('debug', '[StaffCashPaymentSave] Success: receipt=' . $receiptNumber . ' for ' . $clientName . ' (payment_id=' . $paymentId . ')');

        // Send notification to client
        $holder = db_connect()->table('plan_holders')
            ->select('user_id')
            ->where('plan_holder_id', $planHolderId)
            ->get()
            ->getRowArray();

        if (isset($holder['user_id']) && $holder['user_id'] > 0) {
            (new \App\Services\NotificationService())->notify(
                (int) $holder['user_id'],
                'Cash payment of PHP ' . number_format($amount, 2) . ' recorded for ' . $monthsCovered . ' month(s). Receipt: ' . esc($receiptNumber) . '. Pending branch verification.',
                'payment_pending'
            );
        }

        // Log activity
        (new \App\Services\ActivityLogService())->log(
            (int) session('user_id'),
            'created',
            'payment',
            $paymentId,
            'Recorded cash payment for plan #' . (int) $plan['plan_id'],
            null,
            [
                'plan_id' => (int) $plan['plan_id'],
                'amount' => $amount,
                'payment_method' => 'cash',
                'status' => 'pending',
                'receipt_number' => $receiptNumber,
            ]
        );

        $label = $paymentType === 'initial' ? 'Initial payment' : 'Payment';

        return redirect()->to('/staff/record-payment')
            ->with('success', ucfirst($label) . ' recorded. Receipt: ' . esc($receiptNumber) . ' for ' . esc($clientName) . ' (pending branch verification)');
    }
}
