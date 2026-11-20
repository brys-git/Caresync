<?php

namespace App\Services;

use Config\CareSync;

/**
 * A collector's month: who they had to collect from, what happened with each, and closing the month.
 *
 *   members       every active plan holder in the collector's area (CollectorAssignmentService)
 *   state         per member, for a month (by when the collector collected):
 *                   collected      collected, not yet in a remittance
 *                   remitted       in a remittance the Branch Admin has not confirmed yet (submitted / discrepancy)
 *                   confirmed      in a confirmed remittance
 *                   not_collected  marked "not collected" with a reason (collection_marks)
 *                   remaining      none of the above yet
 *   close         allowed only when every member is collected or marked not collected AND every collected payment of
 *                 the month is in a CONFIRMED remittance. It writes one monthly_collection_summaries row.
 *   flags         money collected but not remitted within CareSync::$remittanceMaxDays, and months not closed by
 *                 CareSync::$monthCloseDueDay of the next month.
 *
 * Everything is checked on the server; the browser only names the member, month and reason.
 */
class CollectionMonthService
{
    public const REASONS = [
        'not_home'            => 'Not home',
        'refused'             => 'Refused',
        'will_pay_next_month' => 'Will pay next month',
        'other'               => 'Other',
    ];

    private CareSync $cfg;

    public function __construct(?CareSync $cfg = null)
    {
        $this->cfg = $cfg ?? config('CareSync');
    }

    public static function ready(): bool
    {
        $db = db_connect();

        return $db->tableExists('collection_marks') && $db->tableExists('monthly_collection_summaries') && PaymentService::collectedReady();
    }

