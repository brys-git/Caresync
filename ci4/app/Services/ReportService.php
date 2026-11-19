<?php

namespace App\Services;

class ReportService
{
    /**
     * Panel brief, section 8: "Collector/agent commission = 10% of the
     * amount collected, computed automatically." Defined once here so the
     * Commission Report and any later overdue/commission policy work read
     * the same rate rather than each hardcoding their own 0.10.
     */
    public const COMMISSION_RATE = 0.10;

    public function getDashboardSummary(array $filters): array
    {
        return [
            'monthly_collections' => $this->getMonthlyCollections($filters),
            'payment_breakdown' => $this->getPaymentBreakdown($filters),
            'member_status' => $this->getMemberStatusSummary($filters),
            'delinquent_accounts' => $this->getDelinquentAccounts($filters),
            'service_usage' => $this->getServiceUsageStatistics($filters),
            'payment_trends' => $this->getPaymentTrends($filters),
        ];
    }

    public function getRemittanceReport(array $filters): array
    {
        $builder = $this->baseQuery($filters)
            ->select('p.payment_id, p.payment_date, p.amount, p.months_covered, p.payment_method, p.reference_number, p.official_receipt_number, p.status, rb.first_name AS staff_first, rb.last_name AS staff_last, cu.first_name AS client_first, cu.last_name AS client_last, ph.plan_holder_id, ph.unique_identifier, ph.id_control_no, pl.start_date, b.branch_name');

        $rows = $builder
            ->orderBy('p.payment_date', 'DESC')
            ->orderBy('p.payment_id', 'DESC')
            ->get()
            ->getResultArray();

        // Where each paid payment went: plan contribution / package instalment / down payment.
        $amounts = [];
        foreach ($rows as $r) {
            $amounts[(int) $r['payment_id']] = (float) $r['amount'];
        }
        $splits = $this->metrics()->paymentSplits(array_keys($amounts), $amounts);
        foreach ($rows as &$r) {
            $paid = strtolower((string) ($r['status'] ?? '')) === 'paid';
            $sp = $splits[(int) $r['payment_id']] ?? ['plan' => 0.0, 'instalments' => 0.0, 'down_payments' => 0.0];
            $r['plan_part'] = $paid ? $sp['plan'] : null;
            $r['instalment_part'] = $paid ? $sp['instalments'] : null;
            $r['down_part'] = $paid ? $sp['down_payments'] : null;
        }
        unset($r);

        return $rows;
    }

    /**
     * The three total lines every remittance/collections report prints under "Cash / GCash":
     * plan contributions, package instalments, down payments. They add up to the total.
     *
     * @return list<array{label:string, amount:float}>
     */
    public function splitTotalLines(array $filters): array
    {
        $s = $this->metrics()->collectedSplit($this->branchOf($filters), (string) $filters['date_from'], (string) $filters['date_to'], $this->metricOpts($filters));

        return [
            ['label' => 'Plan contributions', 'amount' => $s['plan']],
            ['label' => 'Package instalments', 'amount' => $s['instalments']],
            ['label' => 'Down payments', 'amount' => $s['down_payments']],
        ];
    }

    /**
     * Paid money in the filtered range. Totals on every report come from
     * MetricsService (status 'paid' only), so they match the dashboards.
     */
    public function getTotalRemittance(array $filters): float
    {
        return $this->metrics()->collected($this->branchOf($filters), (string) $filters['date_from'], (string) $filters['date_to'], $this->metricOpts($filters));
    }

    public function getPaymentBreakdown(array $filters): array
    {
        return $this->metrics()->collectedByMethod($this->branchOf($filters), (string) $filters['date_from'], (string) $filters['date_to'], $this->metricOpts($filters));
    }

    private function metrics(): MetricsService
    {
        return new MetricsService();
    }

