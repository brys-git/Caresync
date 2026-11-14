<?php

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;

/**
 * MetricsService - the ONE place that computes aggregate figures (money
 * collected, member counts, pending counts). Dashboards, analytics, reports,
 * payment tracking, the collection list and the sidebar badges all call it,
 * so the same branch and date range always give the same numbers.
 *
 * Plan-level figures (months paid, total paid, remaining balance, overdue
 * months/amount, status of ONE plan) are NOT computed here: they come only
 * from PlanLedgerService / ClientPlanSummary. Commission comes only from
 * CommissionService.
 *
 * ---------------------------------------------------------------- RULES
 * What counts as "collected":
 *   Only payments with status = 'paid'. Pending, rejected and cancelled
 *   payments are never collected money. (Past records entered by a branch
 *   live in legacy_payments; they count toward a plan's own totals through the
 *   ledger but are not part of "collected" - that is money received through
 *   the system.)
 *
 * Which date field:
 *   Money figures use payments.payment_date (the day the payment was made),
 *   compared as a whole day, from and to both included. Counts of members use
 *   no date (they are "right now"). Pending counts are "right now" too.
 *
 * How branch scoping is applied:
 *   $branchId = null (or 0) means every branch (System Admin only - callers
 *   pass the signed-in user's own branch for Branch Admin / Staff).
 *   - Money: the branch that took the payment, payments.branch_id, falling
 *     back to the plan holder's branch when a payment row has none.
 *   - Members / plans / pending registrations: plan_holders.branch_id.
 *   - Service requests: the requesting plan holder's branch.
 *
 * Member status comes from plans.lifecycle_status, the stored result of
 * PlanLedgerService::sync() - the same status the ledger and the Overdue
 * report use:
 *   active   = lifecycle 'active' or 'fully_paid'
 *   grace    = 'grace'   (overdue, still inside the grace period)
 *   lapsed   = 'lapsed'  (on hold)
 *   forfeited= 'forfeited'
 *   overdue_accounts = grace + lapsed + forfeited, i.e. exactly the rows the
 *   Overdue report lists.
 *
 * Figure -> method:
 *   total collected / collected in a range ..... collected()
 *   number of paid payments ................... collectedCount()
 *   cash vs GCash split ....................... collectedByMethod()
 *   collections per calendar month ............ collectedByMonth()
 *   collections per day in a month ............ collectedByDay()
 *   collected by one person (received_by) ..... collected(... ['received_by' => id])
 *   member / status counts .................... memberCounts()
 *   payments waiting to be verified ........... pendingPayments()
 *   registrations waiting for approval ........ pendingApprovals()
 *   service requests waiting ................... pendingServiceRequests()
 *   sidebar badge numbers ..................... navCounts()
 */
class MetricsService
{
    public const PAID = 'paid';

    /**
     * Builder over paid payments for a branch and date range, with the joins
     * the optional filters need. Every "collected" figure starts here.
     *
     * @param array{method?:string,received_by?:int,search?:string} $opts
     */
    public function paidPayments(?int $branchId = null, ?string $from = null, ?string $to = null, array $opts = []): BaseBuilder
    {
        $builder = db_connect()->table('payments pay')
            ->join('plans pl', 'pl.plan_id = pay.plan_id', 'left')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'left')
            ->where('pay.status', self::PAID);

        if ($branchId !== null && $branchId > 0) {
            $builder->where('COALESCE(NULLIF(pay.branch_id, 0), ph.branch_id) =', $branchId, false);
        }

        if ($from !== null && $from !== '') {
            $builder->where('DATE(pay.payment_date) >=', $from);
        }

        if ($to !== null && $to !== '') {
            $builder->where('DATE(pay.payment_date) <=', $to);
        }

        if (! empty($opts['method'])) {
            $builder->where('pay.payment_method', (string) $opts['method']);
        }

        if (! empty($opts['received_by'])) {
            $builder->where('pay.received_by', (int) $opts['received_by']);
        }

        if (! empty($opts['search'])) {
            $builder->join('users cu', 'cu.user_id = ph.user_id', 'left')
                ->join('users rb', 'rb.user_id = pay.received_by', 'left');
            cs_apply_search($builder, (string) $opts['search'], [
                'cu.first_name', 'cu.last_name', 'cu.contact_number', 'rb.first_name', 'rb.last_name',
                'ph.unique_identifier', 'pay.reference_number', 'pay.official_receipt_number',
            ]);
        }

