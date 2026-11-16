<?php

namespace App\Services;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * The plan holder's ledger as a PDF: the plan, every VERIFIED (remitted)
 * payment, the total paid and the remaining balance.
 *
 * Numbers for the plan (contract total, total paid, remaining balance) come
 * from ClientPlanSummary / PlanLedgerService, the same source as every other
 * page. Who may ask for it is decided by the callers: the client downloads
 * only their own (Client\ClientPaymentController), Branch Admin / Staff go
 * through ClientDetailController, which already returns "not found" for a
 * plan holder in another branch.
 */
class LedgerPdfService
{
    /**
     * @return array<string,mixed>|null null when the plan holder has no user/plan record
     */
    public function build(int $planHolderId): ?array
    {
        $db = db_connect();

        $holder = $db->table('plan_holders ph')
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.branch_id, u.first_name, u.middle_name, u.last_name, u.name_extension, b.branch_name')
            ->join('users u', 'u.user_id = ph.user_id', 'inner')
            ->join('branches b', 'b.branch_id = ph.branch_id', 'left')
            ->where('ph.plan_holder_id', $planHolderId)
            ->get()
            ->getRowArray();

        if (! $holder) {
            return null;
        }

        $plan = $db->table('plans p')
            ->select('p.*, pk.package_name, mp.program_name')
            ->join('packages pk', 'pk.package_id = p.package_id', 'left')
            ->join('membership_programs mp', 'mp.program_id = p.program_id', 'left')
            ->where('p.plan_holder_id', $planHolderId)
            ->orderBy("CASE WHEN p.status = 'active' THEN 0 ELSE 1 END", 'ASC', false)
            ->orderBy('p.plan_id', 'DESC')
            ->get()
            ->getRowArray();

        $summary = $plan ? (new ClientPlanSummary())->forPlan((int) $plan['plan_id']) : null;

        $payments = [];
        if ($plan) {
            $rows = $db->table('payments pay')
                ->select('pay.payment_id, pay.payment_date, pay.amount, pay.months_covered, pay.payment_method, pay.reference_number, pay.official_receipt_number, pay.coverage_start, rb.first_name AS rb_first, rb.last_name AS rb_last')
                ->join('users rb', 'rb.user_id = pay.received_by', 'left')
                ->where('pay.plan_id', (int) $plan['plan_id'])
                ->where('pay.status', 'paid')
                ->where('pay.deleted_at IS NULL', null, false)
                ->orderBy('pay.payment_date', 'ASC')
                ->orderBy('pay.payment_id', 'ASC')
                ->get()
                ->getResultArray();

            $running = 0.0;
            foreach ($rows as $r) {
                $running += (float) $r['amount'];
                $months = max(1, (int) $r['months_covered']);
                $method = strtolower((string) $r['payment_method']);
                $payments[] = [
                    'date'     => (string) $r['payment_date'],
                    'period'   => strip_tags(cs_coverage_period($r['coverage_start'] ?? null, $months)),
                    'amount'   => (float) $r['amount'],
                    'method'   => $method === 'gcash' ? 'GCash' : ($method === 'cash' ? 'Cash' : ucfirst($method)),
                    'ref'      => (string) (($r['official_receipt_number'] ?? '') !== '' ? $r['official_receipt_number'] : ($r['reference_number'] ?? '')),
                    'received' => trim((string) $r['rb_first'] . ' ' . (string) $r['rb_last']),
                    'running'  => round($running, 2),
                ];
            }
        }

        $name = trim(preg_replace('/\s+/', ' ', $holder['first_name'] . ' ' . $holder['middle_name'] . ' ' . $holder['last_name'] . ' ' . ($holder['name_extension'] ?? '')));

        return [
            'holder_name'   => $name,
            'last_name'     => (string) $holder['last_name'],
            'plan_no'       => (string) (($holder['unique_identifier'] ?? '') ?: ('PH-' . $planHolderId)),
            'branch_name'   => (string) ($holder['branch_name'] ?? ''),
            'plan_name'     => $plan ? trim((string) (($plan['program_name'] ?? '') ?: MembershipService::PROGRAM_NAME) . (($plan['package_name'] ?? '') !== '' ? ' - ' . $plan['package_name'] : '')) : '',
            'monthly_fee'   => $plan ? (float) $plan['monthly_fee'] : 0.0,
            'contract'      => $summary !== null ? (float) $summary['contract_amount'] : ($plan ? PlanLedgerService::targetFor($plan) : 0.0),
            'total_paid'    => $summary !== null ? (float) $summary['total_paid'] : 0.0,
            'remaining'     => $summary !== null ? (float) $summary['remaining_balance'] : 0.0,
            'months_paid'   => $summary !== null ? (int) $summary['months_paid'] : 0,
            'total_months'  => $summary !== null ? (int) $summary['total_months'] : 0,
            'payments'      => $payments,
            'generated'     => \CodeIgniter\I18n\Time::now(config('App')->appTimezone)->format('F j, Y, g:i A'),
        ];
    }

    /** Renders the ledger PDF as a download (or inline when $inline). */
    public function respond(ResponseInterface $response, array $data, bool $inline = false): ResponseInterface
    {
        $clean = static fn (string $s): string => trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '', str_replace(' ', '-', $s)), '-');
        $file  = 'Ledger-' . ($clean($data['plan_no']) ?: 'plan') . '-' . ($clean($data['last_name']) ?: 'Client') . '.pdf';

        return (new ReportPdfService())->respond($response, 'ledger', $data, [
            'branch_name' => $data['branch_name'],
            'period_line' => 'As of ' . date('F j, Y'),
            'filename'    => $file,
        ], $inline);
    }
}
