<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\PlanModel;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use App\Services\ActivityLogService;
use App\Services\CollectorAssignmentService;
use App\Services\CycleService;
use App\Services\MembershipService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PlanLedgerService;

class PaymentTracking extends BaseController
{
    private ?bool $paymentsHasProofImage = null;

    public function admin(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 1) {
            return redirect()->to('/unauthorized');
        }

        return view('admin/payment_monitoring/index', [
            'role_layout' => 'layouts/admin',
            'page_title' => 'Payment Monitoring',
            'page_sub' => 'System-wide transaction visibility across all branches.',
            'rows' => $this->adminPaymentRows(),
            'branches' => $this->branchOptions(),
            'filters' => $this->adminFilters(),
            'supports_proof_upload' => $this->supportsProofUpload(),
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
        $paymentService = new PaymentService();

        $headers = [
            'Plan Holder',
            'Unique Identifier',
            'Branch',
            'Coverage Period',
            'Amount',
            'Payment Date',
            'Method',
            'Reference Number',
            'Official Receipt Number',
            'Staff Account',
        ];

        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $coverageStart = (string) ($row['coverage_start'] ?? $row['payment_date'] ?? '');
            $coverageLabel = $coverageStart !== ''
                ? $paymentService->describeCoverage($coverageStart, (int) ($row['months_covered'] ?? 1))
                : (int) ($row['months_covered'] ?? 1) . ' month(s)';
            $staffName = trim((string) ($row['staff_first_name'] ?? '') . ' ' . (string) ($row['staff_last_name'] ?? ''));

            $lines[] = implode(',', [
                $this->csv(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))),
                $this->csv((string) ($row['unique_identifier'] ?? '')),
                $this->csv((string) ($row['branch_name'] ?? '')),
                $this->csv($coverageLabel),
                $this->csv(number_format((float) ($row['amount'] ?? 0), 2, '.', '')),
                $this->csv((string) ($row['payment_date'] ?? '')),
                $this->csv(strtoupper((string) ($row['payment_method'] ?? ''))),
                $this->csv((string) ($row['reference_number'] ?? '')),
                $this->csv((string) ($row['official_receipt_number'] ?? '')),
                $this->csv($staffName),
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

        return view('branch_admin/payment_tracking/index', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Payment Tracking',
            'page_sub' => 'Record advance payments and verify pending GCash/cash submissions.',
            'plan_options' => $this->branchPlanOptions($branchId),
            'initial_plan_options' => $this->branchInitialPlanOptions($branchId),
            'rows' => $this->paymentRows($branchId),
            'initial_rows' => $this->initialPaymentRows($branchId),
            'can_approve' => true,
            'selected_status' => (string) $this->request->getGet('status'),
            'search' => $this->searchTerm(),
            'active_tab' => $tab,
        ]);
    }

