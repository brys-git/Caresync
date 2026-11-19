<?php

namespace App\Services;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Official receipt for one paid payment, as a PDF.
 *
 * It reuses ReportPdfService (same logo/company header, layout and
 * public/assets/css/report-pdf.css as every report) and takes the plan's
 * remaining balance from ClientPlanSummary / PlanLedgerService, so a receipt
 * can never disagree with the plan pages.
 *
 * Who may download (enforced in forViewer(), never in the controller):
 *   Plan holder  - only payments on their own plan
 *   Branch Admin / Staff - only payments of clients in their own branch
 *   System Admin - any
 * Anything else, and any payment that is not 'paid', is "not found".
 */
class ReceiptPdfService
{
    /**
     * @param array{role_id:int,user_id:int,branch_id:int} $viewer
     *
     * @return array<string,mixed>|null null = not found / not allowed
     */
    public function forViewer(int $paymentId, array $viewer): ?array
    {
        if ($paymentId <= 0) {
            return null;
        }

        $db  = db_connect();
        $row = $db->table('payments pay')
            ->select('pay.*, p.plan_holder_id, p.package_id, p.program_id, ph.user_id AS holder_user_id, ph.branch_id AS holder_branch_id, ph.unique_identifier,'
                . ' u.first_name, u.middle_name, u.last_name, u.name_extension,'
                . ' pk.package_name, mp.program_name,'
                . ' rb.first_name AS rb_first, rb.last_name AS rb_last,'
                . ' vb.first_name AS vb_first, vb.last_name AS vb_last', false)
            ->join('plans p', 'p.plan_id = pay.plan_id', 'inner')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->join('users u', 'u.user_id = ph.user_id', 'left')
            ->join('packages pk', 'pk.package_id = p.package_id', 'left')
            ->join('membership_programs mp', 'mp.program_id = p.program_id', 'left')
            ->join('users rb', 'rb.user_id = pay.received_by', 'left')
            ->join('users vb', 'vb.user_id = pay.verified_by', 'left')
            ->where('pay.payment_id', $paymentId)
            ->get()
            ->getRowArray();

        if (! $row || (string) $row['status'] !== 'paid') {
            return null;
        }

        if (! $this->mayView($row, $viewer)) {
            return null;
        }

        return $row;
    }

    /**
     * Builds the PDF response for a payment row returned by forViewer().
     *
     * @param array<string,mixed> $row
     */
    public function respond(ResponseInterface $response, array $row, bool $inline = false): ResponseInterface
    {
        $db       = db_connect();
        $branchId = (int) ($row['holder_branch_id'] ?: $row['branch_id']);
        $branch   = $branchId > 0
            ? $db->table('branches')->where('branch_id', $branchId)->get()->getRowArray()
            : null;

        $receiptNo = trim((string) ($row['official_receipt_number'] ?? ''));
        if ($receiptNo === '') {
            $receiptNo = (new PaymentService())->generateReceiptNumber((int) $row['payment_id'], (string) ($row['payment_date'] ?? ''));
        }

        $summary = (new ClientPlanSummary())->forPlan((int) $row['plan_id']);

        // The ledger gives the balance as of today. This receipt shows the
        // balance right after THIS payment, so add back anything paid later.
        $paidLater = (float) ($db->table('payments')
            ->selectSum('amount', 'total')
            ->where('plan_id', (int) $row['plan_id'])
            ->where('status', 'paid')
            ->where('payment_id !=', (int) $row['payment_id'])
            ->groupStart()
                ->where('payment_date >', (string) $row['payment_date'])
                ->orGroupStart()
                    ->where('payment_date', (string) $row['payment_date'])
                    ->where('payment_id >', (int) $row['payment_id'])
                ->groupEnd()
            ->groupEnd()
            ->get()
            ->getRow('total') ?? 0);

        $remainingAfter = $summary !== null
            ? round((float) $summary['remaining_balance'] + $paidLater, 2)
            : null;

        $holderName = trim(preg_replace('/\s+/', ' ',
            ($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? '') . ' ' . ($row['name_extension'] ?? '')));

        $method = strtolower((string) ($row['payment_method'] ?? ''));

        $months = max(1, (int) ($row['months_covered'] ?? 1));

        $data = [
            'receipt_no'   => $receiptNo,
            'holder_name'  => $holderName,
            'holder_id'    => (string) (($row['unique_identifier'] ?? '') ?: ('PH-' . (int) $row['plan_holder_id'])),
            'program_name' => (string) (($row['program_name'] ?? '') ?: MembershipService::PROGRAM_NAME),
            'package_name' => (string) ($row['package_name'] ?? ''),
            'payment_date' => (string) ($row['payment_date'] ?? ''),
            'amount'       => (float) ($row['amount'] ?? 0),
            'discount'     => (float) ($row['discount_amount'] ?? 0),
            'discount_reason' => (string) ($row['discount_reason'] ?? ''),
            'method_label' => $method === 'gcash' ? 'GCash' : ($method === 'cash' ? 'Cash' : ucfirst($method)),
            'is_gcash'     => $method === 'gcash',
            'reference'    => (string) ($row['reference_number'] ?? ''),
            'months'       => $months,
            'coverage'     => cs_coverage_period($row['coverage_start'] ?? null, $months),
            'received_by'  => trim((string) ($row['rb_first'] ?? '') . ' ' . (string) ($row['rb_last'] ?? '')),
            'verified_by'  => trim((string) ($row['vb_first'] ?? '') . ' ' . (string) ($row['vb_last'] ?? '')),
            'remaining_after' => $remainingAfter,
            'applied_to'   => (new ClaimAvailService())->appliedTo((int) $row['payment_id']),
            'branch'       => $branch,
        ];

        return (new ReportPdfService())->respond($response, 'receipt', $data, [
            'branch_name'  => $branch['branch_name'] ?? '',
            'period_line'  => '',
            'filename'     => $this->filename($receiptNo, (string) ($row['last_name'] ?? '')),
        ], $inline);
    }

    /** Receipt-{OR number}-{Lastname}.pdf, with anything unsafe for a file name removed. */
    public function filename(string $receiptNo, string $lastName): string
    {
        $clean = static fn (string $s): string => trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '', str_replace(' ', '-', $s)), '-');

        $last = $clean($lastName);

        return 'Receipt-' . ($clean($receiptNo) ?: 'payment') . '-' . ($last !== '' ? $last : 'Client') . '.pdf';
    }

    /** @param array<string,mixed> $row */
    private function mayView(array $row, array $viewer): bool
    {
        $role = (int) ($viewer['role_id'] ?? 0);

        return match ($role) {
            1       => true,
            2, 3    => (int) $viewer['branch_id'] > 0 && (int) $row['holder_branch_id'] === (int) $viewer['branch_id'],
            4       => (int) $viewer['user_id'] > 0 && (int) $row['holder_user_id'] === (int) $viewer['user_id'],
            default => false,
        };
    }
}