    /** 'YYYY-MM' for any date-ish input, or the current month. */
    public static function monthKey(?string $month = null, ?string $today = null): string
    {
        $m = trim((string) $month);

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m) ? $m : date('Y-m', strtotime($today ?? 'today'));
    }

    /** 'YYYY-MM' if the input is one, else '' (report filters). */
    public static function monthKeyOrEmpty(string $month): string
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', trim($month)) ? trim($month) : '';
    }

    /** @return list<string> */
    public static function csvHeaders(): array
    {
        return ['Month', 'Collector', 'Branch', 'Plan holders assigned', 'Collected', 'Not collected', 'Cash', 'GCash', 'Gross', 'Commission', 'Net remitted', 'Remittances', 'Closed at', 'Not collected (who and why)'];
    }

    /** @return list<string> */
    public static function csvRow(array $r): array
    {
        $nc = implode('; ', array_map(static fn (array $n): string => (string) $n['name'] . ' - ' . (string) $n['reason'], $r['not_collected'] ?? []));

        return [
            (string) $r['month_label'], (string) $r['collector_name'], (string) ($r['branch_name'] ?? ''), (string) $r['members_assigned'], (string) $r['collected_count'], (string) $r['not_collected_count'],
            number_format((float) $r['cash_amount'], 2, '.', ''), number_format((float) $r['gcash_amount'], 2, '.', ''), number_format((float) $r['gross_amount'], 2, '.', ''),
            number_format((float) $r['commission_amount'], 2, '.', ''), number_format((float) $r['net_amount'], 2, '.', ''), (string) $r['remittance_count'], (string) $r['closed_at'], $nc,
        ];
    }

    // ------------------------------------------------------------------ members and progress

    /**
     * Active plan holders in the collector's area (a branch-wide assignment = the whole branch; no assignment = nobody).
     *
     * @return list<array{plan_holder_id:int, plan_id:int, name:string, unique_identifier:string, barangay:string}>
     */
    public function assignedMembers(int $collectorId, int $branchId): array
    {
        $assign = new CollectorAssignmentService();
        $wide   = $assign->hasBranchWideAssignment($collectorId, $branchId);
        $codes  = $assign->assignedBarangays($collectorId, $branchId);
        if (! $wide && $codes === []) {
            return [];
        }

        $b = db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.address_barangay, u.first_name, u.last_name, MAX(pl.plan_id) AS plan_id', false)
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('plans pl', "pl.plan_holder_id = ph.plan_holder_id AND pl.status = 'active'", 'inner')
            ->where('ph.status', 'active')
            ->where('ph.branch_id', $branchId);
        if (! $wide) {
            $b->whereIn('ph.barangay_code', $codes);
        }
        $rows = $b->groupBy('ph.plan_holder_id')->orderBy('u.last_name', 'ASC')->orderBy('u.first_name', 'ASC')->get()->getResultArray();

        return array_map(static fn (array $r): array => [
            'plan_holder_id' => (int) $r['plan_holder_id'], 'plan_id' => (int) $r['plan_id'],
            'name' => trim((string) $r['first_name'] . ' ' . (string) $r['last_name']),
            'unique_identifier' => (string) ($r['unique_identifier'] ?? ''), 'barangay' => (string) ($r['address_barangay'] ?? ''),
        ], $rows);
    }

    /**
     * The collector's collections of a month (by the day they collected), with where each one is in a remittance.
     *
     * @return list<array<string,mixed>>
     */
    public function monthPayments(int $collectorId, int $branchId, string $month): array
    {
        $first = $month . '-01';
        $last  = date('Y-m-t', strtotime($first));

        return db_connect()->table('payments pay')
            ->select('pay.payment_id, pay.amount, pay.payment_method, pay.status, pay.awaiting_remittance, pay.remittance_id, pay.collected_at, pl.plan_holder_id, r.status AS remittance_status', false)
            ->join('plans pl', 'pl.plan_id = pay.plan_id', 'inner')
            ->join('remittances r', 'r.remittance_id = pay.remittance_id', 'left')
            ->where('pay.received_by', $collectorId)
            ->where('pay.branch_id', $branchId)
            ->where('pay.collected_at IS NOT NULL', null, false)
            ->where('pay.status !=', 'cancelled')
            ->where('pay.deleted_at', null)
            ->where('DATE(pay.collected_at) >=', $first)
            ->where('DATE(pay.collected_at) <=', $last)
            ->orderBy('pay.collected_at', 'ASC')->orderBy('pay.payment_id', 'ASC')
            ->get()->getResultArray();
    }

    /** @return array<int,array{reason_code:string, reason_note:?string, marked_at:?string}> by plan_holder_id */
    public function marks(int $collectorId, string $month): array
    {
        $out = [];
        foreach (db_connect()->table('collection_marks')->where('collector_user_id', $collectorId)->where('month', $month . '-01')->get()->getResultArray() as $r) {
            $out[(int) $r['plan_holder_id']] = $r;
        }

        return $out;
    }

    /**
     * @return array{month:string, month_label:string, members:list<array<string,mixed>>, counts:array<string,int>, summary_text:string, closed:?array<string,mixed>, blockers:list<string>, can_close:bool}
     */
    public function progress(int $collectorId, int $branchId, ?string $month = null, ?string $today = null): array
    {
        $month    = self::monthKey($month, $today);
        $members  = $this->assignedMembers($collectorId, $branchId);
        $payments = $this->monthPayments($collectorId, $branchId, $month);
        $marks    = $this->marks($collectorId, $month);

        $byHolder = [];
        foreach ($payments as $p) {
            $byHolder[(int) $p['plan_holder_id']][] = $p;
        }

        $counts = ['assigned' => count($members), 'collected' => 0, 'collected_only' => 0, 'remitted' => 0, 'confirmed' => 0, 'not_collected' => 0, 'remaining' => 0];
        $rows = [];
        foreach ($members as $m) {
            $hid = $m['plan_holder_id'];
            $m['amount'] = 0.0;
            $m['reason'] = null;
            if (isset($byHolder[$hid])) {
                $state = 'confirmed';
                foreach ($byHolder[$hid] as $p) {
                    $m['amount'] += (float) $p['amount'];
                    if ($p['remittance_id'] === null) {
                        $state = 'collected';
                    } elseif ($state !== 'collected' && $p['remittance_status'] !== 'confirmed') {
                        $state = 'remitted';
                    }
                }
                $counts['collected']++;
                $counts[$state === 'collected' ? 'collected_only' : $state]++;
            } elseif (isset($marks[$hid])) {
                $state = 'not_collected';
                $m['reason'] = (self::REASONS[$marks[$hid]['reason_code']] ?? $marks[$hid]['reason_code']) . (trim((string) $marks[$hid]['reason_note']) !== '' ? ' - ' . $marks[$hid]['reason_note'] : '');
                $m['reason_code'] = $marks[$hid]['reason_code'];
                $counts['not_collected']++;
            } else {
                $state = 'remaining';
                $counts['remaining']++;
            }
            $m['state'] = $state;
            $m['amount'] = round($m['amount'], 2);
            $rows[] = $m;
        }

        $closed = db_connect()->table('monthly_collection_summaries')->where('collector_user_id', $collectorId)->where('month', $month . '-01')->get(1)->getRowArray() ?: null;

        $blockers = [];
        if ($counts['remaining'] > 0) {
            $blockers[] = $counts['remaining'] . ' plan holder' . ($counts['remaining'] === 1 ? ' is' : 's are') . ' not collected or marked "not collected" yet.';
        }
        $unremitted = count(array_filter($payments, static fn (array $p): bool => $p['remittance_id'] === null));
        $unconfirmed = count(array_filter($payments, static fn (array $p): bool => $p['remittance_id'] !== null && $p['remittance_status'] !== 'confirmed'));
        if ($unremitted > 0) {
            $blockers[] = $unremitted . ' collected payment' . ($unremitted === 1 ? ' is' : 's are') . ' not in a remittance yet.';
        }
        if ($unconfirmed > 0) {
            $blockers[] = $unconfirmed . ' collected payment' . ($unconfirmed === 1 ? ' is' : 's are') . ' in a remittance the Branch Admin has not confirmed yet.';
        }
        if (strcmp($month, date('Y-m', strtotime($today ?? 'today'))) > 0) {
            $blockers[] = 'This month has not started.';
        }

        return [
            'month' => $month, 'month_label' => date('F Y', strtotime($month . '-01')), 'members' => $rows, 'counts' => $counts,
            'summary_text' => $counts['collected'] . ' of ' . $counts['assigned'] . ' collected, ' . $counts['not_collected'] . ' not collected, ' . $counts['remaining'] . ' remaining',
            'closed' => $closed, 'blockers' => $blockers, 'can_close' => $closed === null && $blockers === [],
        ];
    }

    // ------------------------------------------------------------------ "not collected"

    /** @return array{ok:bool, message:string} */
    public function markNotCollected(int $collectorId, int $branchId, int $planHolderId, string $month, string $reasonCode, string $note): array
    {
        $month = self::monthKey($month);
        $note  = trim($note);
        if (! isset(self::REASONS[$reasonCode])) {
            return ['ok' => false, 'message' => 'Choose why the payment was not collected.'];
        }
        if ($reasonCode === 'other' && mb_strlen($note) < 3) {
            return ['ok' => false, 'message' => 'Write the reason when you choose "Other".'];
        }
        if ($this->isClosed($collectorId, $month)) {
            return ['ok' => false, 'message' => 'That month is already closed.'];
        }
        $member = null;
        foreach ($this->assignedMembers($collectorId, $branchId) as $m) {
            if ($m['plan_holder_id'] === $planHolderId) {
                $member = $m;
                break;
            }
        }
        if ($member === null) {
            return ['ok' => false, 'message' => 'That plan holder is not in your collection area.'];
        }
        foreach ($this->monthPayments($collectorId, $branchId, $month) as $p) {
            if ((int) $p['plan_holder_id'] === $planHolderId) {
                return ['ok' => false, 'message' => $member['name'] . ' already has a collection this month.'];
            }
        }

        $db = db_connect();
        $db->query(
            'INSERT INTO collection_marks (collector_user_id, branch_id, plan_holder_id, month, reason_code, reason_note, marked_at) VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE reason_code = VALUES(reason_code), reason_note = VALUES(reason_note), marked_at = VALUES(marked_at)',
            [$collectorId, $branchId, $planHolderId, $month . '-01', $reasonCode, $note !== '' ? mb_substr($note, 0, 255) : null, date('Y-m-d H:i:s')]
        );

        return ['ok' => true, 'message' => $member['name'] . ' marked as not collected (' . self::REASONS[$reasonCode] . ').'];
    }

    /** @return array{ok:bool, message:string} */
    public function clearMark(int $collectorId, int $planHolderId, string $month): array
    {
        $month = self::monthKey($month);
        if ($this->isClosed($collectorId, $month)) {
            return ['ok' => false, 'message' => 'That month is already closed.'];
        }
        db_connect()->table('collection_marks')->where('collector_user_id', $collectorId)->where('plan_holder_id', $planHolderId)->where('month', $month . '-01')->delete();

        return ['ok' => true, 'message' => 'The "not collected" mark was removed.'];
    }

    private function isClosed(int $collectorId, string $month): bool
    {
        return db_connect()->table('monthly_collection_summaries')->where('collector_user_id', $collectorId)->where('month', $month . '-01')->countAllResults() > 0;
    }

    // ------------------------------------------------------------------ closing

    /**
     * Closes a month and writes the summary. Everything is re-checked here (members accounted for, every collected
     * payment of the month in a confirmed remittance).
     *
     * @return array{ok:bool, message:string, summary_id?:int}
     */
    public function closeMonth(int $collectorId, int $branchId, string $month, int $byUserId, ?string $today = null): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'Monthly close is not available yet. Please contact the System Administrator.'];
        }
        $month = self::monthKey($month, $today);
        $db = db_connect();
        $db->transBegin();
        try {
            // One close per collector and month: the unique key backs this up, the lock keeps two clicks apart.
            $db->query('SELECT user_id FROM users WHERE user_id = ? FOR UPDATE', [$collectorId]);
            $p = $this->progress($collectorId, $branchId, $month, $today);
            if ($p['closed'] !== null) {
                $db->transRollback();

                return ['ok' => false, 'message' => $p['month_label'] . ' is already closed.'];
            }
            if ($p['blockers'] !== []) {
                $db->transRollback();

                return ['ok' => false, 'message' => 'You cannot close ' . $p['month_label'] . ' yet: ' . implode(' ', $p['blockers'])];
            }

            $totals = $this->monthTotals($collectorId, $branchId, $month);
            $notCollected = [];
            foreach ($p['members'] as $m) {
                if ($m['state'] === 'not_collected') {
                    $notCollected[] = ['plan_holder_id' => $m['plan_holder_id'], 'name' => $m['name'], 'unique_identifier' => $m['unique_identifier'], 'reason' => $m['reason']];
                }
            }
            $db->table('monthly_collection_summaries')->insert([
                'collector_user_id' => $collectorId, 'branch_id' => $branchId, 'month' => $month . '-01',
                'members_assigned' => $p['counts']['assigned'], 'collected_count' => $p['counts']['collected'], 'not_collected_count' => $p['counts']['not_collected'],
                'not_collected_json' => $notCollected === [] ? null : json_encode($notCollected, JSON_UNESCAPED_UNICODE),
                'gross_amount' => $totals['gross'], 'cash_amount' => $totals['cash'], 'gcash_amount' => $totals['gcash'],
                'commission_amount' => $totals['commission'], 'net_amount' => $totals['net'], 'remittance_count' => $totals['remittances'],
                'closed_at' => date('Y-m-d H:i:s'), 'closed_by' => $byUserId,
            ]);
            $id = (int) $db->insertID();
            if ($db->transStatus() === false) {
                throw new \RuntimeException('A database step failed.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'CollectionMonthService::closeMonth failed for collector ' . $collectorId . ', month ' . $month . ': ' . $e->getMessage());

            return ['ok' => false, 'message' => 'The month could not be closed. Nothing was changed - please try again.'];
        }

        $collector = $db->table('users')->select('first_name, last_name')->where('user_id', $collectorId)->get(1)->getRowArray() ?: [];
        $name = trim((string) ($collector['first_name'] ?? '') . ' ' . (string) ($collector['last_name'] ?? ''));
        $notify = new NotificationService();
        foreach ($db->table('users')->select('user_id')->where('role_id', 2)->where('branch_id', $branchId)->where('status', 'active')->where('deleted_at', null)->get()->getResultArray() as $ba) {
            $notify->notify((int) $ba['user_id'], $name . ' closed ' . date('F Y', strtotime($month . '-01')) . ': ' . cs_peso($totals['gross']) . ' collected, ' . cs_peso($totals['net']) . ' remitted. The monthly collection summary is under Remittances.', 'general');
        }

        return ['ok' => true, 'message' => date('F Y', strtotime($month . '-01')) . ' is closed. The monthly collection summary was saved.', 'summary_id' => $id];
    }

    /**
     * Money of a month: gross, cash, GCash, commission and net remitted. A remittance made up only of this month's
     * payments contributes its own figures (so the summary equals the sum of the remittances to the cent); one that
     * mixes months is split per payment.
     *
     * @return array{gross:float, cash:float, gcash:float, commission:float, net:float, remittances:int}
     */
    public function monthTotals(int $collectorId, int $branchId, string $month): array
    {
        $out  = ['gross' => 0.0, 'cash' => 0.0, 'gcash' => 0.0, 'commission' => 0.0, 'net' => 0.0, 'remittances' => 0];
        $pays = array_filter($this->monthPayments($collectorId, $branchId, $month), static fn (array $p): bool => $p['remittance_id'] !== null);
        $byRemittance = [];
        foreach ($pays as $p) {
            $byRemittance[(int) $p['remittance_id']][] = $p;
        }
        foreach ($byRemittance as $rid => $inMonth) {
            $r = db_connect()->table('remittances')->where('remittance_id', $rid)->get(1)->getRowArray();
            if (! $r || $r['status'] === 'cancelled') {
                continue;
            }
            $out['remittances']++;
            if (count($inMonth) === (int) $r['payment_count']) {
                $out['gross'] += (float) $r['gross_amount'];
                $out['cash'] += (float) $r['cash_amount'];
                $out['gcash'] += (float) $r['gcash_amount'];
                $out['commission'] += (float) $r['commission_amount'];
                $out['net'] += (float) $r['net_amount'];

                continue;
            }
            foreach ((new RemittanceService())->lines($rid) as $line) {
                if (! in_array((int) $line['payment_id'], array_map('intval', array_column($inMonth, 'payment_id')), true)) {
                    continue;
                }
                $amount = (float) $line['amount'];
                $out['gross'] += $amount;
                $out[$line['payment_method'] === 'gcash' ? 'gcash' : 'cash'] += $amount;
                $out['commission'] += (float) $line['commission'];
                $out['net'] += $amount - (float) $line['commission'];
            }
        }
        foreach (['gross', 'cash', 'gcash', 'commission', 'net'] as $k) {
            $out[$k] = round($out[$k], 2);
        }

        return $out;
    }

    // ------------------------------------------------------------------ summaries (Branch Admin / reports)

    /**
     * Closed months, newest first, with the collector's and branch's name.
     *
     * @param array{branch_id?:int, collector_id?:int, month_from?:string, month_to?:string, search?:string} $f months are 'YYYY-MM'
     *
     * @return list<array<string,mixed>>
     */
    public function summaries(array $f = []): array
    {
        if (! self::ready()) {
            return [];
        }
        $b = db_connect()->table('monthly_collection_summaries s')
            ->select('s.*, u.first_name, u.last_name, br.branch_name, cb.first_name AS closed_first, cb.last_name AS closed_last')
            ->join('users u', 'u.user_id = s.collector_user_id', 'inner')
            ->join('branches br', 'br.branch_id = s.branch_id', 'left')
            ->join('users cb', 'cb.user_id = s.closed_by', 'left');
        if ((int) ($f['branch_id'] ?? 0) > 0) {
            $b->where('s.branch_id', (int) $f['branch_id']);
        }
        if ((int) ($f['collector_id'] ?? 0) > 0) {
            $b->where('s.collector_user_id', (int) $f['collector_id']);
        }
        if (preg_match('/^\d{4}-\d{2}$/', (string) ($f['month_from'] ?? ''))) {
            $b->where('s.month >=', $f['month_from'] . '-01');
        }
        if (preg_match('/^\d{4}-\d{2}$/', (string) ($f['month_to'] ?? ''))) {
            $b->where('s.month <=', $f['month_to'] . '-01');
        }
        $rows = $b->orderBy('s.month', 'DESC')->orderBy('s.summary_id', 'DESC')->get()->getResultArray();
        $q = strtolower(trim((string) ($f['search'] ?? '')));
        $out = [];
        foreach ($rows as $r) {
            $r['collector_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
            $r['month_label'] = date('F Y', strtotime((string) $r['month']));
            $r['not_collected'] = $r['not_collected_json'] ? (json_decode((string) $r['not_collected_json'], true) ?: []) : [];
            if ($q !== '' && ! str_contains(strtolower($r['collector_name'] . ' ' . (string) $r['branch_name']), $q)) {
                continue;
            }
            $out[] = $r;
        }

        return $out;
    }

    public function summary(int $summaryId): ?array
    {
        foreach ($this->summaries() as $r) {
            if ((int) $r['summary_id'] === $summaryId) {
                return $r;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------ flags

    /**
     * Collected payments not remitted for more than CareSync::$remittanceMaxDays days.
     *
     * @return list<array<string,mixed>> payment_id, collector_user_id, collector_name, amount, collected_at, days, branch_id
     */
    public function unremittedPastLimit(?int $branchId = null, ?int $collectorId = null, ?string $today = null): array
    {
        if (! PaymentService::collectedReady()) {
            return [];
        }
        $today = date('Y-m-d', strtotime($today ?? 'today'));
        $b = db_connect()->table('payments pay')
            ->select("pay.payment_id, pay.branch_id, pay.received_by AS collector_user_id, pay.amount, pay.collected_at, u.first_name, u.last_name, DATEDIFF('$today', DATE(pay.collected_at)) AS days", false)
            ->join('users u', 'u.user_id = pay.received_by', 'left')
            ->where('pay.status', 'pending')->where('pay.awaiting_remittance', 1)->where('pay.remittance_id', null)->where('pay.deleted_at', null)
            ->where("DATEDIFF('$today', DATE(pay.collected_at)) >", (int) $this->cfg->remittanceMaxDays, false);
        if ($branchId !== null && $branchId > 0) {
            $b->where('pay.branch_id', $branchId);
        }
        if ($collectorId !== null && $collectorId > 0) {
            $b->where('pay.received_by', $collectorId);
        }
        $rows = $b->orderBy('pay.collected_at', 'ASC')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['collector_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
        }
        unset($r);

        return $rows;
    }

    /**
     * Collectors who have not closed last month although it is past CareSync::$monthCloseDueDay of this month.
     *
     * @return list<array{collector_user_id:int, collector_name:string, month:string, month_label:string}>
     */
    public function unclosedMonths(int $branchId, ?string $today = null): array
    {
        if (! self::ready()) {
            return [];
        }
        $today = date('Y-m-d', strtotime($today ?? 'today'));
        if ((int) date('j', strtotime($today)) <= (int) $this->cfg->monthCloseDueDay) {
            return [];
        }
        $month = date('Y-m', strtotime(date('Y-m-01', strtotime($today)) . ' -1 month'));
        $lastMoment = date('Y-m-t', strtotime($month . '-01')) . ' 23:59:59';
        $out = [];
        foreach ((new CollectorAssignmentService())->branchCollectorsWithAssignments($branchId) as $c) {
            $uid = (int) $c['user_id'];
            $created = db_connect()->table('users')->select('created_at')->where('user_id', $uid)->get(1)->getRowArray()['created_at'] ?? null;
            if ($created !== null && strcmp((string) $created, $lastMoment) > 0) {
                continue;
            }
            if (! $this->isClosed($uid, $month)) {
                $out[] = ['collector_user_id' => $uid, 'collector_name' => trim((string) $c['first_name'] . ' ' . (string) $c['last_name']), 'month' => $month, 'month_label' => date('F Y', strtotime($month . '-01'))];
            }
        }

        return $out;
    }
}