    private function branchOf(array $filters): ?int
    {
        $id = (int) ($filters['branch_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    private function metricOpts(array $filters): array
    {
        return [
            'method'      => (string) ($filters['payment_method'] ?? ''),
            'received_by' => (int) ($filters['received_by'] ?? 0),
            'search'      => (string) ($filters['search'] ?? ''),
        ];
    }

    public function getBranchPaymentStaff(int $branchId): array
    {
        return db_connect()->table('users u')
            ->select('u.user_id, u.first_name, u.last_name, u.contact_number')
            ->join('payments p', 'p.received_by = u.user_id', 'inner')
            ->where('u.branch_id', $branchId)
            ->groupBy('u.user_id, u.first_name, u.last_name, u.contact_number')
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getBranchInfo(int $branchId): ?array
    {
        $row = db_connect()->table('branches')
            ->select('branch_id, branch_name, address_street, address_barangay, address_city, address_province, contact_number')
            ->where('branch_id', $branchId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function getMonthlyCollections(array $filters): array
    {
        $year = (int) ($filters['year'] ?? date('Y'));

        return $this->metrics()->collectedByMonth($this->branchOf($filters), $year . '-01-01', $year . '-12-31');
    }

    public function getMemberStatusSummary(array $filters): array
    {
        $m = $this->metrics()->memberCounts($this->branchOf($filters));

        return [
            'total_members'    => $m['total_members'],
            'active_members'   => $m['active'],
            'inactive_members' => $m['inactive_members'],
        ];
    }

    /**
     * The top of the Overdue report (same rows, same numbers) for the summary page.
     */
    public function getDelinquentAccounts(array $filters): array
    {
        $rows = array_slice($this->getOverdueReport(['branch_id' => $filters['branch_id'] ?? 0]), 0, 25);

        foreach ($rows as &$row) {
            $row['plan_status'] = $row['lifecycle'];
        }
        unset($row);

        return $rows;
    }

    public function getServiceUsageStatistics(array $filters): array
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);

        $builder = db_connect()->table('services s')
            // Same stray-backslash issue as getMonthlyCollections() above: \"
            // inside a single-quoted PHP string isn't an escape sequence.
            ->select('COALESCE(sl.service_name, "Unknown") AS service_name, COUNT(*) AS total_services, SUM(CASE WHEN s.status = "completed" THEN 1 ELSE 0 END) AS completed_services, SUM(CASE WHEN s.status = "cancelled" THEN 1 ELSE 0 END) AS cancelled_services', false)
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->groupBy('COALESCE(sl.service_name, "Unknown")', false)
            ->orderBy('total_services', 'DESC')
            ->limit(10);

        if ($branchId > 0) {
            $builder->where('s.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    public function getPaymentTrends(array $filters): array
    {
        $year    = (int) ($filters['year'] ?? date('Y'));
        $metrics = $this->metrics();
        $branch  = $this->branchOf($filters);

        $cash  = array_column($metrics->collectedByMonth($branch, $year . '-01-01', $year . '-12-31', ['method' => 'cash']), null, 'month_key');
        $gcash = array_column($metrics->collectedByMonth($branch, $year . '-01-01', $year . '-12-31', ['method' => 'gcash']), null, 'month_key');

        $keys = array_unique(array_merge(array_keys($cash), array_keys($gcash)));
        sort($keys);

        $rows = [];
        foreach ($keys as $key) {
            $rows[] = [
                'month_key'   => $key,
                'month_label' => date('M Y', strtotime($key . '-01')),
                'cash_total'  => (float) ($cash[$key]['total_amount'] ?? 0),
                'gcash_total' => (float) ($gcash[$key]['total_amount'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Cleans the overdue-report period filters: "as of" (default today, never in
     * the future) and an optional "due from" / "due to" range.
     *
     * @return array{as_of:string, due_from:string, due_to:string}
     */
    public function overduePeriod(array $input): array
    {
        $valid = static function ($v): string {
            $v = trim((string) $v);

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false ? $v : '';
        };

        $today = date('Y-m-d');
        $asOf = $valid($input['as_of'] ?? '');
        if ($asOf === '' || $asOf > $today) {
            $asOf = $today;
        }

        $from = $valid($input['due_from'] ?? '');
        $to = $valid($input['due_to'] ?? '');
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return ['as_of' => $asOf, 'due_from' => $from, 'due_to' => $to];
    }

    /** The period in words, for report headers and the page. */
    public function overduePeriodLine(array $filters): string
    {
        $asOf = (string) ($filters['as_of'] ?? date('Y-m-d'));
        $line = 'As of ' . date('j M Y', strtotime($asOf));

        $from = (string) ($filters['due_from'] ?? '');
        $to = (string) ($filters['due_to'] ?? '');
        if ($from !== '' && $to !== '') {
            $line .= ' - due ' . date('j M Y', strtotime($from)) . ' to ' . date('j M Y', strtotime($to));
        } elseif ($from !== '') {
            $line .= ' - due from ' . date('j M Y', strtotime($from));
        } elseif ($to !== '') {
            $line .= ' - due up to ' . date('j M Y', strtotime($to));
        }

        return $line;
    }

    /**
     * "Avails and waivers": every package / service / add-on avail with its price, down payment
     * (paid, waived with reason and who, or still due), term, what has been paid so far, the balance and the status.
     *
     * @param array{branch_id?:int, search?:string, status?:string, date_from?:string, date_to?:string} $filters
     *        status: '' | awaiting_down_payment | active | settled | cancelled | waived   (dates filter the approval date)
     *
     * @return list<array<string,mixed>>
     */
    public function getAvailsReport(array $filters): array
    {
        $db = db_connect();
        if (! $db->tableExists('package_avails')) {
            return [];
        }
        $b = $db->table('package_avails pa')
            ->select("pa.*, ph.unique_identifier, u.first_name, u.last_name, br.branch_name, COALESCE(pk.package_name, sl.service_name) AS what,"
                . " w.first_name AS waived_by_first, w.last_name AS waived_by_last, ap.first_name AS approved_by_first, ap.last_name AS approved_by_last,"
                . " (SELECT COALESCE(SUM(al.amount),0) FROM payment_allocations al WHERE al.avail_id = pa.avail_id) AS paid_so_far,"
                . " (SELECT COALESCE(SUM(al.amount),0) FROM payment_allocations al WHERE al.avail_id = pa.avail_id AND al.target_type = 'avail_down_payment') AS down_paid", false)
            ->join('plan_holders ph', 'ph.plan_holder_id = pa.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches br', 'br.branch_id = ph.branch_id', 'left')
            ->join('packages pk', 'pk.package_id = pa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = pa.service_list_id', 'left')
            ->join('users w', 'w.user_id = pa.waived_by', 'left')
            ->join('users ap', 'ap.user_id = pa.approved_by', 'left');

        if ((int) ($filters['branch_id'] ?? 0) > 0) {
            $b->where('ph.branch_id', (int) $filters['branch_id']);
        }
        $status = (string) ($filters['status'] ?? '');
        if ($status === 'waived') {
            $b->where('pa.down_payment_status', 'waived');
        } elseif (in_array($status, ['awaiting_down_payment', 'active', 'settled', 'cancelled'], true)) {
            $b->where('pa.status', $status);
        }
        if (($filters['date_from'] ?? '') !== '') {
            $b->where('DATE(pa.approved_at) >=', (string) $filters['date_from']);
        }
        if (($filters['date_to'] ?? '') !== '') {
            $b->where('DATE(pa.approved_at) <=', (string) $filters['date_to']);
        }
        cs_apply_search($b, (string) ($filters['search'] ?? ''), ['u.first_name', 'u.last_name', 'ph.unique_identifier', 'pk.package_name', 'sl.service_name']);

        $rows = $b->orderBy('pa.approved_at', 'DESC')->orderBy('pa.avail_id', 'DESC')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['paid_so_far'] = round((float) $r['paid_so_far'], 2);
            $r['balance'] = $r['status'] === 'cancelled' ? 0.0 : max(0.0, round((float) $r['package_price'] - $r['paid_so_far'], 2));
            $r['client_name'] = trim((string) $r['first_name'] . ' ' . (string) $r['last_name']);
            $r['waived_by_name'] = trim((string) ($r['waived_by_first'] ?? '') . ' ' . (string) ($r['waived_by_last'] ?? ''));
            $r['status_label'] = ['awaiting_down_payment' => 'Awaiting down payment', 'active' => 'Active', 'settled' => 'Settled', 'cancelled' => 'Cancelled'][$r['status']] ?? (string) $r['status'];
            $r['kind_label'] = ['package' => 'Package', 'service' => 'Service', 'addons' => 'Add-on'][$r['kind'] ?? 'package'] ?? 'Package';
            $r['down_label'] = $r['down_payment_status'] === 'waived'
                ? 'Waived'
                : ($r['down_payment_status'] === 'paid' ? 'Paid' : 'Due' . ($r['down_payment_due_by'] ? ' by ' . date('j M Y', strtotime((string) $r['down_payment_due_by'])) : ''));
            $r['term_label'] = (int) $r['term_months'] > 0 ? (int) $r['term_months'] . ' months' : 'Paid in full';
        }
        unset($r);

        return $rows;
    }

    /** Cleans the Avails and waivers filters (status, approval dates, search). */
    public function availsFilters(array $input, int $branchId): array
    {
        $date = static function ($v): string {
            $v = trim((string) $v);

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false ? $v : '';
        };
        $status = (string) ($input['status'] ?? '');
        $from = $date($input['date_from'] ?? '');
        $to = $date($input['date_to'] ?? '');
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'branch_id' => $branchId,
            'status' => in_array($status, ['awaiting_down_payment', 'active', 'settled', 'cancelled', 'waived'], true) ? $status : '',
            'date_from' => $from,
            'date_to' => $to,
            'search' => cs_search_term($input['search'] ?? ''),
        ];
    }

    /** @return list<string> */
    public function availsCsvHeaders(): array
    {
        return ['Plan Holder', 'Plan No.', 'Branch', 'Type', 'Package / Service', 'Price', 'Down Payment Amount', 'Down Payment', 'Waived By', 'Waived On', 'Waiver Reason', 'Term', 'Monthly Instalment', 'Paid So Far', 'Balance', 'Status', 'Approved On'];
    }

    /** @return list<string> */
    public function availsCsvRow(array $r): array
    {
        $m = static fn ($v): string => number_format((float) $v, 2, '.', '');

        return [
            (string) $r['client_name'], (string) ($r['unique_identifier'] ?? ''), (string) ($r['branch_name'] ?? ''), (string) $r['kind_label'], (string) ($r['what'] ?? ''),
            $m($r['package_price']), $m($r['down_payment_amount']), (string) $r['down_label'],
            (string) $r['waived_by_name'], $r['waived_at'] ? date('Y-m-d', strtotime((string) $r['waived_at'])) : '', (string) ($r['waiver_reason'] ?? ''),
            (string) $r['term_label'], (int) $r['term_months'] > 0 ? $m($r['monthly_instalment']) : '', $m($r['paid_so_far']), $m($r['balance']),
            (string) $r['status_label'], $r['approved_at'] ? date('Y-m-d', strtotime((string) $r['approved_at'])) : '',
        ];
    }

    public function getOverdueReport(array $filters): array
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);
        $today = date('Y-m-d');
        $period = $this->overduePeriod($filters);
        $asOf = $period['as_of'];
        $dueFrom = $period['due_from'];
        $dueTo = $period['due_to'];
        $custom = $asOf !== $today || $dueFrom !== '' || $dueTo !== '';

        // Candidate plans only (cheap SQL); the real numbers come from the
        // one rule, PlanLedgerService, so this report always agrees with the
        // client details page and the client's own dashboard.
        $db = db_connect();
        $builder = $db->table('plans pl')
            ->select('pl.plan_id, ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name, u.contact_number, b.branch_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left');

        if ($custom) {
            // A past date (or a due range) can include plans that have paid up
            // since, so look at every plan that had started by then.
            $builder->where('pl.start_date <=', $asOf);
        } else {
            $builder->groupStart()
                ->where('pl.next_due_date <', $today);

            // plans.lifecycle_status comes from migration AddPlanLifecycle; on a
            // database that has not run it yet, fall back to the due-date test
            // alone instead of failing the whole page.
            if ($db->fieldExists('lifecycle_status', 'plans')) {
                $builder->orWhereIn('pl.lifecycle_status', ['grace', 'lapsed', 'forfeited']);
            }

            // a plan that is fine on its contribution but behind on a package instalment
            if ($db->tableExists('package_avail_instalments')) {
                $builder->orWhere("pl.plan_id IN (SELECT pa.plan_id FROM package_avails pa JOIN package_avail_instalments i ON i.avail_id = pa.avail_id WHERE pa.status IN ('active','settled') AND i.due_date < " . $db->escape($today) . ' AND i.paid_amount < i.amount)', null, false);
            }

            $builder->groupEnd();
        }

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        cs_apply_search($builder, (string) ($filters['search'] ?? ''), ['u.first_name', 'u.last_name', 'u.contact_number', 'ph.unique_identifier']);

        $ledger = new PlanLedgerService();
        $claimAvail = new ClaimAvailService();
        $rows = [];

        foreach ($builder->get()->getResultArray() as $row) {
            // "As of": the plan as it stood on that date (payments after it do not count yet).
            $s = $ledger->summarizeAsOf((int) $row['plan_id'], $asOf);
            if (! $s) {
                continue;
            }
            $planBehind = in_array($s['lifecycle'], ['grace', 'lapsed', 'forfeited'], true);
            $pk = $claimAvail->packageStandingAsOf((int) $row['plan_id'], $asOf, $dueFrom, $dueTo);
            if (! $planBehind && $pk['overdue_count'] === 0) {
                continue;
            }

            $overdueCount = $planBehind ? (int) $s['overdue_count'] : 0;
            $overdueAmount = $planBehind ? (float) $s['overdue_amount'] : 0.0;
            $overdueDues = $planBehind ? $s['overdue_dues'] : [];
            $oldestDue = $planBehind ? $s['oldest_unpaid_due'] : null;

            // "Due from / to": only overdue contributions whose due date is in the range.
            if ($dueFrom !== '' || $dueTo !== '') {
                $inRange = array_values(array_filter($s['contributions'], static function (array $c) use ($asOf, $dueFrom, $dueTo): bool {
                    return ! $c['is_paid']
                        && strcmp($c['due'], $asOf) < 0
                        && ($dueFrom === '' || strcmp($c['due'], $dueFrom) >= 0)
                        && ($dueTo === '' || strcmp($c['due'], $dueTo) <= 0);
                }));

                if ($inRange === []) {
                    $overdueCount = 0;
                    $overdueAmount = 0.0;
                    $overdueDues = [];
                    $oldestDue = null;
                } else {
                    $overdueCount = count($inRange);
                    $overdueAmount = round(array_sum(array_column($inRange, 'remaining')), 2);
                    $overdueDues = array_column($inRange, 'due');
                    $oldestDue = $inRange[0]['due'];
                }
                if ($overdueCount === 0 && $pk['overdue_count'] === 0) {
                    continue;
                }
            }
            if ($pk['oldest_due'] !== null && ($oldestDue === null || strcmp($pk['oldest_due'], (string) $oldestDue) < 0)) {
                $oldestDue = $pk['oldest_due'];
            }

            $rows[] = $row + [
                'remaining_balance'      => ClientPlanSummary::fromLedger($s, $asOf)['remaining_balance'],
                'months_paid'            => $s['months_paid'],
                'overdue_months'         => $overdueCount,
                'overdue_amount'         => $overdueAmount,
                'overdue_dues'           => $overdueDues,
                'package_balance'        => $pk['balance'],
                'package_overdue_count'  => $pk['overdue_count'],
                'package_overdue_amount' => $pk['overdue_amount'],
                'total_overdue_amount'   => round($overdueAmount + $pk['overdue_amount'], 2),
                'has_package'            => $pk['has_package'],
                'next_due_date'          => $oldestDue,
                'days_overdue'           => max((int) $s['days_overdue'], $pk['oldest_due'] !== null ? (int) floor((strtotime($asOf) - strtotime($pk['oldest_due'])) / 86400) : 0),
                'lifecycle'              => $s['lifecycle'],
                // Kept under its old key for the CSV/PDF callers: now the
                // days left in the grace period (null once it has passed).
                'days_until_forfeiture'  => $s['grace_days_left'],
                'grace_days_left'        => $s['grace_days_left'],
                'status_text'            => ! $planBehind ? 'Package instalment overdue' : match ($s['lifecycle']) {
                    'grace'  => $s['grace_days_left'] . ' day(s) left to pay',
                    'lapsed' => 'Lapsed - on hold',
                    default  => 'Forfeited',
                },
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['next_due_date'], (string) $b['next_due_date']));

        return $rows;
    }
    /**
     * Plan-holder search for the Ledger report (by name or unique
     * identifier).
     */
    public function searchPlanHolders(string $query, int $branchId = 0): array
    {
        $query = cs_search_term($query);
        if ($query === '') {
            return [];
        }

        $builder = db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name, b.branch_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left');

        cs_apply_search($builder, $query, ['u.first_name', 'u.last_name', 'u.contact_number', 'u.email', 'ph.unique_identifier']);

        $builder
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.last_name', 'ASC')
            ->limit(20);

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Panel brief, section 7: "Ledger of Plan Holder - full payment/
     * transaction history per plan holder." Returns the plan holder's
     * profile/plan header plus every payment on their current plan, in
     * ascending order (same convention as the client-facing payment
     * history from Phase 2), with a running total of confirmed payments.
     */
    public function getPlanHolderLedger(int $planHolderId): ?array
    {
        if ($planHolderId <= 0) {
            return null;
        }

        $holder = db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.status AS holder_status, u.first_name, u.last_name, u.contact_number, u.email, b.branch_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->where('ph.plan_holder_id', $planHolderId)
            ->get()
            ->getRowArray();

        if (! $holder) {
            return null;
        }

        $plan = db_connect()->table('plans')
            ->where('plan_holder_id', $planHolderId)
            ->orderBy('plan_id', 'DESC')
            ->get()
            ->getRowArray();

        $payments = [];
        if ($plan) {
            $payments = db_connect()->table('payments pay')
                ->select('pay.payment_id, pay.payment_date, pay.amount, pay.months_covered, pay.payment_method, pay.reference_number, pay.official_receipt_number, pay.status, pay.coverage_start, pay.coverage_end, rb.first_name AS staff_first, rb.last_name AS staff_last')
                ->join('users rb', 'rb.user_id = pay.received_by', 'left')
                ->where('pay.plan_id', (int) $plan['plan_id'])
                ->orderBy('pay.payment_date', 'ASC')
                ->orderBy('pay.payment_id', 'ASC')
                ->get()
                ->getResultArray();

            $runningTotal = 0.0;
            foreach ($payments as &$payment) {
                if (in_array((string) $payment['status'], ['paid', 'verified'], true)) {
                    $runningTotal += (float) $payment['amount'];
                }
                $payment['running_total'] = round($runningTotal, 2);
            }
            unset($payment);
        }

        return [
            'holder' => $holder,
            'plan' => $plan,
            'payments' => $payments,
        ];
    }

    /**
     * Panel brief, section 7: "Collections/Collector Report - summary of
     * amounts collected per collector." "Collector" here is whoever
     * recorded the payment (received_by), not narrowly the Collector role -
     * Staff and Branch Admin already record payments today, and a Collector
     * -only filter would show nothing until that role has real activity.
     * Each row's role_name lets Admin see who's who.
     */
    public function getCollectionsByCollector(array $filters): array
    {
        $builder = $this->collectorBaseQuery($filters)
            ->select("u.user_id, u.first_name, u.last_name, r.role_name, COUNT(p.payment_id) AS transaction_count, COALESCE(SUM(p.amount), 0) AS total_collected, COALESCE(SUM((SELECT SUM(al.amount) FROM payment_allocations al WHERE al.payment_id = p.payment_id AND al.target_type = 'avail_instalment')), 0) AS instalments_collected, COALESCE(SUM((SELECT SUM(al.amount) FROM payment_allocations al WHERE al.payment_id = p.payment_id AND al.target_type = 'avail_down_payment')), 0) AS down_collected", false)
            ->groupBy('u.user_id, u.first_name, u.last_name, r.role_name')
            ->orderBy('total_collected', 'DESC');

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['total_collected'] = (float) ($row['total_collected'] ?? 0);
            $row['transaction_count'] = (int) ($row['transaction_count'] ?? 0);
            $row['instalments_collected'] = round((float) ($row['instalments_collected'] ?? 0), 2);
            $row['down_collected'] = round((float) ($row['down_collected'] ?? 0), 2);
            $row['plan_collected'] = round($row['total_collected'] - $row['instalments_collected'] - $row['down_collected'], 2);
        }
        unset($row);

        return $rows;
    }

    /**
     * Panel brief, section 7/8: "Commission Report - commission earned per
     * agent/collector," at the section 8 rate (10% of amount collected).
     *
     * Fix Prompts Task 4: this used to run off getCollectionsByCollector(),
     * which counts every verified payment regardless of method - including
     * desk cash/GCash a Branch Admin recorded through Payment Tracking, not
     * just cash a collector actually collected in the field. Delegates to
     * CommissionService::reportRows() instead (same cash-only eligibility
     * rule the collector's own commission page uses), so this report and
     * that page always agree for the same date range. Return shape is
     * unchanged: user_id/first_name/last_name/role_name/transaction_count/
     * total_collected/commission/net_remitted.
     */
    public function getCommissionReport(array $filters): array
    {
        $rows = (new CommissionService())->reportRows(
            (int) ($filters['branch_id'] ?? 0) ?: null,
            (string) ($filters['date_from'] ?? '') ?: null,
            (string) ($filters['date_to'] ?? '') ?: null
        );

        // The commission rows come out of CommissionService already
        // aggregated, so the collector-name search is applied to them here
        // (in PHP - no SQL involved): every word must be in the full name.
        // Earned / Paid / Unpaid per collector for this period.
        $rows = (new CommissionPayoutService())->annotate(
            $rows,
            (string) ($filters['date_from'] ?? '') ?: null,
            (string) ($filters['date_to'] ?? '') ?: null,
            (int) ($filters['branch_id'] ?? 0) ?: null
        );

        $term = cs_search_term($filters['search'] ?? '');
        if ($term === '') {
            return $rows;
        }

        $words = array_map('mb_strtolower', explode(' ', $term));

        return array_values(array_filter($rows, static function (array $row) use ($words): bool {
            $name = mb_strtolower(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')));
            foreach ($words as $word) {
                if (! str_contains($name, $word)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function collectorBaseQuery(array $filters)
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);
        $dateFrom = (string) ($filters['date_from'] ?? '');
        $dateTo = (string) ($filters['date_to'] ?? '');

        $builder = db_connect()->table('payments p')
            ->join('users u', 'u.user_id = p.received_by', 'inner')
            ->join('roles r', 'r.role_id = u.role_id', 'left')
            ->where('p.status', 'paid');

        if ($branchId > 0) {
            $builder->where('p.branch_id', $branchId);
        }

        if ($dateFrom !== '') {
            $builder->where('p.payment_date >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $builder->where('p.payment_date <=', $dateTo);
        }

        cs_apply_search($builder, (string) ($filters['search'] ?? ''), ['u.first_name', 'u.last_name']);

        return $builder;
    }

    private function baseQuery(array $filters)
    {
        $builder = db_connect()->table('payments p')
            ->join('users rb', 'rb.user_id = p.received_by', 'left')
            ->join('plans pl', 'pl.plan_id = p.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users cu', 'cu.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->where('p.payment_date >=', (string) $filters['date_from'])
            ->where('p.payment_date <=', (string) $filters['date_to']);

        // branch_id 0 means system-wide (Admin with no branch filter chosen) -
        // Branch Admin/Staff always pass their own real branch_id, so this
        // was never an issue for them, but it silently zeroed out every
        // Admin-facing report built on this query (the existing "Payments
        // Report" dashboard table included) until now.
        if ((int) $filters['branch_id'] > 0) {
            $builder->where('p.branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['payment_method'])) {
            $builder->where('p.payment_method', (string) $filters['payment_method']);
        }

        if (! empty($filters['received_by'])) {
            $builder->where('p.received_by', (int) $filters['received_by']);
        }

        cs_apply_search($builder, (string) ($filters['search'] ?? ''), [
            'cu.first_name', 'cu.last_name', 'rb.first_name', 'rb.last_name',
            'ph.unique_identifier', 'p.reference_number', 'p.official_receipt_number',
        ]);

        return $builder;
    }
}
