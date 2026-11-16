<?php

namespace App\Services;

/**
 * Paying collectors their commission.
 *
 * The amount owed is ALWAYS worked out here from the payments (through
 * CommissionService::reportRows(), the same rule the Commission report uses) -
 * the browser only says which collector and which period. A payout stores that
 * period, what was collected and the commission paid. A collector can never be
 * paid twice for the same stretch of time: any payout whose period overlaps the
 * new one blocks it.
 */
class CommissionPayoutService
{
    public static function ready(): bool
    {
        return db_connect()->tableExists('commission_payouts');
    }

    /**
     * Adds Earned / Paid / Unpaid to the commission report rows.
     *   paid   = payouts whose whole period lies inside the viewed period
     *   unpaid = earned - paid (never below 0)
     *   can_pay = nothing overlaps yet, both dates are set and there is something to pay
     *
     * @param list<array<string,mixed>> $rows from CommissionService::reportRows()
     *
     * @return list<array<string,mixed>>
     */
    public function annotate(array $rows, ?string $from, ?string $to, ?int $branchId = null): array
    {
        foreach ($rows as &$row) {
            $row['earned'] = (float) $row['commission'];
            $row['paid'] = 0.0;
            $row['unpaid'] = $row['earned'];
            $row['can_pay'] = false;
            $row['payout'] = null;
        }
        unset($row);

        if (! self::ready() || $rows === []) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $collector = (int) $row['user_id'];

            $paidQuery = db_connect()->table('commission_payouts')->selectSum('commission_amount', 'total')->where('collector_user_id', $collector);
            if ($branchId) {
                $paidQuery->where('branch_id', $branchId);
            }
            if (! empty($from)) {
                $paidQuery->where('period_start >=', $from);
            }
            if (! empty($to)) {
                $paidQuery->where('period_end <=', $to);
            }
            $row['paid'] = round((float) ($paidQuery->get()->getRow('total') ?? 0), 2);
            $row['unpaid'] = max(0.0, round($row['earned'] - $row['paid'], 2));

            $overlap = ! empty($from) && ! empty($to) ? $this->overlapping($collector, $from, $to) : null;
            $row['payout'] = $overlap;
            $row['can_pay'] = ! empty($from) && ! empty($to) && $overlap === null && $row['earned'] > 0.004;
        }
        unset($row);

        return $rows;
    }

    /** A payout of this collector whose period overlaps from..to, or null. */
    public function overlapping(int $collectorUserId, string $from, string $to): ?array
    {
        if (! self::ready()) {
            return null;
        }

        return db_connect()->table('commission_payouts')
            ->where('collector_user_id', $collectorUserId)
            ->where('period_start <=', $to)
            ->where('period_end >=', $from)
            ->orderBy('payout_id', 'DESC')
            ->get(1)
            ->getRowArray() ?: null;
    }

    /**
     * Records the payout for one collector and one period of ONE branch.
     *
     * @return array{ok:bool, message:string}
     */
    public function record(int $collectorUserId, int $branchId, string $from, string $to, int $paidByUserId, string $reference = '', string $notes = ''): array
    {
        if (! self::ready()) {
            return ['ok' => false, 'message' => 'Commission payments are not available yet. Please contact the System Administrator.'];
        }

        $valid = static fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        if (! $valid($from) || ! $valid($to) || $from > $to) {
            return ['ok' => false, 'message' => 'Choose a valid period (from and to dates) first.'];
        }

        if ($to > date('Y-m-d')) {
            return ['ok' => false, 'message' => 'You cannot pay commission for dates that have not happened yet.'];
        }

        $collector = db_connect()->table('users')->select('user_id, branch_id, first_name, last_name')->where('user_id', $collectorUserId)->get()->getRowArray();
        if (! $collector || (int) $collector['branch_id'] !== $branchId) {
            return ['ok' => false, 'message' => 'That collector was not found in your branch.'];
        }

        // What is owed comes from the payments, not from the page.
        $earned = null;
        foreach ((new CommissionService())->reportRows($branchId, $from, $to) as $row) {
            if ((int) $row['user_id'] === $collectorUserId) {
                $earned = $row;
                break;
            }
        }

        if ($earned === null || (float) $earned['commission'] <= 0.004) {
            return ['ok' => false, 'message' => 'There is no commission to pay for this period.'];
        }

        $existing = $this->overlapping($collectorUserId, $from, $to);
        if ($existing !== null) {
            return ['ok' => false, 'message' => 'This collector was already paid commission for ' . date('M j, Y', strtotime($existing['period_start'])) . ' to ' . date('M j, Y', strtotime($existing['period_end'])) . ', which overlaps this period.'];
        }

        $now = date('Y-m-d H:i:s');

        try {
            db_connect()->table('commission_payouts')->insert([
                'collector_user_id' => $collectorUserId,
                'branch_id'         => $branchId,
                'period_start'      => $from,
                'period_end'        => $to,
                'total_collected'   => (float) $earned['total_collected'],
                'commission_amount' => (float) $earned['commission'],
                'paid_at'           => $now,
                'paid_by_user_id'   => $paidByUserId,
                'reference'         => $reference !== '' ? mb_substr($reference, 0, 100) : null,
                'notes'             => $notes !== '' ? $notes : null,
                'created_at'        => $now,
            ]);
        } catch (\Throwable $e) {
            // The unique key caught a double click / second tab.
            return ['ok' => false, 'message' => 'This commission was already marked as paid.'];
        }

        (new NotificationService())->notify(
            $collectorUserId,
            'Your commission of ' . cs_peso((float) $earned['commission']) . ' for ' . date('M j', strtotime($from)) . ' to ' . date('M j, Y', strtotime($to)) . ' has been paid.',
            'payment_approved'
        );

        return ['ok' => true, 'message' => 'Marked as paid: ' . cs_peso((float) $earned['commission']) . ' to ' . trim($collector['first_name'] . ' ' . $collector['last_name']) . '.'];
    }

    /** A collector's payout history, newest first. */
    public function historyFor(int $collectorUserId, int $limit = 50): array
    {
        if (! self::ready()) {
            return [];
        }

        return db_connect()->table('commission_payouts cp')
            ->select('cp.*, pb.first_name AS paid_by_first, pb.last_name AS paid_by_last')
            ->join('users pb', 'pb.user_id = cp.paid_by_user_id', 'left')
            ->where('cp.collector_user_id', $collectorUserId)
            ->orderBy('cp.paid_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /** Paid money inside a period for one collector (for their own page). */
    public function paidWithin(int $collectorUserId, ?string $from, ?string $to): float
    {
        if (! self::ready()) {
            return 0.0;
        }

        $q = db_connect()->table('commission_payouts')->selectSum('commission_amount', 'total')->where('collector_user_id', $collectorUserId);
        if (! empty($from)) {
            $q->where('period_start >=', $from);
        }
        if (! empty($to)) {
            $q->where('period_end <=', $to);
        }

        return round((float) ($q->get()->getRow('total') ?? 0), 2);
    }
}
