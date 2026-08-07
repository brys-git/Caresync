<?php

namespace App\Controllers\BranchAdmin;

use App\Controllers\BaseController;
use App\Services\ReportService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportController extends BaseController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function index()
    {
        return $this->remittance();
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
        ], $branchId);

        $mode = (string) $this->request->getGet('mode');
        if ($mode === 'csv') {
            return $this->exportCsv($filters);
        }

        if ($mode === 'pdf') {
            return $this->exportPdf($filters);
        }

        if ($mode === 'print') {
            return view('branch_admin/reports/remittance_export', $this->buildExportViewData($filters, true));
        }

        return view('branch_admin/reports/remittance', $this->buildViewData($filters, $mode === 'print'));
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
        ], $branchId);

        $action = (string) $this->request->getPost('action');
        if ($action === 'csv') {
            return $this->exportCsv($filters);
        }

        if ($action === 'pdf') {
            return $this->exportPdf($filters);
        }

        if ($action === 'print') {
            return view('branch_admin/reports/remittance_export', $this->buildExportViewData($filters, true));
        }

        return view('branch_admin/reports/remittance', $this->buildViewData($filters, $action === 'print'));
    }

    private function buildViewData(array $filters, bool $printMode): array
    {
        $rows = $this->reportService->getRemittanceReport($filters);
        $breakdown = $this->reportService->getPaymentBreakdown($filters);
        $totalRemittance = $this->reportService->getTotalRemittance($filters);
        $staffOptions = $this->reportService->getBranchPaymentStaff((int) $filters['branch_id']);

        return [
            'role_layout' => 'layouts/branch_admin',
            'filters' => $filters,
            'report_rows' => $rows,
            'summary' => $breakdown,
            'total_remittance' => $totalRemittance,
            'staff_options' => $staffOptions,
            'print_mode' => $printMode,
        ];
    }

    private function buildExportViewData(array $filters, bool $autoPrint): array
    {
        $rows = $this->reportService->getRemittanceReport($filters);
        $staffOptions = $this->reportService->getBranchPaymentStaff((int) $filters['branch_id']);
        $branch = $this->reportService->getBranchInfo((int) $filters['branch_id']);

        $coordinatorName = '';
        $coordinatorContact = '';
        if ((int) $filters['received_by'] > 0) {
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
        $html = view('branch_admin/reports/remittance_export', $this->buildExportViewData($filters, false));

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
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
        $rows = $this->reportService->getRemittanceReport($filters);

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
        ];
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
