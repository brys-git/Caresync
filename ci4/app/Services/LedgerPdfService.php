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
            ->select('ph.plan_holder_id, ph.unique_identifier, ph.id_control_no, ph.branch_id, u.first_name, u.middle_name, u.last_name, u.name_extension, b.branch_name')
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

            // Money that went to a package is not part of the plan's own ledger: show only the plan slice
            $pkgSlice = [];
            foreach ($db->table('payment_allocations')->select('payment_id, SUM(amount) AS t', false)->where('plan_id', (int) $plan['plan_id'])->whereIn('target_type', ['avail_down_payment', 'avail_instalment'])->groupBy('payment_id')->get()->getResultArray() as $s) {
                $pkgSlice[(int) $s['payment_id']] = (float) $s['t'];
            }
            $running = 0.0;
            foreach ($rows as $r) {
                $r['amount'] = round((float) $r['amount'] - ($pkgSlice[(int) $r['payment_id']] ?? 0.0), 2);
                if ($r['amount'] <= 0.004) {
                    continue;
                }
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

        $sections = $plan ? $this->packageSections((int) $plan['plan_id']) : [];
        $pkgPaid = array_sum(array_column($sections, 'paid'));
        $pkgBalance = array_sum(array_column($sections, 'balance'));

        $name = trim(preg_replace('/\s+/', ' ', $holder['first_name'] . ' ' . $holder['middle_name'] . ' ' . $holder['last_name'] . ' ' . ($holder['name_extension'] ?? '')));

        return [
            'holder_name'   => $name,
            'last_name'     => (string) $holder['last_name'],
            'id_control_no' => IdControlService::label((string) ($holder['id_control_no'] ?? '')),
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
            'sections'      => $sections,
            'grand_paid'    => round(($summary !== null ? (float) $summary['total_paid'] : 0.0) + $pkgPaid, 2),
            'grand_balance' => round(($summary !== null ? (float) $summary['remaining_balance'] : 0.0) + $pkgBalance, 2),
            'generated'     => \CodeIgniter\I18n\Time::now(config('App')->appTimezone)->format('F j, Y, g:i A'),
        ];
    }

    /**
     * One section per package / service / add-on avail: its terms, down payment (or waiver note),
     * every payment applied to it with a running balance, and its status.
     *
     * @return list<array<string,mixed>>
     */
    private function packageSections(int $planId): array
    {
        $db = db_connect();
        if (! $db->tableExists('package_avails')) {
            return [];
        }
        $avails = $db->table('package_avails pa')
            ->select('pa.*, p.package_name, sl.service_name, w.first_name AS w_first, w.last_name AS w_last')
            ->join('packages p', 'p.package_id = pa.package_id', 'left')
            ->join('service_list sl', 'sl.service_list_id = pa.service_list_id', 'left')
            ->join('users w', 'w.user_id = pa.waived_by', 'left')
            ->where('pa.plan_id', $planId)->orderBy('pa.avail_id')->get()->getResultArray();

        $out = [];
        foreach ($avails as $a) {
            $lines = $db->table('payment_allocations al')
                ->select('al.amount, al.target_type, i.number AS inst_no, pay.payment_date, pay.payment_method, pay.official_receipt_number, pay.reference_number')
                ->join('payments pay', 'pay.payment_id = al.payment_id', 'inner')
                ->join('package_avail_instalments i', 'i.instalment_id = al.instalment_id', 'left')
                ->where('al.avail_id', (int) $a['avail_id'])->where('pay.status', 'paid')
                ->orderBy('pay.payment_date')->orderBy('al.allocation_id')->get()->getResultArray();

            $price = (float) $a['package_price']; $paid = 0.0; $rows = [];
            foreach ($lines as $l) {
                $paid += (float) $l['amount'];
                $m = strtolower((string) $l['payment_method']);
                $rows[] = [
                    'date' => (string) $l['payment_date'],
                    'what' => $l['target_type'] === 'avail_instalment' ? 'Instalment ' . (int) $l['inst_no'] . ' of ' . (int) $a['term_months'] : ($a['kind'] === 'addons' ? 'Add-on payment' : 'Down payment'),
                    'amount' => (float) $l['amount'],
                    'method' => $m === 'gcash' ? 'GCash' : ucfirst($m),
                    'ref' => (string) (($l['official_receipt_number'] ?? '') !== '' ? $l['official_receipt_number'] : ($l['reference_number'] ?? '')),
                    'balance' => round(max(0.0, $price - $paid), 2),
                ];
            }
            $cancelled = $a['status'] === 'cancelled';
            $waiver = '';
            if ($a['down_payment_status'] === 'waived') {
                $waiver = 'Down payment waived by ' . trim((string) $a['w_first'] . ' ' . (string) $a['w_last'])
                    . ($a['waived_at'] ? ' on ' . date('M j, Y', strtotime((string) $a['waived_at'])) : '') . '. Reason: ' . (string) $a['waiver_reason'];
            }
            $out[] = [
                'title'   => (string) ($a['package_name'] ?? $a['service_name'] ?? 'Package') . ($a['kind'] === 'addons' ? ' (add-on)' : ''),
                'price'   => $price,
                'down'    => $a['down_payment_status'] === 'waived' ? 'Waived' : cs_peso((float) $a['down_payment_amount']),
                'term'    => (int) $a['term_months'] > 0 ? cs_peso((float) $a['monthly_instalment']) . ' x ' . (int) $a['term_months'] . ' months' : 'Paid in full',
                'status'  => ['awaiting_down_payment' => 'Awaiting down payment', 'active' => 'Active', 'settled' => 'Settled', 'cancelled' => 'Cancelled'][$a['status']] ?? (string) $a['status'],
                'waiver'  => $waiver,
                'rows'    => $rows,
                'paid'    => round($paid, 2),
                'balance' => $cancelled ? 0.0 : round(max(0.0, $price - $paid), 2),
            ];
        }

        return $out;
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
