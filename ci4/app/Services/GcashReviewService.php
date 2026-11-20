<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\CareSync;

/**
 * Verifying a client's GCash payment: the ONE place for the rules behind the review screen.
 *
 *   who         the owner of the GCash account the client paid: a collector (their own account) or - when no collector
 *               account was shown - the Branch Admin (branch account). A Branch Admin can review any GCash payment of
 *               their branch. Nobody else can see the screenshot.
 *   screenshot  required from the client (image only, 5 MB), stored under writable/uploads/payment-proofs, served only
 *               through GcashReviewController to the people above.
 *   warnings    duplicate reference, amount differs from what was expected, paid date older than 7 days or in the future,
 *               no screenshot.
 *   decisions   verify (checklist all ticked + the last 4 digits of the reference typed correctly), received a different
 *               amount (the rest stays due), ask for a clearer screenshot (stays pending), reject (with a reason the client
 *               sees). Every decision is written to payment_reviews.
 *   outcome     a verified payment is 'paid' when the Branch Admin verifies it; when a COLLECTOR verifies it (and
 *               Config\CareSync::$remitGcash is on) it is "collected, awaiting remittance" - the Branch Admin confirms it
 *               with the collector's remittance (see PaymentVerificationService).
 */
class GcashReviewService
{
    public const REJECT_REASONS = [
        'reference_not_found' => 'Reference number not found',
        'amount_mismatch'     => 'Amount does not match',
        'duplicate'           => 'Duplicate payment',
        'unclear_screenshot'  => 'Screenshot is unclear',
        'other'               => 'Other',
    ];

    public const CHECKLIST = [
        'found_history' => 'I found this payment in my GCash history',
        'amount_ok'     => 'The amount matches',
        'datetime_ok'   => 'The date and time match',
        'sender_ok'     => 'The sender matches the client',
    ];

    public const OLD_DAYS = 7;

    private CareSync $cfg;

    public function __construct(?CareSync $cfg = null)
    {
        $this->cfg = $cfg ?? config('CareSync');
    }

    public static function ready(): bool
    {
        $db = db_connect();

        return $db->tableExists('payment_reviews') && $db->fieldExists('paid_at', 'payments');
    }

    // ------------------------------------------------------------------ who

