<?php

namespace App\Controllers\Staff;

use App\Controllers\BaseController;
use App\Models\PaymentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportsController extends BaseController
{
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
    }

    public function index()
    {
        $this->ensureStaffAccess();

        $branchId = (int) session()->get('branch_id');
        if ($branchId <= 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        $filters = $this->buildFilters([
            'date_from' => (string) ($this->request->getGet('date_from') ?? ''),
            'date_to' => (string) ($this->request->getGet('date_to') ?? ''),
            'payment_method' => (string) ($this->request->getGet('payment_method') ?? ''),
            'status' => (string) ($this->request->getGet('status') ?? ''),
            'received_by' => (string) ($this->request->getGet('received_by') ?? ''),
            'search' => (string) ($this->request->getGet('search') ?? ''),
        ], $branchId);

        $mode = (string) $this->request->getGet('mode');
        if ($mode === 'csv') {
            return $this->exportCsv($filters);
        }

        if ($mode === 'pdf') {
            return $this->exportPdf($filters);
        }

        if ($mode === 'print') {
            return view('staff/reports/remittance_export', $this->buildExportViewData($filters, true));
        }

        return view('staff/reports/index', $this->buildViewData($filters, false));
    }

    private function buildViewData(array $filters, bool $printMode): array
    {
        $rows = $this->getRows($filters);
        $summary = $this->getSummary($filters);

        return [
            'role_layout' => 'layouts/staff',
            'filters' => $filters,
            'report_rows' => $rows,
            'summary' => $summary,
            'staff_options' => $this->getStaffOptions((int) $filters['branch_id']),
            'print_mode' => $printMode,
        ];
    }

    private function getRows(array $filters): array
    {
        $builder = $this->baseQuery($filters)
            ->select('p.payment_id, p.amount, p.months_covered, p.payment_date, p.payment_method, p.reference_number, p.official_receipt_number, p.status, rb.first_name AS staff_first, rb.last_name AS staff_last, cu.first_name AS client_first, cu.last_name AS client_last, ph.unique_identifier, pl.start_date')
            ->orderBy('p.payment_date', 'DESC')
            ->orderBy('p.payment_id', 'DESC');

        return $builder->get()->getResultArray();
    }

    private function getSummary(array $filters): array
    {
        $row = $this->baseQuery($filters)
            ->select("COUNT(*) AS total_transactions, COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) AS total_collected, COALESCE(SUM(CASE WHEN p.status = 'pending' THEN p.amount ELSE 0 END), 0) AS total_pending, COALESCE(SUM(CASE WHEN p.status = 'cancelled' THEN p.amount ELSE 0 END), 0) AS total_cancelled", false)
            ->get()
            ->getRowArray();

        return [
            'total_transactions' => (int) ($row['total_transactions'] ?? 0),
            'total_collected' => (float) ($row['total_collected'] ?? 0),
            'total_pending' => (float) ($row['total_pending'] ?? 0),
            'total_cancelled' => (float) ($row['total_cancelled'] ?? 0),
        ];
    }

    private function getStaffOptions(int $branchId): array
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

    private function buildExportViewData(array $filters, bool $autoPrint): array
    {
        $rows = $this->getRows($filters);
        $staffOptions = $this->getStaffOptions((int) $filters['branch_id']);
        $branch = db_connect()->table('branches')
            ->select('branch_id, branch_name, address_street, address_barangay, address_city, address_province')
            ->where('branch_id', (int) $filters['branch_id'])
            ->get()
            ->getRowArray();

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

        if (! empty($filters['status'])) {
            $builder->where('p.status', (string) $filters['status']);
        }

        if (! empty($filters['received_by'])) {
            $builder->where('p.received_by', (int) $filters['received_by']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $builder->groupStart()
                ->like('cu.first_name', $search)
                ->orLike('cu.last_name', $search)
                ->orLike("CONCAT(cu.first_name, ' ', cu.last_name)", $search, 'both', false)
                ->orLike('ph.unique_identifier', $search)
                ->orLike('p.reference_number', $search)
                ->groupEnd();
        }

        return $builder;
    }

    private function buildFilters(array $input, int $branchId): array
    {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');

        $dateFrom = trim((string) ($input['date_from'] ?? ''));
        $dateTo = trim((string) ($input['date_to'] ?? ''));
        $method = strtolower(trim((string) ($input['payment_method'] ?? '')));
        $status = strtolower(trim((string) ($input['status'] ?? '')));
        $receivedBy = (int) ($input['received_by'] ?? 0);
        $search = trim((string) ($input['search'] ?? ''));

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

        if (! in_array($status, ['', 'paid', 'pending', 'cancelled'], true)) {
            $status = '';
        }

        if ($receivedBy > 0) {
            $allowed = array_map(static fn ($row) => (int) $row['user_id'], $this->getStaffOptions($branchId));
            if (! in_array($receivedBy, $allowed, true)) {
                $receivedBy = 0;
            }
        }

        if (strlen($search) > 100) {
            $search = substr($search, 0, 100);
        }

        return [
            'branch_id' => $branchId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'payment_method' => $method,
            'status' => $status,
            'received_by' => $receivedBy,
            'search' => $search,
        ];
    }

    private function exportCsv(array $filters)
    {
        $rows = $this->getRows($filters);
        $filename = 'staff_remittance_report_' . date('Ymd_His') . '.csv';

        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Plan Holder Name', 'Amount', 'Payment Date', 'Payment Method', 'Reference Number', 'Received By', 'Status']);

        foreach ($rows as $row) {
            fputcsv($stream, [
                trim(((string) ($row['client_first'] ?? '')) . ' ' . ((string) ($row['client_last'] ?? ''))),
                number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                (string) ($row['payment_date'] ?? ''),
                (string) ($row['payment_method'] ?? ''),
                (string) ($row['reference_number'] ?? ''),
                trim(((string) ($row['staff_first'] ?? '')) . ' ' . ((string) ($row['staff_last'] ?? ''))),
                (string) ($row['status'] ?? ''),
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

    private function exportPdf(array $filters)
    {
        $html = view('staff/reports/remittance_export', $this->buildExportViewData($filters, false));

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'staff_remittance_report_' . date('Ymd_His') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    private function ensureStaffAccess(): void
    {
        $roleId = (int) session()->get('role_id');
        $roleName = strtolower((string) session()->get('role'));

        if ($roleId !== 3 && $roleName !== 'staff') {
            redirect()->to('/unauthorized')->send();
            exit;
        }
    }
}
