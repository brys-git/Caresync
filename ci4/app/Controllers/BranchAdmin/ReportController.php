<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ReportPdfService;
use App\Services\ReportService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ReportController extends BaseController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function remittance()
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filters = $this->buildFilters([
            'date_from' => (string) ($this->request->getGet('date_from') ?? ''),
            'date_to' => (string) ($this->request->getGet('date_to') ?? ''),
            'payment_method' => (string) ($this->request->getGet('payment_method') ?? ''),
            'received_by' => (string) ($this->request->getGet('received_by') ?? ''),
            'search' => (string) ($this->request->getGet('search') ?? ''),
        ], $branchId);

        $mode = (string) $this->request->getGet('mode');
        if ($mode === 'csv') {
            return $this->exportCsv($filters);
        }

        if ($mode === 'pdf' || $mode === 'print') {
            return $this->remittancePdf($filters, $mode === 'print');
        }

        return view('branch_admin/reports/remittance', $this->buildViewData($filters, false));
    }

    /**
     * Panel brief, section 7: Overdue Report, scoped to this branch.
     */
    public function overdue()
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filters = ['branch_id' => $branchId, 'search' => cs_search_term($this->request->getGet('search'))]
            + $this->reportService->overduePeriod($this->request->getGet());
        $filters['period_line'] = $this->reportService->overduePeriodLine($filters);
        $rows = $this->reportService->getOverdueReport($filters);
        $mode = (string) $this->request->getGet('mode');

        if ($mode === 'csv') {
            return $this->simpleCsv('overdue_report', ['Plan Holder', 'Unique ID', 'Contact', 'Plan Balance', 'Package Balance', 'Months Paid', 'Plan Overdue Months', 'Plan Overdue Amount', 'Package Overdue Instalments', 'Package Overdue Amount', 'Total Overdue Amount', 'Days Overdue', 'Oldest Due Date', 'Status'], $rows, static fn (array $row): array => [
                trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
                (string) ($row['unique_identifier'] ?? ''),
                (string) ($row['contact_number'] ?? ''),
                number_format((float) ($row['remaining_balance'] ?? 0), 2, '.', ''),
                number_format((float) ($row['package_balance'] ?? 0), 2, '.', ''),
                (string) ($row['months_paid'] ?? 0),
                (string) ($row['overdue_months'] ?? 0),
                number_format((float) ($row['overdue_amount'] ?? 0), 2, '.', ''),
                (string) ($row['package_overdue_count'] ?? 0),
                number_format((float) ($row['package_overdue_amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['total_overdue_amount'] ?? 0), 2, '.', ''),
                (string) ($row['days_overdue'] ?? 0),
                (string) ($row['next_due_date'] ?? ''),
                (string) ($row['status_text'] ?? ''),
            ]);
        }

        if ($mode === 'pdf' || $mode === 'print') {
            return $this->pdfResponse('overdue', ['rows' => $rows], $branchId, ['period_line' => $filters['period_line']], $mode === 'print');
        }

        return view('admin/reports/overdue', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Overdue Report',
            'page_sub' => 'Plan holders behind on their contribution, oldest due date first.',
            'reports_base_path' => '/branch-admin/reports',
            'reports_hidden_tabs' => ['ledger', 'collections'],
            'active_report' => 'overdue',
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    /**
     * Avails and waivers for this branch.
     */
    public function avails()
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filters = $this->reportService->availsFilters($this->request->getGet(), $branchId);
        $rows = $this->reportService->getAvailsReport($filters);
        $mode = (string) $this->request->getGet('mode');

        if ($mode === 'csv') {
            return $this->simpleCsv('avails_report', $this->reportService->availsCsvHeaders(), $rows, fn (array $row): array => $this->reportService->availsCsvRow($row));
        }
        if ($mode === 'pdf' || $mode === 'print') {
            return $this->pdfResponse('avails', ['rows' => $rows, 'status_line' => $filters['status']], $branchId, ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']], $mode === 'print');
        }

        return view('admin/reports/avails', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Avails and Waivers',
            'page_sub' => 'Every package, service and add-on avail in your branch with its down payment, waiver, what has been paid and what is left.',
            'reports_base_path' => '/branch-admin/reports',
            'reports_hidden_tabs' => ['ledger', 'collections'],
            'active_report' => 'avails',
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    /**
     * Panel brief, section 7/8: Commission Report, scoped to this branch.
     */
    public function commission()
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filters = $this->buildFilters([
            'date_from' => (string) $this->request->getGet('date_from'),
            'date_to' => (string) $this->request->getGet('date_to'),
            'search' => (string) $this->request->getGet('search'),
        ], $branchId);

        $rows = $this->reportService->getCommissionReport($filters);
        $mode = (string) $this->request->getGet('mode');

        if ($mode === 'csv') {
            return $this->simpleCsv('commission_report', ['Collector', 'Remittances', 'Total Collected', 'Commission Earned (confirmed)', 'Commission Pending', 'Commission On Hold', 'Net Remitted (confirmed)', 'Net Waiting'], $rows, static fn (array $row): array => [
trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
                (string) ($row['remittances'] ?? 0),
                number_format((float) ($row['gross'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission_earned'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission_pending'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission_on_hold'] ?? 0), 2, '.', ''),
                number_format((float) ($row['net_confirmed'] ?? 0), 2, '.', ''),
                number_format((float) ($row['net_pending'] ?? 0), 2, '.', ''),
            ]);
        }

        if ($mode === 'pdf' || $mode === 'print') {
            return $this->pdfResponse('commission', ['rows' => $rows], $branchId, $filters, $mode === 'print');
        }

        return view('admin/reports/commission', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Commission Report',
            'page_sub' => 'What collectors remitted and the commission they earn (counted when you confirm the remittance).',
            'reports_base_path' => '/branch-admin/reports',
            'reports_hidden_tabs' => ['ledger', 'collections'],
            'active_report' => 'commission',
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    /** Monthly collection summary of this branch's collectors. */
    public function monthlySummary()
    {
        $this->ensureBranchAdminAccess();
        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }
        $filters = [
            'month_from' => \App\Services\CollectionMonthService::monthKeyOrEmpty((string) $this->request->getGet('month_from')),
            'month_to' => \App\Services\CollectionMonthService::monthKeyOrEmpty((string) $this->request->getGet('month_to')),
            'branch_id' => $branchId,
            'search' => cs_search_term($this->request->getGet('search')),
        ];
        $rows = (new \App\Services\CollectionMonthService())->summaries($filters);
        $mode = (string) $this->request->getGet('mode');

        if ($mode === 'csv') {
            return $this->simpleCsv('monthly_collection_summary', \App\Services\CollectionMonthService::csvHeaders(), $rows, static fn (array $r): array => \App\Services\CollectionMonthService::csvRow($r));
        }
        if ($mode === 'pdf' || $mode === 'print') {
            return $this->pdfResponse('monthly', ['rows' => $rows], $branchId, $filters + ['filter_lines' => []], $mode === 'print');
        }

        return view('admin/reports/monthly_summary', [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Monthly collection summary',
            'page_sub' => 'What each collector collected, did not collect and remitted, per closed month.',
            'reports_base_path' => '/branch-admin/reports',
            'reports_hidden_tabs' => ['ledger', 'collections'],
            'active_report' => 'monthly-summary',
            'detail_base' => 'branch-admin/remittances/months',
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    public function generate()
    {
        $this->ensureBranchAdminAccess();

        $branchId = (int) session('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filters = $this->buildFilters([
            'date_from' => (string) $this->request->getPost('date_from'),
            'date_to' => (string) $this->request->getPost('date_to'),
            'payment_method' => (string) $this->request->getPost('payment_method'),
            'received_by' => (string) $this->request->getPost('received_by'),
            'search' => (string) $this->request->getPost('search'),
        ], $branchId);

        $action = (string) $this->request->getPost('action');
        if ($action === 'csv') {
            return $this->exportCsv($filters);
        }

        if ($action === 'pdf' || $action === 'print') {
            return $this->remittancePdf($filters, $action === 'print');
        }

        return view('branch_admin/reports/remittance', $this->buildViewData($filters, false));
    }

    private function buildViewData(array $filters, bool $printMode): array
    {
        $rows = $this->reportService->getRemittanceReport($filters);
        $breakdown = $this->reportService->getPaymentBreakdown($filters);
        $totalRemittance = $this->reportService->getTotalRemittance($filters);
        // getBranchPaymentStaff() is really "everyone who has received_by'd a
        // payment in this branch" - despite the name, that already includes
        // Collectors (see CollectionListController's Record Payment), not
        // just role_id 3 Staff. Reused rather than duplicated: this is the
        // "each collector's recorded collections, with totals, and which
        // are still unverified" view the Collector capability work needed -
        // it already had the data, it just never showed status or a
        // pending breakdown.
        $staffOptions = $this->reportService->getBranchPaymentStaff((int) $filters['branch_id']);

        $pendingCount = 0;
        $pendingAmount = 0.0;
        foreach ($rows as $row) {
            if (strtolower((string) ($row['status'] ?? '')) === 'pending') {
                $pendingCount++;
                $pendingAmount += (float) ($row['amount'] ?? 0);
            }
        }
        $breakdown['pending_count'] = $pendingCount;
        $breakdown['pending_amount'] = $pendingAmount;

        return [
            'role_layout' => 'layouts/branch_admin',
            'page_title' => 'Remittance Report',
            'filters' => $filters,
            'report_rows' => $rows,
            'summary' => $breakdown,
            'total_remittance' => $totalRemittance,
            'split_totals' => $this->reportService->splitTotalLines($filters),
            'staff_options' => $staffOptions,
            'print_mode' => $printMode,
        ];
    }

    private function remittancePdf(array $filters, bool $inline)
    {
        $rows = $this->reportService->getRemittanceReport($filters);
        $breakdown = $this->reportService->getPaymentBreakdown($filters);

        // Same figures and labels as the on-screen summary cards + total.
        $totals = [
            ['label' => 'Cash Total', 'amount' => $breakdown['cash_total']],
            ['label' => 'GCash Total', 'amount' => $breakdown['gcash_total']],
            ...$this->reportService->splitTotalLines($filters),
            ['label' => 'Total Remittance', 'amount' => $this->reportService->getTotalRemittance($filters), 'strong' => true],
        ];

        $filterLines = [];
        if ($filters['payment_method'] !== '') {
            $filterLines[] = 'Method: ' . strtoupper($filters['payment_method']);
        }
        if ((int) $filters['received_by'] > 0) {
            foreach ($this->reportService->getBranchPaymentStaff((int) $filters['branch_id']) as $staff) {
                if ((int) $staff['user_id'] === (int) $filters['received_by']) {
                    $filterLines[] = 'Recorded by: ' . trim((string) $staff['first_name'] . ' ' . (string) $staff['last_name']);
                    break;
                }
            }
        }

        return $this->pdfResponse('remittance', ['rows' => $rows, 'totals' => $totals], (int) $filters['branch_id'], $filters + ['filter_lines' => $filterLines], $inline);
    }

    /**
     * Every Branch Admin report PDF goes through here so the header, layout and
     * disposition (inline for Print, attachment for Export PDF) stay identical.
     */
    private function pdfResponse(string $type, array $data, int $branchId, array $context, bool $inline)
    {
        $branch = $this->reportService->getBranchInfo($branchId);
        $context['branch_name'] = (string) ($branch['branch_name'] ?? '');

        return (new ReportPdfService())->respond($this->response, $type, $data, $context, $inline);
    }

    private function exportCsv(array $filters)
    {
        $rows = $this->reportService->getRemittanceReport($filters);

        $filename = 'remittance_report_' . date('Ymd_His') . '.csv';

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Date', 'Client Name', 'Amount', 'Plan Contribution', 'Package Instalment', 'Down Payment', 'Method', 'Reference', 'Received By']);

        foreach ($rows as $row) {
            fputcsv($stream, [
                (string) ($row['payment_date'] ?? ''),
                trim(((string) ($row['client_first'] ?? '')) . ' ' . ((string) ($row['client_last'] ?? ''))),
                number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                $row['plan_part'] === null ? '' : number_format((float) $row['plan_part'], 2, '.', ''),
                $row['instalment_part'] === null ? '' : number_format((float) $row['instalment_part'], 2, '.', ''),
                $row['down_part'] === null ? '' : number_format((float) $row['down_part'], 2, '.', ''),
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

    private function buildFilters(array $input, int $branchId): array
    {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');

        $dateFrom = trim((string) ($input['date_from'] ?? ''));
        $dateTo = trim((string) ($input['date_to'] ?? ''));
        $method = strtolower(trim((string) ($input['payment_method'] ?? '')));
        $receivedBy = (int) ($input['received_by'] ?? 0);

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

        if ($receivedBy > 0) {
            $staffRows = $this->reportService->getBranchPaymentStaff($branchId);
            $allowed = array_map(static fn ($row) => (int) $row['user_id'], $staffRows);
            if (! in_array($receivedBy, $allowed, true)) {
                $receivedBy = 0;
            }
        }

        return [
            'branch_id' => $branchId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'payment_method' => $method,
            'received_by' => $receivedBy,
            'search' => cs_search_term($input['search'] ?? ''),
        ];
    }

    private function simpleCsv(string $baseFilename, array $headers, array $rows, callable $rowMapper)
    {
        $filename = $baseFilename . '_' . date('Ymd_His') . '.csv';

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            fputcsv($stream, $rowMapper($row));
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody((string) $csv);
    }

    private function ensureBranchAdminAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 2 && $roleName !== 'branch admin') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
