<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentModel;
use App\Config\ValidationRules;
use App\Services\CycleService;
use App\Services\MembershipService;
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
     * Entitlement cycle model: how many more months this plan's CURRENT
     * cycle can accept a payment for - not the plan's lifetime term. A
     * payment must never be allowed to span a cycle boundary (see
     * CycleService's class doc comment on why: overpaying past the exact
     * moment a cycle closes would let a plan holder immediately bank
     * months toward - and claim - the next cycle too), so the cap comes
     * from CycleService::currentCycle()'s own remaining-balance figure,
     * converted to whole months at this plan's own monthly_fee, rather
     * than the flat plan-lifetime ceil(total/fee) this used before the
     * cycle model existed.
     *
     * Returns [cycleTargetMonths, verifiedMonths, pendingMonths, remainingMonths].
     */
    private function paymentCapacity(array $plan): array
    {
        $planId = (int) $plan['plan_id'];
        $cycle = (new CycleService())->currentCycle($planId);

        $monthlyFee = (float) ($plan['monthly_fee'] ?? MembershipService::MONTHLY_FEE);
        if ($monthlyFee <= 0) {
            $monthlyFee = MembershipService::MONTHLY_FEE;
        }

        $cycleTargetMonths = (int) ceil($cycle['target'] / $monthlyFee);

        // Phase 0: months_paid is recalculated from verified payments only
        // (now scoped to this cycle - see MembershipService::
        // recalculateMonthsPaid()), so it's safe to use directly here.
        $verifiedMonths = $cycle['months_paid'];

        $pendingMonths = (int) (db_connect()->table('payments')
            ->selectSum('months_covered')
            ->where('plan_id', $planId)
            ->where('status', 'pending')
            ->get()
            ->getRowArray()['months_covered'] ?? 0);

        $cycleRemainingMonths = (int) ceil(max(0.0, $cycle['remaining']) / $monthlyFee);
        $remainingMonths = max(0, $cycleRemainingMonths - $pendingMonths);

        return [$cycleTargetMonths, $verifiedMonths, $pendingMonths, $remainingMonths];
    }

    /**
     * Client dashboard rebuild, Phase 3: Make Payment page. Read-only plan
     * name/monthly contribution, a months selector capped by how much of
     * the plan's term is left to pay for, a fixed GCash payment method, and
     * a reference number field - the actual amount is never entered by the
     * client, only computed (client-side for display, server-side for
     * real) from months selected x monthly fee.
     */
    public function makePayment(): ResponseInterface|string
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        if (($access['state'] ?? 'unregistered') !== 'active' || ! $access['plan_holder']) {
            return redirect()->to('/client/payment')->with('error', 'Access denied. Payment submission requires active membership.');
        }

        $planHolder = $access['plan_holder'];
        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        if (! $plan) {
            return redirect()->to('/client/payment')->with('error', 'No active plan found for payment submission.');
        }

        [$planTermMonths, $verifiedMonths, $pendingMonths, $remainingMonths] = $this->paymentCapacity($plan);

        return view('client/payment_make', [
            'role_layout' => 'layouts/plan_holder',
            'page_title' => 'Make Payment',
            'page_sub' => 'Submit a GCash payment toward your plan.',
            'plan' => $plan,
            'plan_name' => (new MembershipService())->resolvePackageName((int) ($plan['package_id'] ?? 0)),
            'plan_term_months' => $planTermMonths,
            'verified_months' => $verifiedMonths,
            'pending_months' => $pendingMonths,
            'remaining_months' => $remainingMonths,
        ]);
    }

    /**
     * Client dashboard rebuild, Phase 3: server-side re-validation for the
     * Make Payment form. Never trusts the client for months covered or
     * amount - the months cap and the total are both recomputed here from
     * the plan's own current state, exactly like makePayment() computed
     * them for display. payment_method and status are never read from
     * POST: GCash is the only method this page offers, and every
     * client-submitted payment starts 'pending' pending branch admin
     * verification, same as the old submitGcashPayment() flow.
     */
    public function submitPayment()
    {
        try {
            $access = $this->resolveAccessState();
        } catch (\RuntimeException $e) {
            return redirect()->to('/signin')->with('error', 'Session expired. Please log in again.');
        }

        if (($access['state'] ?? 'unregistered') !== 'active' || ! $access['plan_holder']) {
            return redirect()->to('/client/payment')->with('error', 'Access denied. Payment submission requires active membership.');
        }

        $planHolder = $access['plan_holder'];
        $plan = $this->latestPlan((int) $planHolder['plan_holder_id']);
        if (! $plan) {
            return redirect()->to('/client/payment')->with('error', 'No active plan found for payment submission.');
        }

        [, , , $remainingMonths] = $this->paymentCapacity($plan);

        $rules = [
            'months_covered' => 'required|is_natural_no_zero',
            // GCash reference numbers are 13 numeric digits.
            'reference_number' => 'required|regex_match[/^\d{13}$/]',
        ];
        $messages = [
            'months_covered' => ['is_natural_no_zero' => 'Select how many months you want to pay.'],
            'reference_number' => ['regex_match' => 'Enter a valid 13-digit GCash reference number.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to('/client/payment/make')->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $monthsCovered = (int) $this->request->getPost('months_covered');

        if ($remainingMonths <= 0) {
            return redirect()->to('/client/payment')->with('error', 'Your plan has no remaining months left to pay.');
        }

        if ($monthsCovered > $remainingMonths) {
            return redirect()->to('/client/payment/make')->withInput()->with('error', "You can pay for at most {$remainingMonths} more month(s) on this plan.");
        }

        $reference = trim((string) $this->request->getPost('reference_number'));
        $duplicate = (new PaymentModel())
            ->where('reference_number', $reference)
            ->first();

        if ($duplicate) {
            return redirect()->to('/client/payment/make')->withInput()->with('error', 'Duplicate reference number detected. Please verify your reference number.');
        }

        $monthlyFee = (float) ($plan['monthly_fee'] ?? MembershipService::MONTHLY_FEE);
        $amount = round($monthlyFee * $monthsCovered, 2);
        $coverage = (new PaymentService())->projectCoverage($plan, $monthsCovered);

        $payload = [
            'plan_id' => (int) $plan['plan_id'],
            'amount' => $amount,
            'months_covered' => $monthsCovered,
            'payment_date' => date('Y-m-d'),
            'payment_method' => 'gcash',
            'reference_number' => $reference,
            'received_by' => null,
            'branch_id' => (int) ($planHolder['branch_id'] ?? 0),
            'status' => 'pending',
            'coverage_start' => $coverage['start'],
            'coverage_end' => $coverage['end'],
            'remarks' => 'Submitted by client, awaiting branch verification',
        ];

        $paymentModel = new PaymentModel();
        $paymentId = (int) $paymentModel->insert($payload, true);

        if ($paymentId > 0) {
            $paymentModel->update($paymentId, [
                'official_receipt_number' => (new PaymentService())->generateReceiptNumber($paymentId, $payload['payment_date']),
            ]);
        }

        return redirect()->to('/client/payment/history')->with('success', 'GCash payment submitted. Waiting for branch admin verification.');
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
