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
            ->select('p.payment_id, p.payment_date, p.amount, p.months_covered, p.payment_method, p.reference_number, p.official_receipt_number, p.status, rb.first_name AS staff_first, rb.last_name AS staff_last, cu.first_name AS client_first, cu.last_name AS client_last, ph.plan_holder_id, ph.unique_identifier, pl.start_date');

        return $builder
            ->orderBy('p.payment_date', 'DESC')
            ->orderBy('p.payment_id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getTotalRemittance(array $filters): float
    {
        $row = $this->baseQuery($filters)
            ->select('COALESCE(SUM(p.amount), 0) AS total_remittance', false)
            ->get()
            ->getRowArray();

        return (float) ($row['total_remittance'] ?? 0);
    }

    public function getPaymentBreakdown(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->select("COUNT(*) AS total_transactions, COALESCE(SUM(p.amount), 0) AS total_amount, COALESCE(SUM(CASE WHEN p.payment_method = 'cash' THEN p.amount ELSE 0 END), 0) AS cash_total, COALESCE(SUM(CASE WHEN p.payment_method = 'gcash' THEN p.amount ELSE 0 END), 0) AS gcash_total", false)
            ->get()
            ->getRowArray();

        return [
            'total_transactions' => (int) ($row['total_transactions'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
            'cash_total' => (float) ($row['cash_total'] ?? 0),
            'gcash_total' => (float) ($row['gcash_total'] ?? 0),
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
        $branchId = (int) ($filters['branch_id'] ?? 0);

        $builder = db_connect()->table('payments p')
            ->select("DATE_FORMAT(p.payment_date, '%Y-%m') AS month_key, DATE_FORMAT(p.payment_date, '%b %Y') AS month_label, COUNT(*) AS total_transactions, COALESCE(SUM(p.amount), 0) AS total_amount", false)
            ->where('YEAR(p.payment_date)', $year, false)
            // Single-quoted PHP string: \" is not an escape sequence here, it
            // stays as a literal backslash-plus-quote and corrupts the SQL
            // (MySQL sees a stray backslash where it expects the GROUP BY
            // expression to just end) - use a real double quote instead.
            ->groupBy('DATE_FORMAT(p.payment_date, "%Y-%m")', false)
            ->orderBy('month_key', 'ASC');

        if ($branchId > 0) {
            $builder->where('p.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    public function getMemberStatusSummary(array $filters): array
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);

        $builder = db_connect()->table('plan_holders ph')
            ->select("COUNT(*) AS total_members, SUM(CASE WHEN ph.status = 'active' THEN 1 ELSE 0 END) AS active_members, SUM(CASE WHEN ph.status <> 'active' THEN 1 ELSE 0 END) AS inactive_members", false);

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        $row = $builder->get()->getRowArray();

        return [
            'total_members' => (int) ($row['total_members'] ?? 0),
            'active_members' => (int) ($row['active_members'] ?? 0),
            'inactive_members' => (int) ($row['inactive_members'] ?? 0),
        ];
    }

    public function getDelinquentAccounts(array $filters): array
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);
        $today = date('Y-m-d');

        $builder = db_connect()->table('plans pl')
            ->select('pl.plan_id, pl.remaining_balance, pl.months_paid, pl.status AS plan_status, pl.next_due_date, ph.unique_identifier, u.first_name, u.last_name, b.branch_name, pl.start_date')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->where('pl.remaining_balance >', 0)
            ->where('pl.status !=', 'completed')
            ->where('pl.next_due_date <', $today)
            ->orderBy('pl.remaining_balance', 'DESC')
            ->limit(25);

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
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
        $year = (int) ($filters['year'] ?? date('Y'));
        $branchId = (int) ($filters['branch_id'] ?? 0);

        $builder = db_connect()->table('payments p')
            ->select("DATE_FORMAT(p.payment_date, '%Y-%m') AS month_key, DATE_FORMAT(p.payment_date, '%b %Y') AS month_label, SUM(CASE WHEN p.payment_method = 'cash' THEN p.amount ELSE 0 END) AS cash_total, SUM(CASE WHEN p.payment_method = 'gcash' THEN p.amount ELSE 0 END) AS gcash_total", false)
            ->where('YEAR(p.payment_date)', $year, false)
            ->groupBy('DATE_FORMAT(p.payment_date, "%Y-%m")', false)
            ->orderBy('month_key', 'ASC');

        if ($branchId > 0) {
            $builder->where('p.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Panel brief, section 7: "Overdue Report - lists plan holders who are
     * behind on payments." Same conditions getDelinquentAccounts() already
     * uses for its capped dashboard widget, but as a real, unlimited,
     * exportable report.
     */
    public function getOverdueReport(array $filters): array
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);
        $today = date('Y-m-d');

        $builder = db_connect()->table('plans pl')
            ->select('pl.plan_id, pl.remaining_balance, pl.months_paid, pl.overdue_months, pl.status AS plan_status, pl.next_due_date, pl.payment_coverage_until, pl.membership_state, ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name, u.contact_number, b.branch_name')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->where('pl.remaining_balance >', 0)
            ->where('pl.status !=', 'completed')
            ->where('pl.next_due_date <', $today)
            ->orderBy('pl.next_due_date', 'ASC');

        if ($branchId > 0) {
            $builder->where('ph.branch_id', $branchId);
        }

        $rows = $builder->get()->getResultArray();

        $overduePolicy = new OverduePolicyService();

        foreach ($rows as &$row) {
            $dueDate = (string) ($row['next_due_date'] ?? '');
            $row['days_overdue'] = $dueDate !== '' ? max(0, (int) floor((strtotime($today) - strtotime($dueDate)) / 86400)) : 0;

            // Panel brief, section 8: the forfeiture countdown is measured
            // from payment_coverage_until (what OverduePolicyService itself
            // checks), which is a day or two ahead of next_due_date.
            $coverageUntil = (string) ($row['payment_coverage_until'] ?? '');
            $daysPastCoverage = $coverageUntil !== '' ? max(0, (int) floor((strtotime($today) - strtotime($coverageUntil)) / 86400)) : 0;
            $row['days_until_forfeiture'] = (int) $row['months_paid'] > 0
                ? $overduePolicy->daysUntilForfeiture($daysPastCoverage)
                : null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Plan-holder search for the Ledger report (by name or unique
     * identifier).
     */
    public function searchPlanHolders(string $query, int $branchId = 0): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $builder = db_connect()->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, u.first_name, u.last_name, b.branch_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->groupStart()
                ->like('u.first_name', $query)
                ->orLike('u.last_name', $query)
                ->orLike('ph.unique_identifier', $query)
            ->groupEnd()
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
            ->select('u.user_id, u.first_name, u.last_name, r.role_name, COUNT(p.payment_id) AS transaction_count, COALESCE(SUM(p.amount), 0) AS total_collected', false)
            ->groupBy('u.user_id, u.first_name, u.last_name, r.role_name')
            ->orderBy('total_collected', 'DESC');

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['total_collected'] = (float) ($row['total_collected'] ?? 0);
            $row['transaction_count'] = (int) ($row['transaction_count'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    /**
     * Panel brief, section 7/8: "Commission Report - commission earned per
     * agent/collector," at the section 8 rate (10% of amount collected).
     */
    public function getCommissionReport(array $filters): array
    {
        $rows = $this->getCollectionsByCollector($filters);

        foreach ($rows as &$row) {
            $commission = round($row['total_collected'] * self::COMMISSION_RATE, 2);
            $row['commission'] = $commission;
            $row['net_remitted'] = round($row['total_collected'] - $commission, 2);
        }
        unset($row);

        return $rows;
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

        return $builder;
    }

    private function baseQuery(array $filters)
    {
        $builder = db_connect()->table('payments p')
            ->join('users rb', 'rb.user_id = p.received_by', 'inner')
            ->join('plans pl', 'pl.plan_id = p.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users cu', 'cu.user_id = ph.user_id', 'inner')
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

        return $builder;
    }
}