    public function staff(): string
    {
        $roleId = (int) session('role_id');
        if ($roleId !== 3) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');

        return view('staff/payment_management/index', [
            'role_layout' => 'layouts/staff',
            'page_title' => 'Payment Management',
            'page_sub' => 'Record advance payments and monitor transaction statuses.',
            'plan_options' => $this->branchPlanOptions($branchId),
            'rows' => $this->paymentRows($branchId),
            'can_approve' => false,
            'selected_status' => (string) $this->request->getGet('status'),
            'search' => $this->searchTerm(),
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
            // The upper limit is the plan's remaining months, checked below.
            // Required for a normal plan; a client who also owes a package amount enters an AMOUNT instead.
            'months_covered' => 'permit_empty|is_natural',
            'payment_date' => 'required|valid_date[Y-m-d]',
            // Panel brief, section 3: support both cash and GCash here, not just cash.
            'payment_method' => 'required|in_list[cash,gcash]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $planId = (int) $this->request->getPost('plan_id');
        $amount = (float) $this->request->getPost('amount');
        $monthsCovered = max(1, (int) $this->request->getPost('months_covered'));
        $branchId = (int) session('branch_id');
        $paymentMethod = (string) $this->request->getPost('payment_method');
        $referenceNumber = trim((string) $this->request->getPost('reference_number'));

        if ($paymentMethod === 'gcash' && $referenceNumber === '') {
            return redirect()->back()->withInput()->with('error', 'Reference number is required for GCash payments.');
        }

        $plan = (new PlanModel())->find($planId);
        if (! $plan) {
            return redirect()->back()->withInput()->with('error', 'Plan not found.');
        }

        // Months, period and amount all come from the ledger: the next unpaid
        // contributions (verified AND pending ones counted), so payments never
        // overlap or leave a gap, and the last month is its real remainder
        // rather than fee x months.
        $claimAvail     = new \App\Services\ClaimAvailService();
        $paymentType    = 'monthly_contribution';
        $availId        = null;
        $allocationText = '';

        if ($claimAvail->recordingEntry($planId)['amount_mode']) {
            // This client also owes a package amount: the entry is an AMOUNT, and it is split
            // between the plan and the package (oldest due first). A package down payment is
            // chosen explicitly and goes only to the down payment.
            $kind = (string) $this->request->getPost('payment_kind') === 'down_payment' ? 'down_payment' : 'regular';
            $prep = $claimAvail->prepareAmountPayment($planId, (float) $this->request->getPost('amount'), (string) $this->request->getPost('payment_date'), $kind);
            if (! $prep['ok']) {
                return redirect()->back()->withInput()->with('error', (string) ($prep['error'] ?? 'Check the amount.'));
            }
            $amount         = $prep['amount'];
            $monthsCovered  = (int) $prep['months_covered'];
            $paymentType    = (string) $prep['payment_type'];
            $availId        = $prep['avail_id'];
            $allocationText = (string) $prep['text'];
            $projection     = ['start' => $prep['coverage_start'], 'end' => $prep['coverage_end']];
        } else {
            if ((int) $this->request->getPost('months_covered') < 1) {
                return redirect()->back()->withInput()->with('error', 'Choose how many months this payment covers.');
            }
            $projection = (new PaymentService())->projectPayment($plan, $monthsCovered);
            if ($projection['months'] < $monthsCovered) {
                return redirect()->back()->withInput()->with('error', $projection['months'] > 0
                    ? 'Months in advance cannot be more than ' . $projection['months'] . ' - that is all this plan has left to pay.'
                    : 'This plan has no months left to pay.');
            }
            $amount = $projection['amount'];

            // Remaining balance from the one shared calculation (plans.remaining_balance is not maintained).
            if ($amount > (float) ((new \App\Services\ClientPlanSummary())->forPlan((int) $plan['plan_id'])['remaining_balance'] ?? 0)) {
                return redirect()->back()->withInput()->with('error', 'Payment exceeds remaining balance.');
            }
        }


        $holder = db_connect()->table('plan_holders')
            ->select('branch_id')
            ->where('plan_holder_id', (int) $plan['plan_holder_id'])
            ->get()
            ->getRowArray();

        if (! $holder || (int) $holder['branch_id'] !== $branchId) {
            return redirect()->back()->withInput()->with('error', 'Selected plan does not belong to your branch.');
        }

        // Cash recorded here is money already in hand at the counter (or in
        // the field, for a collector - see CollectionListController::
        // submitRecordPayment(), which deliberately mirrors this), so it
        // posts as paid immediately regardless of who recorded it - there is
        // nothing left to verify. GCash always goes through the
        // pending-verification pipeline (duplicate-reference check
        // included), because the money hasn't actually been confirmed
        // received yet - see reviewGcash() for who verifies it.
        $status = $paymentMethod === 'gcash' ? 'pending' : 'paid';

        $coverage = ['start' => $projection['start'], 'end' => $projection['end']];

        $paymentData = [
            'plan_id' => $planId,
            'amount' => $amount,
            'months_covered' => $monthsCovered,
            'payment_type' => $paymentType,
            'avail_id' => $availId,
            'payment_date' => (string) $this->request->getPost('payment_date'),
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber !== '' ? $referenceNumber : null,
            'received_by' => (int) session('user_id'),
            'branch_id' => $branchId,
            'status' => $status,
            'coverage_start' => $coverage['start'],
            'coverage_end' => $coverage['end'],
            'remarks' => $status === 'paid' ? 'Recorded at branch counter' : 'Recorded by ' . ($roleId === 2 ? 'branch admin' : 'staff') . ', pending verification',
            'verified_by' => $status === 'paid' ? (int) session('user_id') : null,
            'verified_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
        ];

        // Filter to only include columns that exist in the payments table
        $paymentData = $this->filterPaymentData($paymentData);

        $paymentModel = new PaymentModel();
        $paymentId = (int) $paymentModel->insert($paymentData, true);

        if ($paymentId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Unable to record payment.');
        }

        // Panel brief, section 3: auto-generate the receipt number, no manual entry.
        $receiptNumber = (new PaymentService())->generateReceiptNumber($paymentId, $paymentData['payment_date']);
        $paymentModel->update($paymentId, $this->filterPaymentData(['official_receipt_number' => $receiptNumber]));

        $autoApproved = false;
        if ($status === 'paid') {
            // The very first payment of a plan holder who is still INACTIVE activates them. A holder who is already
            // active (e.g. registered at the branch) takes the normal path: without it the payment was never given a
            // cycle number or coverage, so the ledger ignored it (months paid stayed 0).
            $holderIsInactive = strtolower((string) (db_connect()->table('plan_holders')->select('status')->where('plan_holder_id', (int) $plan['plan_holder_id'])->get(1)->getRowArray()['status'] ?? '')) === 'inactive';
            if ($holderIsInactive && $this->isInitialPayment($paymentId, (int) $plan['plan_id'], (int) $plan['plan_holder_id'])) {
                $autoApproved = $this->autoApprovePlanHolderFromInitialPayment($plan, $monthsCovered, $paymentId);
            } else {
                (new MembershipService())->applyMembershipCoverage((int) $plan['plan_id'], $monthsCovered);
                (new CycleService())->afterPaymentVerified($paymentId, (int) $plan['plan_id']);
            }
        }

        $notificationService = new NotificationService();
        $activityLogService = new ActivityLogService();

        $userId = (int) (db_connect()->table('plan_holders')->select('user_id')->where('plan_holder_id', (int) $plan['plan_holder_id'])->get()->getRowArray()['user_id'] ?? 0);
        if ($userId > 0) {
            if ($status === 'paid') {
                $notificationService->notify($userId, $allocationText !== '' ? 'Your payment was recorded and confirmed: ' . $allocationText : $this->paymentApprovedMessage($monthsCovered, $plan), 'payment_approved');
            } else {
                $notificationService->notify($userId, 'Your ' . strtoupper($paymentMethod) . ' payment was recorded and is pending branch verification.', 'payment_pending');
            }
        }

        $activityLogService->log(
            (int) session('user_id'),
            'created',
            'payment',
            $paymentId,
            'Recorded ' . $paymentMethod . ' payment for plan #' . (int) $plan['plan_id'],
            null,
            [
                'plan_id' => (int) $plan['plan_id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'status' => $status,
            ]
        );

        $successMessage = $autoApproved
            ? 'Initial payment approved and registration activated.'
            : ($status === 'paid' ? 'Cash payment recorded and confirmed.' : 'Payment recorded and pending branch verification.');

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
     * Payment Monitoring / Payment Management remarks: an editable free-text
     * note on a single payment row (payments.remarks), reachable by Branch
     * Admin and Staff alike - same branch-scope check as recordCash()/
     * reviewGcash() above, no approval-state restriction since a remark is
     * just a note, not a status change. The client's own Payment History
     * shows this same field read-only.
     */
    public function saveRemarks(int $paymentId)
    {
        $roleId = (int) session('role_id');
        if (! in_array($roleId, [2, 3], true)) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        $paymentModel = new PaymentModel();
        $payment = $paymentModel->find($paymentId);

        if (! $payment || (int) ($payment['branch_id'] ?? 0) !== $branchId) {
            return redirect()->back()->with('error', 'That payment is not in your branch.');
        }

        $remarks = trim((string) $this->request->getPost('remarks'));
        $paymentModel->update($paymentId, ['remarks' => $remarks !== '' ? $remarks : null]);

        return redirect()->back()->with('success', 'Remarks saved.');
    }

    /**
     * GCash approval authority (Prompt: "GCash needs approval but not from
     * branch admin, from the collector collecting the contribution"): a
     * Branch Admin can still review any GCash payment in their branch (kept
     * as an oversight/override - nobody else can act if a barangay has no
     * collector assigned yet), but the primary approver is now whichever
     * collector is assigned to the plan holder's barangay - the same
     * area-scoping CollectorAssignmentService already does for the
     * Collection List. The collector's own job here is a manual one: check
     * the reference number shown on this row against their own GCash
     * account's transaction history before approving - there's no GCash API
     * integration in this app to do that automatically.
     */
    private function reviewGcash(int $paymentId, string $targetStatus)
    {
        $roleId = (int) session('role_id');
        $userId = (int) session('user_id');
        $isBranchAdmin = $roleId === 2;
        $isCollector = ! $isBranchAdmin && can_collect($userId);
        if (! $isBranchAdmin && ! $isCollector) {
            return redirect()->to('/unauthorized');
        }

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            $branchId = (int) (db_connect()->table('users')->select('branch_id')->where('user_id', $userId)->get()->getRowArray()['branch_id'] ?? 0);
        }
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

        if ($isCollector) {
            $barangayCode = (string) (db_connect()->table('plans p')
                ->select('ph.barangay_code')
                ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
                ->where('p.plan_id', (int) $payment['plan_id'])
                ->get()
                ->getRowArray()['barangay_code'] ?? '');

            if (! (new CollectorAssignmentService())->canActOnBarangay($userId, $branchId, $barangayCode)) {
                return redirect()->back()->with('error', 'This payment is outside your assigned collection area.');
            }
        }

        $method = strtolower((string) ($payment['payment_method'] ?? ''));
        
        // A client's GCash payment is decided on the review page (screenshot, checklist, last 4 digits of the reference) -
        // never with a bare Approve / Reject. A registration (initial) payment keeps its own approval screen.
        $holderInactive = strtolower((string) (db_connect()->table('plans p')->select('ph.status')->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')->where('p.plan_id', (int) $payment['plan_id'])->get(1)->getRowArray()['status'] ?? '')) === 'inactive';
        if ($method === 'gcash' && ((string) ($payment['payment_type'] ?? '')) !== 'initial_registration' && ! $holderInactive && \App\Services\GcashReviewService::ready()) {
            return redirect()->to('/gcash-review/' . $paymentId)->with('error', 'GCash payments are verified on the review screen: check the screenshot against your GCash app first.');
        }

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

        // Money a collector already collected is confirmed together with the remittance it goes into, never one by one.
        if ((int) ($payment['awaiting_remittance'] ?? 0) === 1) {
            if ($targetStatus === 'paid') {
                return redirect()->back()->with('error', 'A collector collected this payment. It is confirmed when you confirm the collector\'s remittance (Remittances).');
            }
            if ($isCollector || (int) ($payment['remittance_id'] ?? 0) > 0) {
                return redirect()->back()->with('error', 'This payment is already in a remittance. Report a discrepancy on the remittance instead of rejecting the payment.');
            }
            (new \App\Services\PaymentVerificationService())->rejectCollected($paymentId, $userId, trim((string) $this->request->getPost('rejection_reason')));

            return redirect()->back()->with('success', 'Payment rejected successfully.');
        }

        // A collector who verifies a client's GCash reference against their own GCash account has COLLECTED the money:
        // it is remitted with their other collections and confirmed by the Branch Admin (config: remitGcash).
        if ($isCollector && $targetStatus === 'paid' && $method === 'gcash' && config('CareSync')->remitGcash && \App\Services\PaymentService::collectedReady()) {
            $planForCollect = (new PlanModel())->find((int) $payment['plan_id']);
            $holderStatus = strtolower((string) (db_connect()->table('plan_holders')->select('status')->where('plan_holder_id', (int) ($planForCollect['plan_holder_id'] ?? 0))->get(1)->getRowArray()['status'] ?? ''));
            if ($planForCollect && $holderStatus === 'active') {
                $paymentModel->update($paymentId, [
                    'awaiting_remittance' => 1,
                    'collected_at' => date('Y-m-d H:i:s'),
                    'received_by' => $userId,
                    'remarks' => 'GCash checked by collector - waiting for the remittance to be confirmed by the office',
                ]);
                $verification = new \App\Services\PaymentVerificationService();
                $verification->countCollected($paymentId, (int) $payment['plan_id'], max(1, (int) ($payment['months_covered'] ?? 1)));
                $verification->notifyCollected($payment);
                (new ActivityLogService())->log($userId, 'approved', 'payment', $paymentId, 'GCash payment checked by collector (collected, awaiting remittance)', ['status' => 'pending'], ['status' => 'pending', 'awaiting_remittance' => 1]);

                return redirect()->back()->with('success', 'GCash payment checked. It now shows under Remit collections - remit it with your other collections.');
            }
        }

        $rejectionReason = trim((string) $this->request->getPost('rejection_reason'));
        $reviewerLabel = $isCollector ? 'collector' : 'branch admin';
        // $targetStatus is 'paid' or 'cancelled' (see approveGcash()/
        // rejectGcash() above) - this used to compare against 'verified',
        // a value that's never actually passed in, so every approval fell
        // through to the "rejected" branch and mislabeled its own remarks.
        $remarks = $targetStatus === 'paid'
            ? 'GCash verified by ' . $reviewerLabel
            : ('GCash rejected by ' . $reviewerLabel . ($rejectionReason !== '' ? (': ' . $rejectionReason) : ''));

        // received_by is WHO COLLECTED the money. When a collector recorded a cash payment in the field and the Branch Admin
        // now verifies it, the collector must stay as received_by (commission and remittances are worked out from it);
        // only a payment nobody had received yet (a client's own GCash payment) is attributed to the reviewer.
        $reviewUpdate = [
            'status' => $targetStatus,
            'verified_by' => (int) session('user_id'),
            'verified_at' => date('Y-m-d H:i:s'),
            'remarks' => $remarks,
        ];
        if ((int) ($payment['received_by'] ?? 0) <= 0) {
            $reviewUpdate['received_by'] = (int) session('user_id');
        }
        $paymentModel->update($paymentId, $reviewUpdate);

        $plan = (new PlanModel())->find((int) $payment['plan_id']);
        $autoApproved = false;
        if ($plan && ! in_array($targetStatus, ['paid', 'verified'], true)) {
            // Rejected / cancelled: any slices of this payment go and the plan's allocations are recomputed.
            (new \App\Services\ClaimAvailService())->onPaymentReversed((int) $payment['payment_id'], (int) $plan['plan_id']);
        }
        if ($plan && in_array($targetStatus, ['paid', 'verified'], true)) {
            $monthsCovered = max(1, (int) ($payment['months_covered'] ?? 1));
            $holderIsInactive = strtolower((string) (db_connect()->table('plan_holders')->select('status')->where('plan_holder_id', (int) ($plan['plan_holder_id'] ?? 0))->get(1)->getRowArray()['status'] ?? '')) === 'inactive';
            if ($holderIsInactive && $this->isInitialPayment((int) $payment['payment_id'], (int) $plan['plan_id'], (int) ($plan['plan_holder_id'] ?? 0))) {
                $autoApproved = $this->autoApprovePlanHolderFromInitialPayment($plan, $monthsCovered, (int) $payment['payment_id']);
            } else {
                (new MembershipService())->applyMembershipCoverage((int) $plan['plan_id'], $monthsCovered);
                (new CycleService())->afterPaymentVerified((int) $payment['payment_id'], (int) $plan['plan_id']);
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
        return round($monthlyFee * max(1, $monthsCovered), 2);
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

    private function autoApprovePlanHolderFromInitialPayment(array $plan, int $monthsCovered = 1, int $paymentId = 0): bool
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


        // One activation path for every approval route (see ApprovalService::approveInitialPayment()).
        return (new \App\Services\ApprovalService())->approveInitialPayment($paymentId);
    }

    private function branchPlanOptions(int $branchId): array
    {
        $plans = (new MembershipService())->getBranchPlans($branchId);

        return $this->withRemainingMonths(array_values(array_filter($plans, static function (array $plan): bool {
            return (string) ($plan['plan_status'] ?? '') === 'active';
        })));
    }

    /**
     * Adds remaining_months (how many more months this plan's current cycle can
     * take) to each plan option, so the Months in Advance list stops there.
     *
     * @param list<array<string,mixed>> $plans
     *
     * @return list<array<string,mixed>>
     */
    private function withRemainingMonths(array $plans): array
    {
        $payments = new PaymentService();

        foreach ($plans as &$plan) {
            try {
                [, , , $remaining] = $payments->remainingPayableMonths(['plan_id' => $plan['plan_id'], 'monthly_fee' => $plan['monthly_fee'] ?? 0]);
            } catch (\Throwable $e) {
                $remaining = 0;
            }
            $plan['remaining_months'] = max(0, (int) $remaining);
            // A client who also owes a package amount is entered by AMOUNT (with a preview).
            $entry = (new \App\Services\ClaimAvailService())->recordingEntry((int) $plan['plan_id']);
            $plan['amount_mode']   = $entry['amount_mode'];
            $plan['has_down_due']  = $entry['down_avail'] !== null;
            $plan['down_left']     = $entry['down_left'];
        }
        unset($plan);

        return $plans;
    }

    private function branchInitialPlanOptions(int $branchId): array
    {
        $plans = (new MembershipService())->getBranchPlans($branchId);

        return $this->withRemainingMonths(array_values(array_filter($plans, static function (array $plan): bool {
            return (string) ($plan['plan_status'] ?? '') === 'inactive';
        })));
    }

    private function searchTerm(): string
    {
        return cs_search_term($this->request->getGet('search'));
    }

    /** Columns the payment search box looks in (plan holder, plan number, receipt/reference, remarks). */
    private const SEARCH_COLUMNS = [
        'users.first_name', 'users.last_name', 'users.contact_number', 'plan_holders.unique_identifier',
        'payments.reference_number', 'payments.official_receipt_number', 'payments.remarks',
    ];

    private function paymentRows(int $branchId): array
    {
        $status = strtolower(trim((string) $this->request->getGet('status')));
        $params = [$branchId];
        $searchSql = cs_search_sql($this->searchTerm(), self::SEARCH_COLUMNS, $params);

        $sql = "SELECT payments.*, users.first_name, users.last_name, plan_holders.unique_identifier, plan_holders.plan_holder_id, plan_holders.status AS holder_status,
                staff_u.first_name AS staff_first_name, staff_u.last_name AS staff_last_name, plans.payment_coverage_until, plans.months_paid
            FROM payments
            INNER JOIN plans ON plans.plan_id = payments.plan_id
            INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
            INNER JOIN users ON users.user_id = plan_holders.user_id
            LEFT JOIN users staff_u ON staff_u.user_id = payments.received_by
            WHERE payments.branch_id = ?{$searchSql}
            ORDER BY payments.payment_id DESC";

        $db = db_connect();
        $query = $db->query($sql, $params);
        $result = $query->getResultArray();

        if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            return $this->withLivePlanFigures($this->ensurePaymentColumns($result));
        }
        
        // Filter by status after fetching
        $filtered = array_filter($result, function (array $row) use ($status): bool {
            return $row['status'] === $status;
        });
        
        return $this->withLivePlanFigures($this->ensurePaymentColumns($filtered));
    }

    /**
     * The months-paid column on the plans table is not kept up to date, so the
     * figure shown beside each payment comes from the one shared calculation
     * (ClientPlanSummary), looked up once per plan.
     *
     * @param array<int|string,array<string,mixed>> $rows
     *
     * @return array<int|string,array<string,mixed>>
     */
    private function withLivePlanFigures(array $rows): array
    {
        $summary = new \App\Services\ClientPlanSummary();
        $cache   = [];

        foreach ($rows as &$row) {
            $planId = (int) ($row['plan_id'] ?? 0);
            if ($planId <= 0) {
                continue;
            }

            $cache[$planId] ??= $summary->forPlan($planId) ?? false;
            if ($cache[$planId] !== false) {
                $row['months_paid']       = $cache[$planId]['months_paid'];
                $row['plan_total_months'] = $cache[$planId]['total_months'];
            }
        }
        unset($row);

        return $rows;
    }

    private function initialPaymentRows(int $branchId): array
    {
        // The Initial Payments tab has no search box, so the monitoring tab's
        // search term must not silently filter it.
        $params = [$branchId];
        $searchSql = '';

        $sql = "SELECT payments.*, users.first_name, users.last_name, plan_holders.unique_identifier, plan_holders.plan_holder_id, plan_holders.status AS holder_status,
                staff_u.first_name AS staff_first_name, staff_u.last_name AS staff_last_name, plans.payment_coverage_until
            FROM payments
            INNER JOIN plans ON plans.plan_id = payments.plan_id
            INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
            INNER JOIN users ON users.user_id = plan_holders.user_id
            LEFT JOIN users staff_u ON staff_u.user_id = payments.received_by
            WHERE payments.branch_id = ?{$searchSql}
            GROUP BY payments.payment_id
            ORDER BY payments.payment_id DESC";
        
        $db = db_connect();
        $query = $db->query($sql, $params);
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

        $sql = "SELECT payments.*, users.first_name, users.last_name, plan_holders.unique_identifier, branches.branch_name,
                staff_u.first_name AS staff_first_name, staff_u.last_name AS staff_last_name, plans.payment_coverage_until
            FROM payments
            INNER JOIN plans ON plans.plan_id = payments.plan_id
            INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
            INNER JOIN users ON users.user_id = plan_holders.user_id
            LEFT JOIN branches ON branches.branch_id = payments.branch_id
            LEFT JOIN users staff_u ON staff_u.user_id = payments.received_by
            WHERE 1=1";
        
        $params = [];
        $sql .= cs_search_sql($filters["search"], self::SEARCH_COLUMNS, $params);
        
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
     * @return array{status:string,payment_method:string,branch_id:int,date_from:string,date_to:string,search:string}
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
            'search' => $this->searchTerm(),
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
}
