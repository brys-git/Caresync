<?php

namespace App\Controllers;

use App\Services\ReportService;

class Reports extends BaseController
{
    public function index(): string
    {
        $roleId = (int) session('role_id');
        $branchId = (int) session('branch_id');
        $reportService = new ReportService();
        $filters = [
            'branch_id' => $roleId === 1 ? 0 : $branchId,
            'date_from' => date('Y-01-01'),
            'date_to' => date('Y-m-d'),
            'year' => (int) date('Y'),
        ];

        $payments = $reportService->getRemittanceReport($filters);
        $branches = $this->buildBranchSummary($branchId, $roleId);
        $services = $this->buildServiceSummary($branchId, $roleId);
        $clients = $this->buildClientSummary($branchId, $roleId);
        $staffActivity = $this->buildStaffSummary($branchId, $roleId);
        $summary = $reportService->getDashboardSummary($filters);

        if ($roleId === 4) {
            $payments = [];
            $branches = [];
            $services = [];
            $clients = [];
            $staffActivity = [];
            $summary = [
                'monthly_collections' => [],
                'payment_breakdown' => ['total_transactions' => 0, 'total_amount' => 0, 'cash_total' => 0, 'gcash_total' => 0],
                'member_status' => ['total_members' => 0, 'active_members' => 0, 'inactive_members' => 0],
                'delinquent_accounts' => [],
                'service_usage' => [],
                'payment_trends' => [],
            ];
        }

        return view('reports/index', [
            'role_layout' => $this->resolveLayoutView(),
            'payments' => $payments,
            'branches' => $branches,
            'services' => $services,
            'clients' => $clients,
            'staff_activity' => $staffActivity,
            'summary' => $summary,
            'report_scope' => $roleId === 1 ? 'System-wide' : ($roleId === 2 ? 'Branch' : 'Staff'),
        ]);
    }

    private function buildBranchSummary(int $branchId, int $roleId): array
    {
        $db = db_connect();
        $sql = "SELECT b.branch_id, b.branch_name, COUNT(DISTINCT u.user_id) AS staff_count, COUNT(DISTINCT ph.plan_holder_id) AS client_count, COUNT(DISTINCT s.service_id) AS service_count, COALESCE(SUM(CASE WHEN pay.status = 'paid' THEN pay.amount ELSE 0 END), 0) AS revenue
             FROM branches b
             LEFT JOIN users u ON u.branch_id = b.branch_id
             LEFT JOIN plan_holders ph ON ph.branch_id = b.branch_id
             LEFT JOIN services s ON s.branch_id = b.branch_id
             LEFT JOIN payments pay ON pay.branch_id = b.branch_id";

        if (in_array($roleId, [2, 3], true) && $branchId > 0) {
            $sql .= ' WHERE b.branch_id = ' . $db->escape($branchId);
        }

        $sql .= ' GROUP BY b.branch_id, b.branch_name ORDER BY revenue DESC, b.branch_name ASC';

        return $db->query($sql)->getResultArray();
    }

    private function buildServiceSummary(int $branchId, int $roleId): array
    {
        $db = db_connect();
        $sql = "SELECT s.service_id, COALESCE(sl.service_name, '-') AS service_type, s.status, s.total_cost, s.service_date, b.branch_name, u.first_name, u.last_name
             FROM services s
             LEFT JOIN service_list sl ON sl.service_list_id = s.service_list_id
             INNER JOIN branches b ON b.branch_id = s.branch_id
             INNER JOIN plan_holders ph ON ph.plan_holder_id = s.plan_holder_id
             INNER JOIN users u ON u.user_id = ph.user_id";

        if (in_array($roleId, [2, 3], true) && $branchId > 0) {
            $sql .= ' WHERE s.branch_id = ' . $db->escape($branchId);
        }

        $sql .= ' ORDER BY s.service_id DESC LIMIT 25';

        return $db->query($sql)->getResultArray();
    }

    private function buildClientSummary(int $branchId, int $roleId): array
    {
        $db = db_connect();
        $sql = "SELECT ph.plan_holder_id, ph.unique_identifier, ph.status, b.branch_name, u.first_name, u.last_name
             FROM plan_holders ph
             INNER JOIN users u ON u.user_id = ph.user_id
             INNER JOIN branches b ON b.branch_id = ph.branch_id";

        if (in_array($roleId, [2, 3], true) && $branchId > 0) {
            $sql .= ' WHERE ph.branch_id = ' . $db->escape($branchId);
        }

        $sql .= ' ORDER BY ph.plan_holder_id DESC LIMIT 25';

        return $db->query($sql)->getResultArray();
    }

    private function buildStaffSummary(int $branchId, int $roleId): array
    {
        $db = db_connect();
        $sql = "SELECT u.user_id, u.first_name, u.last_name, COUNT(DISTINCT a.assignment_id) AS tasks_assigned, COUNT(DISTINCT pay.payment_id) AS payments_handled, COUNT(DISTINCT s.service_id) AS services_handled
             FROM users u
             LEFT JOIN assignments a ON a.staff_id = u.user_id
             LEFT JOIN payments pay ON pay.received_by = u.user_id
             LEFT JOIN services s ON s.assigned_staff = u.user_id
             WHERE u.role_id = 3";

        if (in_array($roleId, [2, 3], true) && $branchId > 0) {
            $sql .= ' AND u.branch_id = ' . $db->escape($branchId);
        }

        $sql .= ' GROUP BY u.user_id, u.first_name, u.last_name ORDER BY tasks_assigned DESC, payments_handled DESC';

        return $db->query($sql)->getResultArray();
    }

    private function resolveLayoutView(): string
    {
        $role = (int) session()->get('role_id');

        if ($role === 1) {
            return 'layouts/admin';
        }

        if ($role === 2) {
            return 'layouts/branch_admin';
        }

        if ($role === 3) {
            return 'layouts/staff';
        }

        return 'layouts/plan_holder';
    }
}
