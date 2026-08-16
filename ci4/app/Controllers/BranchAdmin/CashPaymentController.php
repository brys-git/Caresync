<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Models\PlanHolderModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CashPaymentController
 * 
 * Handles cash payment recording by branch admin before client submits in the system
 * Client pays cash at branch → admin records receipt number → client later verifies it
 */
class CashPaymentController extends BaseController
{
    /**
     * Show cash payment recording form
     */
    public function recordPaymentForm(): ResponseInterface|string
    {
        $branch_id = (int) session('branch_id');
        if ($branch_id <= 0) {
            return redirect()->to('/admin/dashboard')->with('error', 'Invalid branch.');
        }

        $db = db_connect();

        // Get branch clients with PENDING account_status (for Initial Payment)
        // These are users who haven't verified their account yet
        $initialClients = $db->table('users u')
            ->select('u.user_id, u.first_name, u.last_name, u.account_status, ph.plan_holder_id, ph.unique_identifier')
            ->join('plan_holders ph', 'ph.user_id = u.user_id', 'left')
            ->where('ph.branch_id', $branch_id)
            ->where('u.role_id', 4)
            ->where('u.account_status', 'pending')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Get branch clients with VERIFIED account_status (for Regular Payment)
        // These are users who have verified their account
        $regularClients = $db->table('users u')
            ->select('u.user_id, u.first_name, u.last_name, u.account_status, ph.plan_holder_id, ph.unique_identifier')
            ->join('plan_holders ph', 'ph.user_id = u.user_id', 'left')
            ->where('ph.branch_id', $branch_id)
            ->where('u.role_id', 4)
            ->where('u.account_status', 'verified')
            ->orderBy('u.first_name', 'ASC')
            ->get()
            ->getResultArray();

        // Get approval queue (recent cash payment records)
        $approvalQueue = $db->table('cash_payment_records cpr')
            ->select('cpr.*, cpr.client_name, cpr.receipt_number, cpr.verified, cpr.created_at')
            ->where('cpr.branch_id', $branch_id)
            ->orderBy('cpr.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $program = \App\Services\MembershipService::getProgramInfo();

        return view('branch_admin/cash_payment_record', [
            'branch_id' => $branch_id,
            'role_layout' => 'layouts/branch_admin',
            'page_title' => null,
            'initial_clients' => $initialClients,
            'regular_clients' => $regularClients,
            'approval_queue' => $approvalQueue,
            'monthly_fee' => (float) ($program['monthly_fee'] ?? 240.0),
        ]);
    }

    /**
     * Save cash payment record
     */
    public function savePaymentRecord()
    {
        $branch_id = (int) session('branch_id');
        if ($branch_id <= 0) {
            return redirect()->to('/admin/dashboard')->with('error', 'Invalid branch.');
        }

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
            'branch_id' => $branch_id,
            'client_name' => $clientName,
            'months_covered' => $monthsCovered,
            'amount' => $amount,
            'receipt_number' => $receiptNumber,
            'recorded_by' => (int) session('user_id'),
            'recorded_date' => date('Y-m-d'),
            'verified' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        log_message('debug', '[CashPaymentSave] Inserting to cash_payment_records: branch=' . $branch_id . ' receipt=' . $receiptNumber);

        $inserted = $db->table('cash_payment_records')->insert($paymentData);

        if (!$inserted) {
            $error = $db->error();
            log_message('error', '[CashPaymentSave] cash_payment_records insert failed: ' . json_encode($error));
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record payment. Error: ' . (isset($error['message']) ? $error['message'] : 'Unknown error'));
        }

        // 2. Also save to payments table for client verification sync
        // For branch admin (role 2), status is 'paid' (verified immediately)
        $paymentDataPayments = [
            'plan_id' => (int) $plan['plan_id'],
            'amount' => $amount,
            'months_covered' => $monthsCovered,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'cash',
            'reference_number' => $receiptNumber,
            'official_receipt_number' => $receiptNumber,
            'received_by' => (int) session('user_id'),
            'branch_id' => $branch_id,
            'status' => 'paid',  // Branch admin records are immediately verified
            'remarks' => 'Recorded at branch counter',
            'verified_by' => (int) session('user_id'),
            'verified_at' => date('Y-m-d H:i:s'),
            'payment_type' => $paymentType === 'initial' ? 'initial_registration' : 'monthly_contribution',
        ];

        // Filter to only include columns that exist in the payments table
        $paymentModel = new \App\Models\PaymentModel();
        $paymentFields = $db->getFieldNames('payments');
        $paymentDataPayments = array_intersect_key($paymentDataPayments, array_flip($paymentFields));

        log_message('debug', '[CashPaymentSave] Inserting to payments table: plan_id=' . $plan['plan_id'] . ' receipt=' . $receiptNumber);

        $paymentId = (int) $paymentModel->insert($paymentDataPayments, true);

        if ($paymentId <= 0) {
            // Rollback cash_payment_records if payments insert fails
            $db->table('cash_payment_records')->where('receipt_number', $receiptNumber)->delete();
            log_message('error', '[CashPaymentSave] payments table insert failed, rolled back cash_payment_records');
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record payment in payment system.');
        }

        log_message('debug', '[CashPaymentSave] Success: receipt=' . $receiptNumber . ' for ' . $clientName . ' (payment_id=' . $paymentId . ')');

        // If initial payment, auto-approve the plan holder
        $autoApproved = false;
        if ($paymentType === 'initial') {
            // Check if this is an initial payment for an inactive plan holder
            $holder = db_connect()->table('plan_holders')
                ->select('status, user_id')
                ->where('plan_holder_id', $planHolderId)
                ->get()
                ->getRowArray();

            if ($holder && strtolower((string) ($holder['status'] ?? '')) === 'inactive') {
                $autoApproved = $this->autoApprovePlanHolderFromInitialPayment($plan, $monthsCovered);
            }
        } else {
            // For regular payments, apply membership coverage
            (new \App\Services\MembershipService())->applyMembershipCoverage((int) $plan['plan_id'], $monthsCovered);
        }

        // Send notification to client
        if (isset($holder['user_id']) && $holder['user_id'] > 0) {
            (new \App\Services\NotificationService())->notify(
                (int) $holder['user_id'],
                'Cash payment of PHP ' . number_format($amount, 2) . ' recorded for ' . $monthsCovered . ' month(s). Receipt: ' . esc($receiptNumber),
                'payment_approved'
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
                'status' => 'paid',
                'receipt_number' => $receiptNumber,
            ]
        );

        $successMessage = $autoApproved
            ? 'Initial payment approved and registration activated. Receipt: ' . esc($receiptNumber) . ' for ' . esc($clientName)
            : 'Cash payment recorded and confirmed. Receipt: ' . esc($receiptNumber) . ' for ' . esc($clientName);

        return redirect()->to('/branch-admin/cash-payment-record')
            ->with('success', $successMessage);
    }

    /**
     * Auto-approve plan holder after initial payment verification
     */
    private function autoApprovePlanHolderFromInitialPayment(array $plan, int $monthsCovered = 1): bool
    {
        $planHolderId = (int) ($plan['plan_holder_id'] ?? 0);

        if ($planHolderId <= 0) {
            return false;
        }

        $branchId = (int) session('branch_id');
        $planHolderModel = new \App\Models\PlanHolderModel();
        $userModel = new \App\Models\UserModel();
        $planModel = new \App\Models\PlanModel();

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
                    'monthly_fee' => \App\Services\MembershipService::MONTHLY_FEE,
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
                    'monthly_fee' => \App\Services\MembershipService::MONTHLY_FEE,
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
                (new \App\Services\MembershipService())->enforceOneActivePlan($planHolderId);
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
                (new \App\Services\NotificationService())->notify((int) ($holder['user_id'] ?? 0), 'Your registration has been approved. Your plan is now active.', 'registration_pending');
            } catch (\Throwable $e) {
            }

            // Try to log activity, but don't fail if it errors
            try {
                (new \App\Services\ActivityLogService())->log(
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

    /**
     * Resolve package and version for plan creation
     */
    private function resolvePackageAndVersion(): array
    {
        $db = db_connect();

        $package = $db->table('packages')
            ->select('package_id')
            ->where('package_id', \App\Services\MembershipService::DEFAULT_PACKAGE_ID)
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
                'price' => \App\Services\MembershipService::MONTHLY_FEE,
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

    /**
     * View recorded cash payments
     */
    public function viewPayments(): ResponseInterface|string
    {
        $branch_id = (int) session('branch_id');
        if ($branch_id <= 0) {
            return redirect()->to('/admin/dashboard')->with('error', 'Invalid branch.');
        }

        $payments = db_connect()->table('cash_payment_records')
            ->where('branch_id', $branch_id)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('branch_admin/cash_payments_list', [
            'payments' => $payments,
        ]);
    }
}
