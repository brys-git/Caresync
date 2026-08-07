<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentModel;
use App\Config\ValidationRules;

/**
 * ClientPaymentController
 * 
 * Handles client payment submissions and tracking
 * Part of the refactored ClientPortal controller
 * 
 * Uses centralized validation rules to reduce code duplication
 */
class ClientPaymentController extends BaseController
{
    use ClientPortalTrait;

    /**
     * Display payment page with history
     */
    public function payment(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $planHolder = $access['plan_holder'];
        $program = \App\Services\MembershipService::getProgramInfo();

        $membershipPlans = [];
        $db = db_connect();
        if ($db->tableExists('membership_programs')) {
            $membershipPlans = $db->table('membership_programs')
                ->select('program_id, program_name')
                ->where('is_active', 1)
                ->orderBy('program_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        if (($access['state'] ?? 'unregistered') === 'unregistered' || ! $planHolder) {
            return view('client/payment', [
                'role_layout' => 'layouts/plan_holder',
                'access' => $access,
                'plan' => null,
                'payments' => [],
                'membership_plans' => $membershipPlans,
                'program' => $program,
            ]);
        }

        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        $payments = [];
        if ($plan) {
            $payments = (new PaymentModel())
                ->where('plan_id', (int) $plan['plan_id'])
                ->orderBy('payment_id', 'DESC')
                ->findAll();
        }

        $totalCollections = 0.0;
        $completedCount = 0;
        $pendingCount = 0;

        foreach ($payments as $p) {
            $pStatus = strtolower((string) ($p['status'] ?? ''));
            if ($pStatus === 'paid') {
                $totalCollections += (float) ($p['amount'] ?? 0);
                $completedCount++;
            } elseif ($pStatus === 'pending') {
                $pendingCount++;
            }
        }

        return view('client/payment', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => null,
            'access' => $access,
            'plan' => $plan,
            'payments' => $payments,
            'supports_proof_upload' => $this->supportsProofUpload(),
            'membership_plans' => $membershipPlans,
            'program' => $program,
            'view_mode' => 'history',
            'total_collections' => $totalCollections,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
        ]);
    }

    /**
     * Alias for the client payment page used by the Advance Payment navigation item.
     */
    public function advancePayment(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $planHolder = $access['plan_holder'];
        $program = \App\Services\MembershipService::getProgramInfo();

        $membershipPlans = [];
        $db = db_connect();
        if ($db->tableExists('membership_programs')) {
            $membershipPlans = $db->table('membership_programs')
                ->select('program_id, program_name')
                ->where('is_active', 1)
                ->orderBy('program_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        if (($access['state'] ?? 'unregistered') === 'unregistered' || ! $planHolder) {
            return view('client/payment', [
                'role_layout' => 'layouts/plan_holder',
                'access' => $access,
                'plan' => null,
                'payments' => [],
                'membership_plans' => $membershipPlans,
                'program' => $program,
                'view_mode' => 'advance',
            ]);
        }

        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        $payments = [];
        if ($plan) {
            $payments = (new PaymentModel())
                ->where('plan_id', (int) $plan['plan_id'])
                ->orderBy('payment_id', 'DESC')
                ->findAll();
        }

        $totalCollections = 0.0;
        $completedCount = 0;
        $pendingCount = 0;

        foreach ($payments as $p) {
            $pStatus = strtolower((string) ($p['status'] ?? ''));
            if ($pStatus === 'paid') {
                $totalCollections += (float) ($p['amount'] ?? 0);
                $completedCount++;
            } elseif ($pStatus === 'pending') {
                $pendingCount++;
            }
        }

        $monthlyFee = (float) ($plan['monthly_fee'] ?? ($program['monthly_fee'] ?? 240));

        $userName = trim((string) (($access['user']['first_name'] ?? '') . ' ' . ($access['user']['last_name'] ?? '')));
        $planName = (string) ($plan['package_name'] ?? ($program['name'] ?? 'Damayan Burial Program'));
        $lastPaymentStatus = 'None';
        if (! empty($payments)) {
            $lastPaymentStatus = ucfirst((string) ($payments[0]['status'] ?? 'none'));
        }

        return view('client/payment', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => null,
            'access' => $access,
            'plan' => $plan,
            'payments' => $payments,
            'supports_proof_upload' => $this->supportsProofUpload(),
            'membership_plans' => $membershipPlans,
            'program' => $program,
            'view_mode' => 'advance',
            'total_collections' => $totalCollections,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'monthly_fee' => $monthlyFee,
            'user_name' => $userName,
            'plan_name' => $planName,
            'last_payment_status' => $lastPaymentStatus,
        ]);
    }