        return $builder;
    }

    /** Total money collected (paid payments) in the range. */
    public function collected(?int $branchId = null, ?string $from = null, ?string $to = null, array $opts = []): float
    {
        $row = $this->paidPayments($branchId, $from, $to, $opts)
            ->select('COALESCE(SUM(pay.amount), 0) AS total', false)
            ->get()
            ->getRowArray();

        return round((float) ($row['total'] ?? 0), 2);
    }

    /** Number of paid payments in the range. */
    public function collectedCount(?int $branchId = null, ?string $from = null, ?string $to = null, array $opts = []): int
    {
        return (int) $this->paidPayments($branchId, $from, $to, $opts)->countAllResults();
    }

    /**
     * Cash / GCash split of collected money.
     *
     * @return array{total_transactions:int,total_amount:float,cash_total:float,gcash_total:float}
     */
    public function collectedByMethod(?int $branchId = null, ?string $from = null, ?string $to = null, array $opts = []): array
    {
        $row = $this->paidPayments($branchId, $from, $to, $opts)
            ->select("COUNT(*) AS n, COALESCE(SUM(pay.amount), 0) AS total, COALESCE(SUM(CASE WHEN pay.payment_method = 'cash' THEN pay.amount ELSE 0 END), 0) AS cash_total, COALESCE(SUM(CASE WHEN pay.payment_method = 'gcash' THEN pay.amount ELSE 0 END), 0) AS gcash_total", false)
            ->get()
            ->getRowArray();

        return [
            'total_transactions' => (int) ($row['n'] ?? 0),
            'total_amount'       => round((float) ($row['total'] ?? 0), 2),
            'cash_total'         => round((float) ($row['cash_total'] ?? 0), 2),
            'gcash_total'        => round((float) ($row['gcash_total'] ?? 0), 2),
        ];
    }

    /**
     * Collected money per calendar month.
     *
     * @return list<array{month_key:string,month_label:string,total_transactions:int,total_amount:float}>
     *         only months that have payments, oldest first
     */
    public function collectedByMonth(?int $branchId, string $from, string $to, array $opts = []): array
    {
        $rows = $this->paidPayments($branchId, $from, $to, $opts)
            ->select("DATE_FORMAT(pay.payment_date, '%Y-%m') AS month_key, DATE_FORMAT(pay.payment_date, '%b %Y') AS month_label, COUNT(*) AS total_transactions, COALESCE(SUM(pay.amount), 0) AS total_amount", false)
            ->groupBy("DATE_FORMAT(pay.payment_date, '%Y-%m'), DATE_FORMAT(pay.payment_date, '%b %Y')", false)
            ->orderBy('month_key', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['total_transactions'] = (int) $row['total_transactions'];
            $row['total_amount']       = round((float) $row['total_amount'], 2);
        }
        unset($row);

        return $rows;
    }

    /**
     * The last $months calendar months including this one, zero-filled
     * (for charts that always show N bars).
     *
     * @return list<array{month_key:string,month:string,label:string,amount:float}>
     */
    public function lastMonths(?int $branchId, int $months = 12): array
    {
        $months = max(1, $months);
        $first  = date('Y-m-01', strtotime('first day of -' . ($months - 1) . ' months'));
        $found  = [];
        foreach ($this->collectedByMonth($branchId, $first, date('Y-m-t')) as $row) {
            $found[$row['month_key']] = $row['total_amount'];
        }

        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts    = strtotime('first day of -' . $i . ' months');
            $key   = date('Y-m', $ts);
            $out[] = ['month_key' => $key, 'month' => date('M', $ts), 'label' => date('M Y', $ts), 'amount' => (float) ($found[$key] ?? 0)];
        }

        return $out;
    }

    /**
     * Collected money per day for one month (YYYY-MM).
     *
     * @return list<array{date:string,count:int,total:float}>
     */
    public function collectedByDay(?int $branchId, string $yearMonth): array
    {
        $from = $yearMonth . '-01';
        $to   = date('Y-m-t', strtotime($from));

        $rows = $this->paidPayments($branchId, $from, $to)
            ->select('DATE(pay.payment_date) AS date, COUNT(*) AS count, COALESCE(SUM(pay.amount), 0) AS total', false)
            ->groupBy('DATE(pay.payment_date)', false)
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $row['count'] = (int) $row['count'];
            $row['total'] = round((float) $row['total'], 2);
        }
        unset($row);

        return $rows;
    }

    /**
     * Member and plan-status counts for a branch (null = all branches).
     *
     * @return array{total_members:int,pending_members:int,active:int,grace:int,lapsed:int,forfeited:int,suspended:int,overdue_accounts:int,inactive_members:int}
     */
    public function memberCounts(?int $branchId = null): array
    {
        $db = db_connect();

        $holders = $db->table('plan_holders ph');
        if ($branchId !== null && $branchId > 0) {
            $holders->where('ph.branch_id', $branchId);
        }
        $total = (int) $holders->countAllResults();

        $pending = $db->table('plan_holders ph')->where('ph.status', 'pending');
        if ($branchId !== null && $branchId > 0) {
            $pending->where('ph.branch_id', $branchId);
        }
        $pendingCount = (int) $pending->countAllResults();

        $byStatus = ['active' => 0, 'fully_paid' => 0, 'grace' => 0, 'lapsed' => 0, 'forfeited' => 0];

        $builder = $db->table('plans p')
            ->select('p.lifecycle_status AS lifecycle, COUNT(DISTINCT ph.plan_holder_id) AS n', false)
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->whereIn('p.lifecycle_status', array_keys($byStatus))
            ->groupBy('p.lifecycle_status');
        if ($branchId !== null && $branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }
        foreach ($builder->get()->getResultArray() as $row) {
            $byStatus[(string) $row['lifecycle']] = (int) $row['n'];
        }

        $active = $byStatus['active'] + $byStatus['fully_paid'];

        return [
            'total_members'    => $total,
            'pending_members'  => $pendingCount,
            'active'           => $active,
            'grace'            => $byStatus['grace'],
            'lapsed'           => $byStatus['lapsed'],
            'forfeited'        => $byStatus['forfeited'],
            'suspended'        => $byStatus['lapsed'] + $byStatus['forfeited'],
            'overdue_accounts' => $byStatus['grace'] + $byStatus['lapsed'] + $byStatus['forfeited'],
            'inactive_members' => max(0, $total - $active),
        ];
    }

    /** Payments of any kind waiting to be verified. */
    public function pendingPayments(?int $branchId = null): int
    {
        $builder = db_connect()->table('payments pay')
            ->join('plans pl', 'pl.plan_id = pay.plan_id', 'left')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'left')
            ->where('pay.status', 'pending');

        if ($branchId !== null && $branchId > 0) {
            $builder->where('COALESCE(NULLIF(pay.branch_id, 0), ph.branch_id) =', $branchId, false);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * Registration approvals: the initial GCash payments of plan holders who
     * are not active yet - the same list as the Registration approvals queue.
     */
    public function pendingApprovals(?int $branchId = null): int
    {
        $builder = db_connect()->table('payments pay')
            ->join('plans pl', 'pl.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->where('pay.status', 'pending')
            ->where('pay.payment_method', 'gcash')
            ->where('ph.status', 'inactive');

        if ($branchId !== null && $branchId > 0) {
            $builder->where('pay.branch_id', $branchId);
        }

        return (int) $builder->countAllResults();
    }

    /** Service requests (applications) waiting for review. */
    public function pendingServiceRequests(?int $branchId = null): int
    {
        $builder = db_connect()->table('service_applications sa')
            ->join('plan_holders ph', 'ph.plan_holder_id = sa.plan_holder_id', 'inner')
            ->where('sa.status', 'pending');

        if ($branchId !== null && $branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * Numbers for the sidebar badges (keys match 'count' in Config\Nav).
     *
     * @return array<string,int>
     */
    public function navCounts(int $roleId, int $branchId): array
    {
        if ($roleId !== 2 && $roleId !== 3) {
            return [];
        }

        $counts = ['pending_requests' => $this->pendingServiceRequests($branchId)];

        if ($branchId > 0) {
            $counts['ids_needing_review'] = (new GovernmentIdVerificationService())->countNeedingReview($branchId);
        }

        return $counts;
    }
}
