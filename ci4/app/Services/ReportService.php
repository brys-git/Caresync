<?php

namespace App\Services;

class ReportService
{
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
            ->groupBy('DATE_FORMAT(p.payment_date, \"%Y-%m\")', false)
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
            ->select('COALESCE(sl.service_name, \"Unknown\") AS service_name, COUNT(*) AS total_services, SUM(CASE WHEN s.status = \"completed\" THEN 1 ELSE 0 END) AS completed_services, SUM(CASE WHEN s.status = \"cancelled\" THEN 1 ELSE 0 END) AS cancelled_services', false)
            ->join('service_list sl', 'sl.service_list_id = s.service_list_id', 'left')
            ->groupBy('COALESCE(sl.service_name, \"Unknown\")', false)
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
            ->groupBy('DATE_FORMAT(p.payment_date, \"%Y-%m\")', false)
            ->orderBy('month_key', 'ASC');

        if ($branchId > 0) {
            $builder->where('p.branch_id', $branchId);
        }

        return $builder->get()->getResultArray();
    }

    private function baseQuery(array $filters)
    {
        $builder = db_connect()->table('payments p')
            ->join('users rb', 'rb.user_id = p.received_by', 'inner')
            ->join('plans pl', 'pl.plan_id = p.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = pl.plan_holder_id', 'inner')
            ->join('users cu', 'cu.user_id = ph.user_id', 'inner')
            ->where('p.branch_id', (int) $filters['branch_id'])
            ->where('p.payment_date >=', (string) $filters['date_from'])
            ->where('p.payment_date <=', (string) $filters['date_to']);

        if (! empty($filters['payment_method'])) {
            $builder->where('p.payment_method', (string) $filters['payment_method']);
        }

        if (! empty($filters['received_by'])) {
            $builder->where('p.received_by', (int) $filters['received_by']);
        }

        return $builder;
    }
}