    /**
     * Submit GCash payment for membership fee
     */
    public function submitGcashPayment()
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        if (($access['state'] ?? 'unregistered') !== 'active') {
            return redirect()->back()->with('error', 'Access denied. Payment submission requires active membership.');
        }

        $planHolder = $access['plan_holder'];
        if (! $planHolder) {
            return redirect()->back()->with('error', 'No plan holder profile found.');
        }

        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        if (! $plan) {
            return redirect()->back()->with('error', 'No active plan found for payment submission.');
        }

        $rules = ValidationRules::getPaymentRules();
        $messages = ValidationRules::getValidationMessages();

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $monthsCovered = max(1, (int) $this->request->getPost('months_covered'));
        $amount = (float) $this->request->getPost('amount');
        $monthlyFee = (float) ($plan['monthly_fee'] ?? 0);
        $expectedAmount = round($monthlyFee * $monthsCovered, 2);
        if ($expectedAmount <= 0 || abs($expectedAmount - $amount) > 0.01) {
            return redirect()->back()->withInput()->with('error', 'Amount must match your monthly fee multiplied by months covered.');
        }

        if ($amount > (float) ($plan['remaining_balance'] ?? 0)) {
            return redirect()->back()->withInput()->with('error', 'Payment exceeds your remaining balance.');
        }

        $reference = trim((string) $this->request->getPost('reference_number'));
        $duplicate = (new PaymentModel())
            ->where('reference_number', $reference)
            ->first();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'Duplicate reference number detected. Please verify your reference number.');
        }

        $payload = [
            'plan_id' => (int) $plan['plan_id'],
            'amount' => $amount,
            'months_covered' => $monthsCovered,
            'payment_date' => (string) $this->request->getPost('payment_date'),
            'payment_method' => 'gcash',
            'reference_number' => $reference,
            'received_by' => null,
            'branch_id' => (int) ($planHolder['branch_id'] ?? 0),
            'status' => 'pending',
            'remarks' => 'Submitted by client, awaiting branch verification',
        ];

        $proof = $this->request->getFile('proof_image');
        if ($this->supportsProofUpload() && $monthsCovered > 1 && (! $proof || ! $proof->isValid())) {
            return redirect()->back()->withInput()->with('error', 'Proof image is required for advance payments.');
        }
        if ($this->supportsProofUpload() && $proof && $proof->isValid() && ! $proof->hasMoved()) {
            $uploadDir = WRITEPATH . 'uploads/payment-proofs';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $proofName = $proof->getRandomName();
            $proof->move($uploadDir, $proofName);
            $payload['proof_image'] = $proofName;
        }

        (new PaymentModel())->insert($payload);

        return redirect()->to('/client/payment')->with('success', 'GCash payment submitted. Waiting for branch admin verification.');
    }

    /**
     * Download latest paid receipt for the active plan
     */
    public function downloadReceipt()
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        $planHolder = $access['plan_holder'] ?? null;
        if (! $planHolder) {
            return redirect()->back()->with('error', 'No plan holder profile found.');
        }

        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        if (! $plan) {
            return redirect()->back()->with('error', 'No active plan found.');
        }

        $payment = (new PaymentModel())
            ->where('plan_id', (int) $plan['plan_id'])
            ->where('status', 'paid')
            ->orderBy('payment_id', 'DESC')
            ->limit(1)
            ->first();

        if (! $payment) {
            return redirect()->back()->with('error', 'No paid payments found to download receipt.');
        }

        $content = "Receipt #" . $payment['payment_id'] . "\n";
        $content .= "Date: " . ($payment['payment_date'] ?? '') . "\n";
        $content .= "Amount: PHP " . number_format((float) ($payment['amount'] ?? 0), 2) . "\n";
        $content .= "Method: " . strtoupper((string) ($payment['payment_method'] ?? '')) . "\n";
        $content .= "Reference/OR: " . ($payment['reference_number'] ?: ($payment['official_receipt_number'] ?? '-')) . "\n";
        $content .= "Status: " . ($payment['status'] ?? '') . "\n";

        $filename = 'receipt-' . $payment['payment_id'] . '.txt';

        return $this->response
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }
}