    /**
     * May this person review (see and decide) this GCash payment?
     *
     * @param array<string,mixed> $payment a payments row
     */
    public function canReview(array $payment, int $userId, int $roleId, int $branchId): bool
    {
        if (($payment['payment_method'] ?? '') !== 'gcash' || (int) ($payment['branch_id'] ?? 0) !== $branchId || $branchId <= 0) {
            return false;
        }
        if ($roleId === 2) {
            return true;
        }
        if (! can_collect($userId)) {
            return false;
        }
        $owner = (int) ($payment['receiving_gcash_user_id'] ?? 0);
        if ($owner > 0) {
            return $owner === $userId;
        }
        // Paid to the branch account (the client form recorded a number but no collector): only the Branch Admin decides it.
        if (trim((string) ($payment['receiving_gcash_number'] ?? '')) !== '') {
            return false;
        }
        // An older payment from before owners were recorded: the collector assigned to the client's area.
        $code = (string) (db_connect()->table('plans p')->select('ph.barangay_code')->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')->where('p.plan_id', (int) $payment['plan_id'])->get(1)->getRowArray()['barangay_code'] ?? '');

        return (new CollectorAssignmentService())->canActOnBarangay($userId, $branchId, $code);
    }

    /** A plain pending GCash payment of a regular (not registration) payment. */
    private function reviewable(): \CodeIgniter\Database\BaseBuilder
    {
        return db_connect()->table('payments pay')
            ->select('pay.*, ph.unique_identifier, ph.plan_holder_id, ph.barangay_code, u.first_name, u.last_name, rb.first_name AS owner_first, rb.last_name AS owner_last')
            ->join('plans pl', 'pl.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('users rb', 'rb.user_id = pay.receiving_gcash_user_id', 'left')
            ->where('pay.payment_method', 'gcash')
            ->where(PaymentService::plainPendingSql('pay'), null, false)
            ->where('pay.payment_type !=', 'initial_registration')
            ->where('pay.deleted_at', null);
    }

    /**
     * The review queue of a person: pending GCash payments they may decide, oldest first.
     *
     * @return list<array<string,mixed>>
     */
    public function queue(int $userId, int $roleId, int $branchId, ?string $today = null): array
    {
        if (! self::ready() || $branchId <= 0) {
            return [];
        }
        $rows = $this->reviewable()->where('pay.branch_id', $branchId)->orderBy('pay.created_at', 'ASC')->orderBy('pay.payment_id', 'ASC')->get()->getResultArray();
        $today = date('Y-m-d', strtotime($today ?? 'today'));
        $out = [];
        foreach ($rows as $r) {
            if (! $this->canReview($r, $userId, $roleId, $branchId)) {
                continue;
            }
            $r['client_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
            $r['waiting_days'] = max(0, (int) floor((strtotime($today) - strtotime(substr((string) $r['created_at'], 0, 10))) / 86400));
            $r['reference_grouped'] = self::groupReference((string) ($r['reference_number'] ?? ''));
            $r['has_proof'] = $this->proofExists($r);
            $r['waiting_for_client'] = ! empty($r['proof_requested_at']);
            $out[] = $r;
        }

        return $out;
    }

    public function waitingCount(int $userId, int $roleId, int $branchId): int
    {
        return count(array_filter($this->queue($userId, $roleId, $branchId), static fn (array $r): bool => ! $r['waiting_for_client']));
    }

    /**
     * One payment for the review page (null if it is not reviewable by this person).
     *
     * @return array<string,mixed>|null
     */
    public function detail(int $paymentId, int $userId, int $roleId, int $branchId, ?string $now = null): ?array
    {
        $r = $this->reviewable()->where('pay.payment_id', $paymentId)->get(1)->getRowArray();
        if ($r === null || ! $this->canReview($r, $userId, $roleId, $branchId)) {
            return null;
        }
        $r['client_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
        $r['reference_grouped'] = self::groupReference((string) ($r['reference_number'] ?? ''));
        $r['has_proof'] = $this->proofExists($r);
        $r['warnings'] = $this->warnings($r, $now);
        $r['receiving_label'] = (int) ($r['receiving_gcash_user_id'] ?? 0) > 0
            ? trim((string) $r['owner_first'] . ' ' . (string) $r['owner_last']) . ' (collector) ' . ($r['receiving_gcash_number'] ?: '')
            : 'Branch GCash account ' . ($r['receiving_gcash_number'] ?: '');
        $r['reviews'] = db_connect()->table('payment_reviews rv')->select('rv.*, u.first_name, u.last_name')->join('users u', 'u.user_id = rv.decided_by', 'left')->where('rv.payment_id', $paymentId)->orderBy('rv.review_id', 'DESC')->get()->getResultArray();

        return $r;
    }

    /** "1234567890123" -> "1234 567 890 123" for reading. */
    public static function groupReference(string $ref): string
    {
        $d = preg_replace('/\D+/', '', $ref);

        return $d === '' ? $ref : trim(preg_replace('/^(\d{4})(\d{3})(\d{3})(\d*)$/', '$1 $2 $3 $4', $d));
    }

    // ------------------------------------------------------------------ warnings

    /**
     * Automatic warnings for a payment.
     *
     * @param array<string,mixed> $p
     *
     * @return list<array{code:string, message:string, blocking:bool}>
     */
    public function warnings(array $p, ?string $now = null): array
    {
        $w = [];
        $ts = strtotime($now ?? 'now');
        $ref = trim((string) ($p['reference_number'] ?? ''));
        if ($ref !== '') {
            $dups = db_connect()->table('payments')->select('payment_id, status, awaiting_remittance, plan_id')->where('reference_number', $ref)->where('payment_id !=', (int) $p['payment_id'])->get()->getResultArray();
            if ($dups !== []) {
                $settled = array_filter($dups, static fn (array $d): bool => in_array($d['status'], ['paid', 'verified'], true) || (int) ($d['awaiting_remittance'] ?? 0) === 1);
                $w[] = ['code' => 'duplicate', 'blocking' => $settled !== [], 'message' => 'This reference number is already used on another payment (#' . implode(', #', array_map(static fn (array $d): int => (int) $d['payment_id'], $dups)) . ')' . ($settled !== [] ? ' that was already verified - it cannot be verified again.' : '.')];
            }
        }
        if ($p['amount_sent'] !== null && abs((float) $p['amount_sent'] - (float) $p['amount']) > 0.009) {
            $w[] = ['code' => 'amount', 'blocking' => false, 'message' => 'The client says they sent ' . cs_peso((float) $p['amount_sent']) . ' but ' . cs_peso((float) $p['amount']) . ' was expected.'];
        }
        $paidAt = ! empty($p['paid_at']) ? strtotime((string) $p['paid_at']) : null;
        if ($paidAt !== null) {
            if ($paidAt < $ts - self::OLD_DAYS * 86400) {
                $w[] = ['code' => 'old', 'blocking' => false, 'message' => 'The paid date is more than ' . self::OLD_DAYS . ' days ago (' . date('M j, g:ia', $paidAt) . ').'];
            } elseif ($paidAt > $ts + 300) {
                $w[] = ['code' => 'future', 'blocking' => false, 'message' => 'The paid date is in the future (' . date('M j, g:ia', $paidAt) . ').'];
            }
        }
        if (! $this->proofExists($p)) {
            $w[] = ['code' => 'no_screenshot', 'blocking' => false, 'message' => 'There is no screenshot. Ask the client for one before you verify.'];
        }

        return $w;
    }

    public function proofExists(array $p): bool
    {
        $f = trim((string) ($p['proof_image'] ?? ''));

        return $f !== '' && is_file(WRITEPATH . 'uploads/payment-proofs/' . basename($f));
    }

    // ------------------------------------------------------------------ the client's screenshot

    /**
     * Checks and stores a client's screenshot (image only, 5 MB) under writable/uploads/payment-proofs.
     *
     * @return array{ok:bool, message:string, name?:string}
     */
    public function storeScreenshot(?UploadedFile $file): array
    {
        if ($file === null || ! $file->isValid() || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'message' => 'Upload a screenshot of your GCash payment (image, 5 MB at most).'];
        }
        $check = ServiceProcessService::checkUpload($file, false);
        if (! $check['ok']) {
            return $check;
        }
        $dir = WRITEPATH . 'uploads/payment-proofs';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $check['ext'];
        $file->move($dir, $name);

        return ['ok' => true, 'message' => '', 'name' => $name];
    }

    /**
     * The extra fields a client sends with a GCash payment: when they paid, how much GCash shows, the sender's name; and
     * whose GCash account they paid (the collector shown on the page, or the branch).
     *
     * @return array{ok:bool, message:string, fields?:array<string,mixed>}
     */
    public function clientFields(array $post, ?array $gcashAccount): array
    {
        $paidAt = strtotime(str_replace('T', ' ', trim((string) ($post['paid_at'] ?? ''))));
        if ($paidAt === false) {
            return ['ok' => false, 'message' => 'Enter the date and time shown in your GCash receipt.'];
        }
        if ($paidAt > time() + 300) {
            return ['ok' => false, 'message' => 'The paid date and time cannot be in the future.'];
        }
        $sent = (float) str_replace(',', '', (string) ($post['amount_sent'] ?? ''));
        if ($sent <= 0) {
            return ['ok' => false, 'message' => 'Enter the amount your GCash receipt shows.'];
        }
        $sender = trim((string) ($post['sender_name'] ?? ''));
        if (mb_strlen($sender) < 2) {
            return ['ok' => false, 'message' => 'Enter the name of the GCash account you paid from.'];
        }

        return ['ok' => true, 'message' => '', 'fields' => [
            'paid_at' => date('Y-m-d H:i:s', $paidAt), 'amount_sent' => round($sent, 2), 'sender_name' => mb_substr($sender, 0, 120),
            'receiving_gcash_user_id' => $gcashAccount ? (int) $gcashAccount['user_id'] : null,
            'receiving_gcash_number' => $gcashAccount ? mb_substr((string) preg_replace('/\D+/', '', (string) $gcashAccount['gcash_number']), 0, 20) : null,
        ]];
    }

    /**
     * The client sends a clearer screenshot after the verifier asked for one.
     *
     * @return array{ok:bool, message:string}
     */
    public function reupload(int $paymentId, int $clientUserId, ?UploadedFile $file): array
    {
        $p = db_connect()->table('payments pay')->select('pay.*, ph.user_id AS client_user_id')->join('plans pl', 'pl.plan_id = pay.plan_id', 'inner')->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')->where('pay.payment_id', $paymentId)->get(1)->getRowArray();
        if ($p === null || (int) $p['client_user_id'] !== $clientUserId || $p['status'] !== 'pending' || empty($p['proof_requested_at'])) {
            return ['ok' => false, 'message' => 'No screenshot was asked for on this payment.'];
        }
        $s = $this->storeScreenshot($file);
        if (! $s['ok']) {
            return $s;
        }
        if (! empty($p['proof_image'])) {
            @unlink(WRITEPATH . 'uploads/payment-proofs/' . basename((string) $p['proof_image']));
        }
        db_connect()->table('payments')->where('payment_id', $paymentId)->update(['proof_image' => $s['name'], 'proof_requested_at' => null, 'proof_request_note' => null]);
        $this->notifyReviewers($p, 'The client sent a clearer screenshot for GCash payment #' . $paymentId . '. It is back in your GCash review list.');

        return ['ok' => true, 'message' => 'Thank you. Your new screenshot was sent for review.'];
    }

    // ------------------------------------------------------------------ the decision

    /**
     * @param array<string,mixed> $in decision (verify|different_amount|proof_requested|reject), checklist[] , last4, actual_amount, reason_code, reason_note
     *
     * @return array{ok:bool, message:string}
     */
    public function decide(int $paymentId, int $userId, int $roleId, int $branchId, array $in): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'GCash review is not available yet. Please contact the System Administrator.'];
        }
        $db = db_connect();
        $db->transBegin();
        try {
            $p = $db->query("SELECT * FROM payments WHERE payment_id = ? FOR UPDATE", [$paymentId])->getRowArray();
            if (! $p || $p['payment_method'] !== 'gcash' || $p['status'] !== 'pending' || (int) ($p['awaiting_remittance'] ?? 0) === 1 || ! $this->canReview($p, $userId, $roleId, $branchId)) {
                $db->transRollback();

                return ['ok' => false, 'message' => 'This payment is not waiting for your review (it may already be decided).'];
            }
            $decision = (string) ($in['decision'] ?? '');
            $isCollector = $roleId !== 2;
            $warnings = $this->warnings($p);
            $result = match ($decision) {
                'verify' => $this->doVerify($p, $userId, $isCollector, $in, $warnings, false),
                'different_amount' => $this->doVerify($p, $userId, $isCollector, $in, $warnings, true),
                'proof_requested' => $this->doRequestProof($p, $userId, $in, $warnings),
                'reject' => $this->doReject($p, $userId, $in, $warnings),
                default => ['ok' => false, 'message' => 'Choose what to do with this payment.'],
            };
            if (! $result['ok']) {
                $db->transRollback();

                return $result;
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('A database step failed.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'GcashReviewService::decide failed for payment ' . $paymentId . ' (' . get_class($e) . ')');

            return ['ok' => false, 'message' => 'The decision could not be saved. Nothing was changed - please try again.'];
        }

        return $result;
    }

    /** The checklist and the last 4 digits, checked on the server. @return array{ok:bool, message:string, checklist?:array<string,bool>} */
    private function checkChecklist(array $p, array $in, bool $differentAmount): array
    {
        $ticked = array_map('strval', (array) ($in['checklist'] ?? []));
        $list = [];
        foreach (array_keys(self::CHECKLIST) as $k) {
            $list[$k] = in_array($k, $ticked, true);
        }
        $required = $differentAmount ? ['found_history'] : array_keys(self::CHECKLIST);
        foreach ($required as $k) {
            if (! $list[$k]) {
                return ['ok' => false, 'message' => 'Tick every item of the checklist: "' . self::CHECKLIST[$k] . '".'];
            }
        }
        $digits = preg_replace('/\D+/', '', (string) ($p['reference_number'] ?? ''));
        $typed = preg_replace('/\D+/', '', (string) ($in['last4'] ?? ''));
        if (strlen($digits) < 4 || strlen($typed) !== 4 || $typed !== substr($digits, -4)) {
            return ['ok' => false, 'message' => 'The last 4 digits of the reference do not match. Look at the reference in your own GCash app and type its last 4 digits.'];
        }

        return ['ok' => true, 'message' => '', 'checklist' => $list];
    }

    private function doVerify(array $p, int $userId, bool $isCollector, array $in, array $warnings, bool $differentAmount): array
    {
        foreach ($warnings as $w) {
            if ($w['blocking']) {
                return ['ok' => false, 'message' => $w['message']];
            }
        }
        $holderStatus = strtolower((string) (db_connect()->table('plans p')->select('ph.status')->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')->where('p.plan_id', (int) $p['plan_id'])->get(1)->getRowArray()['status'] ?? ''));
        if ($holderStatus !== 'active') {
            return ['ok' => false, 'message' => 'This plan holder is not active yet - verify the registration payment from Registration approvals.'];
        }
        $chk = $this->checkChecklist($p, $in, $differentAmount);
        if (! $chk['ok']) {
            return $chk;
        }
        $expected = (float) $p['amount'];
        $actual = $expected;
        if ($differentAmount) {
            $actual = round((float) str_replace(',', '', (string) ($in['actual_amount'] ?? '')), 2);
            if ($actual <= 0 || $actual >= $expected - 0.004) {
                return ['ok' => false, 'message' => 'Enter the amount you actually received. It must be more than 0 and less than the ' . cs_peso($expected) . ' expected (the rest stays due).'];
            }
            $fee = (float) (db_connect()->table('plans')->select('monthly_fee')->where('plan_id', (int) $p['plan_id'])->get(1)->getRowArray()['monthly_fee'] ?? 0);
            $update = ['amount' => $actual, 'months_covered' => $fee > 0 ? max(1, (int) ceil($actual / $fee)) : 1];
            db_connect()->table('payments')->where('payment_id', (int) $p['payment_id'])->update($update);
            $p['amount'] = $actual;
            $p['months_covered'] = $update['months_covered'];
        }

        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $remit = $isCollector && $this->cfg->remitGcash && PaymentService::collectedReady();
        $label = $differentAmount ? 'GCash verified with a different amount' : 'GCash verified';
        if ($remit) {
            $db->table('payments')->where('payment_id', (int) $p['payment_id'])->update([
                'awaiting_remittance' => 1, 'collected_at' => $now, 'received_by' => $userId, 'proof_requested_at' => null,
                'remarks' => $label . ' by collector - waiting for the remittance to be confirmed by the office',
            ]);
            $v = new PaymentVerificationService();
            $v->countCollected((int) $p['payment_id'], (int) $p['plan_id'], max(1, (int) $p['months_covered']));
            $v->notifyCollected($p);
        } else {
            $upd = ['status' => 'paid', 'verified_by' => $userId, 'verified_at' => $now, 'proof_requested_at' => null, 'remarks' => $label . ' by ' . ($isCollector ? 'collector' : 'branch admin')];
            if ((int) ($p['received_by'] ?? 0) <= 0) {
                $upd['received_by'] = $userId;
            }
            $db->table('payments')->where('payment_id', (int) $p['payment_id'])->update($upd);
            (new MembershipService())->applyMembershipCoverage((int) $p['plan_id'], max(1, (int) $p['months_covered']));
            (new CycleService())->afterPaymentVerified((int) $p['payment_id'], (int) $p['plan_id']);
            $this->notifyClient($p, 'Your GCash payment of ' . cs_peso($actual) . ' (reference ' . self::groupReference((string) $p['reference_number']) . ') was verified.' . ($differentAmount ? ' We received ' . cs_peso($actual) . ' instead of ' . cs_peso($expected) . '; the remaining ' . cs_peso($expected - $actual) . ' is still due.' : ''), 'payment_approved');
        }
        if ($differentAmount && $remit) {
            $this->notifyClient($p, 'Your GCash payment was checked: we received ' . cs_peso($actual) . ' instead of ' . cs_peso($expected) . '. The remaining ' . cs_peso($expected - $actual) . ' is still due.', 'general');
        }
        $this->audit($p, $userId, $differentAmount ? 'different_amount' : 'verified', $chk['checklist'], true, null, null, $expected, $actual, $warnings);

        return ['ok' => true, 'message' => $remit
            ? 'Verified. It now shows under Remit collections - remit it with your other collections.' . ($differentAmount ? ' Recorded ' . cs_peso($actual) . '; the rest stays due.' : '')
            : ($differentAmount ? 'Verified for ' . cs_peso($actual) . ' - the remaining ' . cs_peso($expected - $actual) . ' stays due.' : 'Payment verified.')];
    }

    private function doRequestProof(array $p, int $userId, array $in, array $warnings): array
    {
        $note = trim((string) ($in['reason_note'] ?? ''));
        $now = date('Y-m-d H:i:s');
        db_connect()->table('payments')->where('payment_id', (int) $p['payment_id'])->update(['proof_requested_at' => $now, 'proof_request_note' => $note !== '' ? mb_substr($note, 0, 255) : null]);
        $this->notifyClient($p, 'Please upload a clearer screenshot of your GCash payment (reference ' . self::groupReference((string) $p['reference_number']) . ').' . ($note !== '' ? ' ' . $note : '') . ' Open Payments and use "Upload a clearer screenshot".', 'general');
        $this->audit($p, $userId, 'proof_requested', null, null, null, $note, (float) $p['amount'], null, $warnings);

        return ['ok' => true, 'message' => 'The client was asked for a clearer screenshot. The payment stays pending.'];
    }

    private function doReject(array $p, int $userId, array $in, array $warnings): array
    {
        $code = (string) ($in['reason_code'] ?? '');
        $note = trim((string) ($in['reason_note'] ?? ''));
        if (! isset(self::REJECT_REASONS[$code])) {
            return ['ok' => false, 'message' => 'Choose why the payment is rejected.'];
        }
        if ($code === 'other' && mb_strlen($note) < 3) {
            return ['ok' => false, 'message' => 'Write the reason when you choose "Other".'];
        }
        $reason = self::REJECT_REASONS[$code] . ($note !== '' ? ': ' . $note : '');
        db_connect()->table('payments')->where('payment_id', (int) $p['payment_id'])->update([
            'status' => 'cancelled', 'verified_by' => $userId, 'verified_at' => date('Y-m-d H:i:s'), 'rejection_code' => $code, 'rejection_reason' => mb_substr($reason, 0, 255),
            'proof_requested_at' => null, 'remarks' => 'GCash rejected: ' . mb_substr($reason, 0, 200),
        ]);
        (new ClaimAvailService())->onPaymentReversed((int) $p['payment_id'], (int) $p['plan_id']);
        $this->notifyClient($p, 'Your GCash payment (reference ' . self::groupReference((string) $p['reference_number']) . ') was not accepted. Reason: ' . $reason . '. Please check and submit it again.', 'payment_rejected');
        $this->audit($p, $userId, 'rejected', null, null, $code, $note, (float) $p['amount'], null, $warnings);

        return ['ok' => true, 'message' => 'Payment rejected. The client was told the reason.'];
    }

    private function audit(array $p, int $userId, string $decision, ?array $checklist, ?bool $last4, ?string $code, ?string $note, ?float $expected, ?float $actual, array $warnings): void
    {
        db_connect()->table('payment_reviews')->insert([
            'payment_id' => (int) $p['payment_id'], 'decision' => $decision, 'decided_by' => $userId, 'decided_at' => date('Y-m-d H:i:s'),
            'checklist_json' => $checklist !== null ? json_encode($checklist) : null, 'last4_ok' => $last4 === null ? null : ($last4 ? 1 : 0),
            'reason_code' => $code, 'reason_note' => $note !== null && $note !== '' ? mb_substr($note, 0, 255) : null,
            'amount_expected' => $expected, 'amount_actual' => $actual, 'warnings_json' => $warnings === [] ? null : json_encode(array_column($warnings, 'code')),
        ]);
        (new ActivityLogService())->log($userId, $decision === 'rejected' ? 'rejected' : 'approved', 'payment', (int) $p['payment_id'], 'GCash review: ' . $decision);
    }

    private function notifyClient(array $p, string $message, string $type): void
    {
        $uid = (int) (db_connect()->table('plans pl')->select('ph.user_id')->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')->where('pl.plan_id', (int) $p['plan_id'])->get(1)->getRowArray()['user_id'] ?? 0);
        (new NotificationService())->notify($uid, $message, $type);
    }

    /** Tells whoever has to review this payment (the owner collector, and the Branch Admins). */
    private function notifyReviewers(array $p, string $message): void
    {
        $notify = new NotificationService();
        $ids = [];
        if ((int) ($p['receiving_gcash_user_id'] ?? 0) > 0) {
            $ids[] = (int) $p['receiving_gcash_user_id'];
        }
        foreach (db_connect()->table('users')->select('user_id')->where('role_id', 2)->where('branch_id', (int) $p['branch_id'])->where('status', 'active')->get()->getResultArray() as $ba) {
            $ids[] = (int) $ba['user_id'];
        }
        foreach (array_unique($ids) as $id) {
            $notify->notify($id, $message, 'general');
        }
    }

    /** A new client GCash payment: tell the account owner (and the Branch Admins when the branch owns the account). */
    public function notifyNewPayment(int $paymentId): void
    {
        $p = db_connect()->table('payments')->where('payment_id', $paymentId)->get(1)->getRowArray();
        if (! $p) {
            return;
        }
        $notify = new NotificationService();
        $owner = (int) ($p['receiving_gcash_user_id'] ?? 0);
        if ($owner > 0) {
            $notify->notify($owner, 'A client sent a GCash payment (' . cs_peso((float) $p['amount']) . ') for you to verify. Open GCash review.', 'general');

            return;
        }
        foreach (db_connect()->table('users')->select('user_id')->where('role_id', 2)->where('branch_id', (int) $p['branch_id'])->where('status', 'active')->get()->getResultArray() as $ba) {
            $notify->notify((int) $ba['user_id'], 'A client sent a GCash payment (' . cs_peso((float) $p['amount']) . ') to the branch account. Open GCash review.', 'general');
        }
    }
}
