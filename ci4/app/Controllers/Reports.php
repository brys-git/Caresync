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

    // ─── Remittance Report ──────────────────────────────────────

    public function remittance()
    {
        $filters = $this->buildRemittanceFilters();

        $mode = (string) $this->request->getGet('mode');
        if ($mode === 'csv') {
            return $this->exportCsv($filters);
        }
        if ($mode === 'pdf') {
            return $this->exportPdf($filters);
        }
        if ($mode === 'print') {
            return view('admin/reports/remittance_export', $this->buildExportViewData($filters, true));
        }

        return view('admin/reports/remittance', $this->buildRemittanceViewData($filters));
    }

    public function generate()
    {
        $filters = $this->buildRemittanceFilters(true);

        $action = (string) $this->request->getPost('action');
        if ($action === 'csv') {
            return $this->exportCsv($filters);
        }
        if ($action === 'pdf') {
            return $this->exportPdf($filters);
        }
        if ($action === 'print') {
            return view('admin/reports/remittance_export', $this->buildExportViewData($filters, true));
        }

        return view('admin/reports/remittance', $this->buildRemittanceViewData($filters));
    }

    private function buildRemittanceViewData(array $filters): array
    {
        $reportService = new ReportService();
        $rows = $reportService->getRemittanceReport($filters);
        $breakdown = $reportService->getPaymentBreakdown($filters);
        $totalRemittance = $reportService->getTotalRemittance($filters);
        $staffOptions = $reportService->getBranchPaymentStaff((int) ($filters['branch_id'] ?? 0));
        $branches = db_connect()->table('branches')->orderBy('branch_name', 'ASC')->get()->getResultArray();

        return [
            'role_layout' => 'layouts/admin',
            'filters' => $filters,
            'report_rows' => $rows,
            'summary' => $breakdown,
            'total_remittance' => $totalRemittance,
            'staff_options' => $staffOptions,
            'branches' => $branches,
        ];
    }

    private function buildExportViewData(array $filters, bool $autoPrint): array
    {
        $reportService = new ReportService();
        $rows = $reportService->getRemittanceReport($filters);
        $staffOptions = $reportService->getBranchPaymentStaff((int) ($filters['branch_id'] ?? 0));

        $branchId = (int) ($filters['branch_id'] ?? 0);
        $branch = $branchId > 0 ? $reportService->getBranchInfo($branchId) : null;

        $coordinatorName = '';
        $coordinatorContact = '';
        if ((int) ($filters['received_by'] ?? 0) > 0) {
            foreach ($staffOptions as $staff) {
                if ((int) $staff['user_id'] === (int) $filters['received_by']) {
                    $coordinatorName = trim(((string) ($staff['first_name'] ?? '')) . ' ' . ((string) ($staff['last_name'] ?? '')));
                    $coordinatorContact = (string) ($staff['contact_number'] ?? '');
                    break;
                }
            }
        }

        $exportRows = [];
        $maxRows = 30;
        $index = 1;

        foreach ($rows as $row) {
            if ($index > $maxRows) {
                break;
            }
            $monthMap = array_fill(1, 12, '');
            $monthNumber = (int) date('n', strtotime((string) ($row['payment_date'] ?? date('Y-m-d'))));
            if ($monthNumber >= 1 && $monthNumber <= 12) {
                $monthMap[$monthNumber] = number_format((float) ($row['amount'] ?? 0), 2);
            }
            $exportRows[] = [
                'no' => $index,
                'plan_holder_name' => trim(((string) ($row['client_first'] ?? '')) . ' ' . ((string) ($row['client_last'] ?? ''))),
                'control_no' => (string) ($row['unique_identifier'] ?? ''),
                'date_started' => (string) ($row['start_date'] ?? ''),
                'months' => $monthMap,
            ];
            $index++;
        }

        while (count($exportRows) < $maxRows) {
            $exportRows[] = [
                'no' => count($exportRows) + 1,
                'plan_holder_name' => '',
                'control_no' => '',
                'date_started' => '',
                'months' => array_fill(1, 12, ''),
            ];
        }

        $location = '';
        if ($branch) {
            $parts = array_filter([
                (string) ($branch['branch_name'] ?? ''),
                (string) ($branch['address_barangay'] ?? ''),
                (string) ($branch['address_city'] ?? ''),
                (string) ($branch['address_province'] ?? ''),
            ]);
            $location = implode(', ', $parts);
        }

        return [
            'export_rows' => $exportRows,
            'export_date' => date('Y-m-d'),
            'coordinator_name' => $coordinatorName,
            'coordinator_contact' => $coordinatorContact,
            'location_area' => $location,
            'auto_print' => $autoPrint,
        ];
    }

    private function exportPdf(array $filters)
    {
        $html = view('admin/reports/remittance_export', $this->buildExportViewData($filters, false));

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'remittance_report_' . date('Ymd_His') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    private function exportCsv(array $filters)
    {
        $reportService = new ReportService();
        $rows = $reportService->getRemittanceReport($filters);

        $filename = 'remittance_report_' . date('Ymd_His') . '.csv';

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Date', 'Client Name', 'Amount', 'Method', 'Reference', 'Received By']);

        foreach ($rows as $row) {
            fputcsv($stream, [
                (string) ($row['payment_date'] ?? ''),
                trim(((string) ($row['client_first'] ?? '')) . ' ' . ((string) ($row['client_last'] ?? ''))),
                number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                (string) ($row['payment_method'] ?? ''),
                (string) ($row['reference_number'] ?? ''),
                trim(((string) ($row['staff_first'] ?? '')) . ' ' . ((string) ($row['staff_last'] ?? ''))),
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody((string) $csv);
    }

    private function buildRemittanceFilters(bool $fromPost = false): array
    {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');

        if ($fromPost) {
            $dateFrom = trim((string) ($this->request->getPost('date_from') ?? ''));
            $dateTo = trim((string) ($this->request->getPost('date_to') ?? ''));
            $method = strtolower(trim((string) ($this->request->getPost('payment_method') ?? '')));
            $receivedBy = (int) $this->request->getPost('received_by');
            $branchId = (int) $this->request->getPost('branch_id');
        } else {
            $dateFrom = trim((string) ($this->request->getGet('date_from') ?? ''));
            $dateTo = trim((string) ($this->request->getGet('date_to') ?? ''));
            $method = strtolower(trim((string) ($this->request->getGet('payment_method') ?? '')));
            $receivedBy = (int) $this->request->getGet('received_by');
            $branchId = (int) $this->request->getGet('branch_id');
        }

        if ($dateFrom === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = $startOfMonth;
        }
        if ($dateTo === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $today;
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if (! in_array($method, ['', 'cash', 'gcash'], true)) {
            $method = '';
        }

        return [
            'branch_id' => $branchId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'payment_method' => $method,
            'received_by' => $receivedBy,
        ];
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
