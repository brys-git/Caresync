<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentModel;
use App\Config\ValidationRules;
use App\Services\PaymentService;

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
     * Client dashboard rebuild, Phase 2: PAYMENT overview only - plan
     * name, monthly contribution, remaining balance, months paid (+ an
     * awaiting-verification note so a client understands why the number
     * hasn't moved yet and isn't tempted to pay twice), next due date,
     * and a Make Payment button. The submission forms and the full
     * transaction table both used to live on this same page - the forms
     * move to the new Make Payment page (Phase 3), the table moves to
     * paymentHistory() below.
     */
    public function payment(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $planHolder = $access['plan_holder'];

        if (($access['state'] ?? 'unregistered') === 'unregistered' || ! $planHolder) {
            return view('client/payment', [
                'role_layout' => 'layouts/plan_holder',
                'page_title' => 'Payment',
                'page_sub' => 'Track contribution records.',
                'access' => $access,
                'plan' => null,
            ]);
        }

        if (($access['state'] ?? 'unregistered') === 'awaiting_activation') {
            return redirect()->to('/initial-payment')->with('info', 'Complete your initial payment before viewing payment history.');
        }

        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        $monthsAwaitingVerification = 0;
        if ($plan) {
            $monthsAwaitingVerification = (int) (db_connect()->table('payments')
                ->selectSum('months_covered')
                ->where('plan_id', (int) $plan['plan_id'])
                ->where('status', 'pending')
                ->get()
                ->getRowArray()['months_covered'] ?? 0);
        }

        return view('client/payment', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Payment',
            'page_sub' => 'Track contribution records.',
            'access' => $access,
            'plan' => $plan,
            'plan_name' => $plan ? (new \App\Services\MembershipService())->resolvePackageName((int) ($plan['package_id'] ?? 0)) : '',
            'months_awaiting_verification' => $monthsAwaitingVerification,
        ]);
    }

    /**
     * Client dashboard rebuild, Phase 2: PAYMENT HISTORY, split out to its
     * own page. Sorted newest first per spec - previously ascending
     * (oldest first) on the combined page; this is a deliberate reversal
     * for the new dedicated history page, not an oversight.
     */
    public function paymentHistory(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }
        $planHolder = $access['plan_holder'];

        if (($access['state'] ?? 'unregistered') === 'unregistered' || ! $planHolder) {
            return view('client/payment_history', [
                'role_layout' => 'layouts/plan_holder',
                'page_title' => 'Payment History',
                'page_sub' => 'Every payment on your plan.',
                'access' => $access,
                'payments' => [],
            ]);
        }

        if (($access['state'] ?? 'unregistered') === 'awaiting_activation') {
            return redirect()->to('/initial-payment')->with('info', 'Complete your initial payment before viewing payment history.');
        }

        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        $payments = [];
        if ($plan) {
            $payments = (new PaymentModel())
                ->where('plan_id', (int) $plan['plan_id'])
                ->orderBy('payment_date', 'DESC')
                ->orderBy('payment_id', 'DESC')
                ->findAll();
        }

        return view('client/payment_history', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Payment History',
            'page_sub' => 'Every payment on your plan.',
            'access' => $access,
            'payments' => $payments,
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

        $coverage = (new PaymentService())->projectCoverage($plan, $monthsCovered);

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
            'coverage_start' => $coverage['start'],
            'coverage_end' => $coverage['end'],
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

        $paymentModel = new PaymentModel();
        $paymentId = (int) $paymentModel->insert($payload, true);

        if ($paymentId > 0) {
            // Panel brief, section 3: auto-generate the receipt number, no manual entry.
            $paymentModel->update($paymentId, [
                'official_receipt_number' => (new PaymentService())->generateReceiptNumber($paymentId, $payload['payment_date']),
            ]);
        }

        return redirect()->to('/client/payment')->with('success', 'GCash payment submitted. Waiting for branch admin verification.');
    }

    /**
     * Download the receipt for one specific payment (Phase 1: was a
     * dashboard quick action that only ever grabbed the plan's latest
     * paid payment - now a per-row link in Payment History, so it needs
     * to fetch the exact payment the client clicked, not just the most
     * recent one).
     *
     * Never trusts the payment ID alone: re-verifies server-side that the
     * payment belongs to a plan owned by the requesting client's own
     * plan_holder_id (not just "some plan"), and that its status is
     * 'paid' - the UI only ever links to paid rows, but a client could
     * still hand-edit the URL to a pending/cancelled payment_id otherwise.
     */
    public function downloadReceipt(int $paymentId)
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

        $payment = db_connect()->table('payments pay')
            ->select('pay.*')
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->where('pay.payment_id', $paymentId)
            ->where('p.plan_holder_id', (int) $planHolder['plan_holder_id'])
            ->where('pay.status', 'paid')
            ->get()
            ->getRowArray();

        if (! $payment) {
            return redirect()->back()->with('error', 'Receipt not found, or this payment has not been verified yet.');
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
