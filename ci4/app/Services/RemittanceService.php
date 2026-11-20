<?php

namespace App\Services;

use Config\CareSync;

/**
 * Collector remittances and commission (Part E) - the ONE place for the rules.
 *
 *  1. What a collector collects in the field - cash, or a client's GCash reference they checked on their own GCash
 *     account (Config\CareSync::$remitGcash) - is "collected, awaiting remittance" (status 'pending' +
 *     payments.awaiting_remittance = 1). The client has paid, so it already counts for the plan ledger / overdue.
 *     Collections not yet in a remittance are "available". Any number of partial remittances per month is fine.
 *  2. The collector picks some of them (default: all) and remits: gross = their total; commission = rate x the commission base;
 *     net to hand over = gross - commission. The commission base depends on Config\CareSync::$commissionAppliesTo:
 *       'plan_only'        the plan-contribution part of each collection (default: package down payments and
 *                          instalments earn nothing)
 *       'all_collections'  the whole collection
 *     The rate and the rule are copied onto the remittance, so a later change of the setting never rewrites it.
 *  3. The Branch Admin of that branch confirms it (the money was received): in ONE transaction every payment of that
 *     remittance becomes 'paid' through the normal verification path (PaymentVerificationService). Or they report a
 *     discrepancy (what was actually received and why): only that remittance goes on hold, its payments stay awaiting.
 *     A discrepancy can still be confirmed later once it is sorted out.
 *  4. Commission is EARNED only when the remittance is confirmed. Submitted and discrepancy remittances are shown as
 *     pending / on hold.
 *
 * Everything money-related is worked out here from the payments; the browser only names which payments.
 */
class RemittanceService
{
    public const NOTE_MIN = 5;

    private CareSync $cfg;

    public function __construct(?CareSync $cfg = null)
    {
        $this->cfg = $cfg ?? config('CareSync');
    }

    public static function ready(): bool
    {
        $db = db_connect();

        return $db->tableExists('remittances') && $db->fieldExists('remittance_id', 'payments') && PaymentService::collectedReady();
    }

    public function rate(): float
    {
        return (float) $this->cfg->commissionRate;
    }

    public function appliesTo(): string
    {
        return $this->cfg->commissionAppliesTo === 'all_collections' ? 'all_collections' : 'plan_only';
    }

    /** Plain words for the rule in force, for the pages. */
    public function ruleText(?float $rate = null, ?string $appliesTo = null): string
    {
        $pct = rtrim(rtrim(number_format(($rate ?? $this->rate()) * 100, 2), '0'), '.') . '%';

        return $pct . ' of ' . (($appliesTo ?? $this->appliesTo()) === 'all_collections' ? 'everything you collect' : 'the plan contributions you collect (package payments earn no commission)');
    }

    /** What commission is worked out on for one collection under a rule. */
    public static function baseFor(float $amount, float $packagePart, string $appliesTo): float
    {
        return round($appliesTo === 'all_collections' ? $amount : max(0.0, $amount - $packagePart), 2);
    }

    /**
     * Collections the collector could remit now: cash, verified, theirs, not in a remittance. Each row carries the
     * plan / package split and the commission it would earn under the current rule.
     *
     * @return list<array<string,mixed>>
     */
    public function available(int $collectorUserId, int $branchId): array
    {
        if (! self::ready()) {
            return [];
        }
        $rows = $this->paymentQuery()
            ->where('pay.received_by', $collectorUserId)
            ->where('pay.branch_id', $branchId)
            ->whereIn('pay.payment_method', $this->remittableMethods())
            ->where('pay.status', 'pending')
            ->where('pay.awaiting_remittance', 1)
            ->where('pay.remittance_id', null)
            ->orderBy('pay.payment_date', 'ASC')->orderBy('pay.payment_id', 'ASC')
            ->get()->getResultArray();

        return $this->decorate($rows, $this->rate(), $this->appliesTo());
    }

    /** Payment methods that go into a remittance (GCash only when the setting says so). @return list<string> */
    public function remittableMethods(): array
    {
        return $this->cfg->remitGcash ? ['cash', 'gcash'] : ['cash'];
    }

    /**
     * What a remittance of these payments would be (nothing is saved).
     *
     * @param list<int> $paymentIds
     *
     * @return array{ok:bool, message:string, rows:list<array<string,mixed>>, gross:float, base:float, commission:float, net:float, rate:float, applies_to:string}
     */
    public function preview(int $collectorUserId, int $branchId, array $paymentIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $paymentIds), static fn (int $i): bool => $i > 0)));
        $out = ['ok' => false, 'message' => '', 'rows' => [], 'cash' => 0.0, 'gcash' => 0.0, 'gross' => 0.0, 'base' => 0.0, 'commission' => 0.0, 'net' => 0.0, 'rate' => $this->rate(), 'applies_to' => $this->appliesTo()];
        if ($ids === []) {
            $out['message'] = 'Select at least one collection to remit.';

            return $out;
        }
        $byId = [];
        foreach ($this->available($collectorUserId, $branchId) as $r) {
            $byId[(int) $r['payment_id']] = $r;
        }
        foreach ($ids as $id) {
            if (! isset($byId[$id])) {
                $out['message'] = 'One of the selected collections is not available to remit (it may already be in a remittance, or it is not yours).';

                return $out;
            }
            $out['rows'][] = $byId[$id];
        }
        foreach ($out['rows'] as $r) {
            $out['gross'] += (float) $r['amount'];
            $out['base'] += (float) $r['commission_base'];
            $out[$r['payment_method'] === 'gcash' ? 'gcash' : 'cash'] += (float) $r['amount'];
        }
        $out['cash'] = round($out['cash'], 2);
        $out['gcash'] = round($out['gcash'], 2);
        $out['gross'] = round($out['gross'], 2);
        $out['base'] = round($out['base'], 2);
        $out['commission'] = round($out['base'] * $out['rate'], 2);
        $out['net'] = round($out['gross'] - $out['commission'], 2);
        $out['ok'] = true;

        return $out;
    }

    /**
     * The collector remits the selected collections.
     *
     * @param list<int> $paymentIds
     *
     * @return array{ok:bool, message:string, remittance_id?:int}
     */
    public function submit(int $collectorUserId, int $branchId, array $paymentIds, string $note = ''): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'Remittances are not available yet. Please contact the System Administrator.'];
        }
        $db = db_connect();
        $db->transBegin();
        try {
            $ids = array_values(array_unique(array_filter(array_map('intval', $paymentIds), static fn (int $i): bool => $i > 0)));
            if ($ids !== []) {
                // Lock the rows so two submissions can never take the same payment.
                $db->query('SELECT payment_id FROM payments WHERE payment_id IN (' . implode(',', $ids) . ') FOR UPDATE');
            }
            $p = $this->preview($collectorUserId, $branchId, $ids);
            if (! $p['ok']) {
                $db->transRollback();

                return ['ok' => false, 'message' => $p['message']];
            }
            $now = date('Y-m-d H:i:s');
            $db->table('remittances')->insert([
                'branch_id' => $branchId,
                'collector_user_id' => $collectorUserId,
                'status' => 'submitted',
                'payment_count' => count($p['rows']),
                'gross_amount' => $p['gross'],
                'cash_amount' => $p['cash'],
                'gcash_amount' => $p['gcash'],
                'commission_rate' => $p['rate'],
                'commission_applies_to' => $p['applies_to'],
                'commission_base' => $p['base'],
                'commission_amount' => $p['commission'],
                'net_amount' => $p['net'],
                'collector_note' => trim($note) !== '' ? mb_substr(trim($note), 0, 255) : null,
                'submitted_at' => $now,
            ]);
            $rid = (int) $db->insertID();
            $db->table('payments')->whereIn('payment_id', $ids)->update(['remittance_id' => $rid]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Could not save the remittance.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'RemittanceService::submit failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'The remittance could not be saved. Nothing was changed - please try again.'];
        }

        $collector = $db->table('users')->select('first_name, last_name')->where('user_id', $collectorUserId)->get(1)->getRowArray() ?: [];
        $notify = new NotificationService();
        foreach ($db->table('users')->select('user_id')->where('role_id', 2)->where('branch_id', $branchId)->where('status', 'active')->where('deleted_at', null)->get()->getResultArray() as $ba) {
            $notify->notify((int) $ba['user_id'], trim((string) ($collector['first_name'] ?? '') . ' ' . (string) ($collector['last_name'] ?? '')) . ' submitted remittance #' . $rid . ' (' . cs_peso($p['net']) . ' to hand over, ' . cs_peso($p['gross']) . ' collected). Please confirm it when you receive the cash.', 'general');
        }

        return ['ok' => true, 'message' => 'Remittance #' . $rid . ' submitted: hand ' . cs_peso($p['net']) . ' to your Branch Admin (you keep ' . cs_peso($p['commission']) . ' commission once they confirm).', 'remittance_id' => $rid];
    }

    /** The collector withdraws a remittance nobody has confirmed yet; its collections become available again. */
    public function cancel(int $remittanceId, int $collectorUserId): array
    {
        $db = db_connect();
        $db->transBegin();
        $r = $db->query('SELECT * FROM remittances WHERE remittance_id = ? FOR UPDATE', [$remittanceId])->getRowArray();
        if (! $r || (int) $r['collector_user_id'] !== $collectorUserId) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'Remittance not found.'];
        }
        if ($r['status'] !== 'submitted') {
            $db->transRollback();

            return ['ok' => false, 'message' => 'Only a remittance that is still waiting for confirmation can be cancelled.'];
        }
        $db->table('payments')->where('remittance_id', $remittanceId)->update(['remittance_id' => null]);
        $db->table('remittances')->where('remittance_id', $remittanceId)->update(['status' => 'cancelled']);
        $db->transCommit();

        return ['ok' => true, 'message' => 'Remittance #' . $remittanceId . ' cancelled. Its collections can be remitted again.'];
    }

    /**
     * The Branch Admin confirms the money was received (also resolves a discrepancy). In ONE transaction the remittance
     * becomes 'confirmed' and each of its payments becomes 'paid' through the normal verification path - if any
     * payment cannot be confirmed nothing at all changes.
     */
    public function confirm(int $remittanceId, int $branchId, int $byUserId): array
    {
        $db = db_connect();
        $db->transBegin();
        $r = $db->query('SELECT * FROM remittances WHERE remittance_id = ? FOR UPDATE', [$remittanceId])->getRowArray();
        if (! $r || (int) $r['branch_id'] !== $branchId) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'Remittance not found in your branch.'];
        }
        if (! in_array($r['status'], ['submitted', 'discrepancy'], true)) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'This remittance is already ' . $r['status'] . '.'];
        }
        try {
            $verification = new PaymentVerificationService();
            $pays = $db->query('SELECT payment_id, status, awaiting_remittance FROM payments WHERE remittance_id = ? ORDER BY payment_id FOR UPDATE', [$remittanceId])->getResultArray();
            // Check everything first: either all of its payments can be confirmed or none is touched.
            if (count($pays) !== (int) $r['payment_count']) {
                throw new \RuntimeException('The remittance does not hold the payments it was made with.');
            }
            foreach ($pays as $pay) {
                if ($pay['status'] !== 'pending' || (int) $pay['awaiting_remittance'] !== 1) {
                    throw new \RuntimeException('Payment #' . (int) $pay['payment_id'] . ' is not waiting for confirmation.');
                }
            }
            foreach ($pays as $pay) {
                $verification->confirmCollected((int) $pay['payment_id'], $byUserId, 'Confirmed with remittance #' . $remittanceId);
            }
            $db->table('remittances')->where('remittance_id', $remittanceId)->update([
                'status' => 'confirmed', 'confirmed_by' => $byUserId, 'confirmed_at' => date('Y-m-d H:i:s'),
                'received_amount' => $r['received_amount'] !== null ? $r['received_amount'] : $r['net_amount'],
            ]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('A database step failed.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'RemittanceService::confirm failed for remittance ' . $remittanceId . ': ' . $e->getMessage());

            return ['ok' => false, 'message' => 'The remittance could not be confirmed. Nothing was changed - please try again.'];
        }
        (new NotificationService())->notify((int) $r['collector_user_id'], 'Remittance #' . $remittanceId . ' was confirmed. Your commission of ' . cs_peso((float) $r['commission_amount']) . ' is now earned.', 'general');

        return ['ok' => true, 'message' => 'Remittance #' . $remittanceId . ' confirmed.'];
    }

    /** The cash received was not what was expected. A note is required; the commission stays on hold. */
    public function reportDiscrepancy(int $remittanceId, int $branchId, int $byUserId, float $received, string $note): array
    {
        $note = trim($note);
        if (mb_strlen($note) < self::NOTE_MIN) {
            return ['ok' => false, 'message' => 'Say what is different (at least ' . self::NOTE_MIN . ' characters).'];
        }
        if ($received < 0) {
            return ['ok' => false, 'message' => 'The amount received cannot be negative.'];
        }
        $db = db_connect();
        $db->transBegin();
        $r = $db->query('SELECT * FROM remittances WHERE remittance_id = ? FOR UPDATE', [$remittanceId])->getRowArray();
        if (! $r || (int) $r['branch_id'] !== $branchId) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'Remittance not found in your branch.'];
        }
        if (! in_array($r['status'], ['submitted', 'discrepancy'], true)) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'This remittance is already ' . $r['status'] . '.'];
        }
        if (abs($received - (float) $r['net_amount']) < 0.005) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'The amount received equals the amount expected - confirm the remittance instead.'];
        }
        $db->table('remittances')->where('remittance_id', $remittanceId)->update([
            'status' => 'discrepancy', 'received_amount' => round($received, 2), 'discrepancy_note' => mb_substr($note, 0, 500),
            'discrepancy_by' => $byUserId, 'discrepancy_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();
        (new NotificationService())->notify((int) $r['collector_user_id'], 'Remittance #' . $remittanceId . ': your Branch Admin received ' . cs_peso($received) . ' instead of ' . cs_peso((float) $r['net_amount']) . '. Reason given: ' . $note . '. Your commission on it is on hold until this is settled.', 'general');

        return ['ok' => true, 'message' => 'Discrepancy recorded on remittance #' . $remittanceId . '. The collector was told.'];
    }

    /**
     * Remittances, newest first, with the collector's name.
     *
     * @param array{branch_id?:int, collector_id?:int, status?:string, from?:string, to?:string} $f dates filter the submission date
     *
     * @return list<array<string,mixed>>
     */
    public function list(array $f): array
    {
        if (! self::ready()) {
            return [];
        }
        $b = db_connect()->table('remittances r')
            ->select('r.*, u.first_name, u.last_name, cb.first_name AS confirmed_first, cb.last_name AS confirmed_last, br.branch_name')
            ->join('users u', 'u.user_id = r.collector_user_id', 'inner')
            ->join('users cb', 'cb.user_id = r.confirmed_by', 'left')
            ->join('branches br', 'br.branch_id = r.branch_id', 'left');
        if ((int) ($f['branch_id'] ?? 0) > 0) {
            $b->where('r.branch_id', (int) $f['branch_id']);
        }
        if ((int) ($f['collector_id'] ?? 0) > 0) {
            $b->where('r.collector_user_id', (int) $f['collector_id']);
        }
        if (in_array((string) ($f['status'] ?? ''), ['submitted', 'confirmed', 'discrepancy', 'cancelled'], true)) {
            $b->where('r.status', (string) $f['status']);
        }
        if (($f['from'] ?? '') !== '') {
            $b->where('DATE(r.submitted_at) >=', (string) $f['from']);
        }
        if (($f['to'] ?? '') !== '') {
            $b->where('DATE(r.submitted_at) <=', (string) $f['to']);
        }
        $rows = $b->orderBy('r.remittance_id', 'DESC')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['collector_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
            $r['status_label'] = ['submitted' => 'Waiting for confirmation', 'confirmed' => 'Confirmed', 'discrepancy' => 'Discrepancy', 'cancelled' => 'Cancelled'][$r['status']] ?? (string) $r['status'];
        }
        unset($r);

        return $rows;
    }

    public function find(int $remittanceId): ?array
    {
        $rows = $this->list([]);
        foreach ($rows as $r) {
            if ((int) $r['remittance_id'] === $remittanceId) {
                return $r;
            }
        }

        return null;
    }

    /**
     * The collections inside a remittance, split plan / package, with the commission under the rule the remittance was made with.
     *
     * @return list<array<string,mixed>>
     */
    public function lines(int $remittanceId): array
    {
        $r = db_connect()->table('remittances')->where('remittance_id', $remittanceId)->get(1)->getRowArray();
        if (! $r) {
            return [];
        }
        $rows = $this->paymentQuery()->where('pay.remittance_id', $remittanceId)->orderBy('pay.payment_date', 'ASC')->orderBy('pay.payment_id', 'ASC')->get()->getResultArray();

        return $this->decorate($rows, (float) $r['commission_rate'], (string) $r['commission_applies_to']);
    }

    /**
     * A collector's commission position for a period (dates filter the submission date of remittances; the
     * "available" figures are always right now).
     *
     * @return array{earned:float, pending:float, on_hold:float, remitted_gross:float, confirmed_net:float, available_gross:float, available_commission:float, available_count:int}
     */
    public function summary(int $collectorUserId, int $branchId, ?string $from = null, ?string $to = null): array
    {
        $out = ['earned' => 0.0, 'pending' => 0.0, 'on_hold' => 0.0, 'remitted_gross' => 0.0, 'confirmed_net' => 0.0, 'available_gross' => 0.0, 'available_commission' => 0.0, 'available_count' => 0];
        if (! self::ready()) {
            return $out;
        }
        foreach ($this->list(['collector_id' => $collectorUserId, 'from' => (string) $from, 'to' => (string) $to]) as $r) {
            if ($r['status'] === 'cancelled') {
                continue;
            }
            $out['remitted_gross'] += (float) $r['gross_amount'];
            if ($r['status'] === 'confirmed') {
                $out['earned'] += (float) $r['commission_amount'];
                $out['confirmed_net'] += (float) $r['net_amount'];
            } elseif ($r['status'] === 'submitted') {
                $out['pending'] += (float) $r['commission_amount'];
            } else {
                $out['on_hold'] += (float) $r['commission_amount'];
            }
        }
        foreach ($this->available($collectorUserId, $branchId) as $a) {
            $out['available_gross'] += (float) $a['amount'];
            $out['available_commission'] += (float) $a['commission'];
            $out['available_count']++;
        }
        foreach ($out as $k => $v) {
            $out[$k] = is_float($v) ? round($v, 2) : $v;
        }

        return $out;
    }

    /**
     * Commission report rows per collector for a branch (null = all) and a period (submission date):
     * remittances split by state, and what is earned (confirmed only).
     *
     * @return list<array<string,mixed>>
     */
    public function reportRows(?int $branchId, ?string $from, ?string $to, string $search = ''): array
    {
        $by = [];
        foreach ($this->list(['branch_id' => (int) $branchId, 'from' => (string) $from, 'to' => (string) $to]) as $r) {
            if ($r['status'] === 'cancelled') {
                continue;
            }
            $k = (int) $r['collector_user_id'];
            $by[$k] ??= ['user_id' => $k, 'first_name' => $r['first_name'], 'last_name' => $r['last_name'], 'branch_name' => $r['branch_name'], 'remittances' => 0, 'gross' => 0.0, 'commission_earned' => 0.0, 'commission_pending' => 0.0, 'commission_on_hold' => 0.0, 'net_confirmed' => 0.0, 'net_pending' => 0.0, 'discrepancies' => 0];
            $by[$k]['remittances']++;
            $by[$k]['gross'] += (float) $r['gross_amount'];
            if ($r['status'] === 'confirmed') {
                $by[$k]['commission_earned'] += (float) $r['commission_amount'];
                $by[$k]['net_confirmed'] += (float) $r['net_amount'];
            } elseif ($r['status'] === 'submitted') {
                $by[$k]['commission_pending'] += (float) $r['commission_amount'];
                $by[$k]['net_pending'] += (float) $r['net_amount'];
            } else {
                $by[$k]['commission_on_hold'] += (float) $r['commission_amount'];
                $by[$k]['net_pending'] += (float) $r['net_amount'];
                $by[$k]['discrepancies']++;
            }
        }
        $rows = array_values($by);
        $s = strtolower(trim($search));
        foreach ($rows as $i => &$r) {
            foreach (['gross', 'commission_earned', 'commission_pending', 'commission_on_hold', 'net_confirmed', 'net_pending'] as $k) {
                $r[$k] = round($r[$k], 2);
            }
            $r['collector_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
            if ($s !== '' && ! str_contains(strtolower($r['collector_name']), $s)) {
                unset($rows[$i]);
            }
        }
        unset($r);
        usort($rows, static fn ($a, $b) => $b['gross'] <=> $a['gross']);

        return array_values($rows);
    }

    /**
     * Confirmed remittances of the branch with this name - for the admin branch table.
     *
     * @return array{commission:float, net:float}
     */
    public function confirmedForBranchName(string $branchName): array
    {
        if (! self::ready()) {
            return ['commission' => 0.0, 'net' => 0.0];
        }
        $row = db_connect()->table('remittances r')->select('COALESCE(SUM(r.commission_amount),0) AS c, COALESCE(SUM(r.net_amount),0) AS n', false)->join('branches b', 'b.branch_id = r.branch_id', 'inner')
            ->where('b.branch_name', $branchName)->where('r.status', 'confirmed')->get()->getRowArray();

        return ['commission' => round((float) ($row['c'] ?? 0), 2), 'net' => round((float) ($row['n'] ?? 0), 2)];
    }

    /** Pending remittances waiting for a Branch Admin (sidebar badge / dashboard). */
    public function pendingCount(int $branchId): int
    {
        if (! self::ready()) {
            return 0;
        }

        return (int) db_connect()->table('remittances')->where('branch_id', $branchId)->whereIn('status', ['submitted', 'discrepancy'])->countAllResults();
    }

    // ------------------------------------------------------------------ internals

    private function paymentQuery()
    {
        return db_connect()->table('payments pay')
            ->select("pay.payment_id, pay.payment_date, pay.payment_method, pay.reference_number, pay.collected_at, pay.status, pay.amount, pay.official_receipt_number, pay.remittance_id, pay.plan_id, cu.first_name, cu.last_name, ph.unique_identifier,"
                . " (SELECT COALESCE(SUM(al.amount),0) FROM payment_allocations al WHERE al.payment_id = pay.payment_id AND al.target_type IN ('avail_instalment','avail_down_payment')) AS package_part", false)
            ->join('plans pl', 'pl.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users cu', 'cu.user_id = ph.user_id', 'inner')
            ->where('pay.deleted_at', null);
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    private function decorate(array $rows, float $rate, string $appliesTo): array
    {
        foreach ($rows as &$r) {
            $r['amount'] = (float) $r['amount'];
            $r['package_part'] = round((float) $r['package_part'], 2);
            $r['plan_part'] = round($r['amount'] - $r['package_part'], 2);
            $r['commission_base'] = self::baseFor($r['amount'], $r['package_part'], $appliesTo);
            $r['commission'] = round($r['commission_base'] * $rate, 2);
            $r['client_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
        }
        unset($r);

        return $rows;
    }
}
