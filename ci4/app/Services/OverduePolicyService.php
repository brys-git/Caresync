<?php

namespace App\Services;

/**
 * Panel brief, section 8: "Define and implement a policy on overdue
 * accounts and penalties" - the brief flagged this as needing explicit
 * business rules before implementation. The user supplied them directly:
 *
 *   If a plan holder misses a monthly contribution, they have 60 days from
 *   the missed due date to pay it. If they pay within 60 days, nothing
 *   happens. If they do NOT pay within 60 days, their accumulated
 *   months_paid counter resets to 0 - e.g. someone with 5 months paid who
 *   then misses a payment and doesn't catch up within 60 days goes back to
 *   0. The plan holder is NOT deactivated or unregistered; they remain a
 *   registered plan holder with a fresh start, only the paid-months
 *   progress is forfeited.
 *
 * This is a distinct concept from the existing membership_state
 * (active/delinquent/suspended) ladder in MembershipService::
 * updateMembershipStates(), which tracks standing at whole-month
 * granularity (3/6 months overdue) and never resets anything - that logic
 * is left untouched. This service only handles the specific 60-day
 * forfeiture rule, run from the same daily cron command
 * (membership:update-status) so both checks happen on the same cadence.
 */
class OverduePolicyService
{
    public const GRACE_PERIOD_DAYS = 60;

    /**
     * Sweeps every active plan that's overdue past the grace period and
     * still has paid-months progress to lose, resets it, and gives the
     * plan a fresh coverage start from today (so the forfeiture is a clean
     * reset, not an accumulating debt) - matching "the paid months will
     * return to zero... but the client is still considered registered."
     *
     * @return array{checked: int, forfeited: int}
     */
    public function applyForfeitures(): array
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $result = ['checked' => 0, 'forfeited' => 0];

        $candidates = $db->table('plans p')
            ->select('p.plan_id, p.plan_holder_id, p.months_paid, p.payment_coverage_until, ph.user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('p.status', 'active')
            ->where('p.months_paid >', 0)
            ->where('p.payment_coverage_until IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $notificationService = new NotificationService();

        foreach ($candidates as $plan) {
            $result['checked']++;

            $coverageUntil = (string) ($plan['payment_coverage_until'] ?? '');
            if ($coverageUntil === '') {
                continue;
            }

            $daysOverdue = (int) floor((strtotime($today) - strtotime($coverageUntil)) / 86400);
            if ($daysOverdue <= self::GRACE_PERIOD_DAYS) {
                continue;
            }

            $planId = (int) $plan['plan_id'];
            $monthsForfeited = (int) $plan['months_paid'];

            $db->table('plans')->where('plan_id', $planId)->update([
                'months_paid' => 0,
                'overdue_months' => 0,
                'membership_state' => 'active',
                'payment_coverage_until' => $today,
                'next_due_date' => date('Y-m-d', strtotime('+1 day', strtotime($today))),
            ]);

            $userId = (int) ($plan['user_id'] ?? 0);
            if ($userId > 0) {
                // Panel brief, section 9: "Notify plan holder when
                // forfeited," delivered via email and/or SMS in addition to
                // the in-app record.
                $notificationService->notify(
                    $userId,
                    "Your contribution was not settled within the {$this->graceDays()}-day grace period, so your {$monthsForfeited} paid month(s) have been forfeited and reset to 0. Your plan remains active - simply resume your monthly contribution to start building it back up.",
                    'general',
                    true,
                    true
                );
            }

            $result['forfeited']++;
        }

        return $result;
    }

    /**
     * Panel brief, section 9: "Notify plan holder when overdue." Fires once
     * per unpaid cycle (gated by plans.overdue_notified_at, cleared by
     * PaymentService::recomputePlan() whenever a payment comes in) rather
     * than every single day an account stays overdue.
     *
     * @return array{checked: int, notified: int}
     */
    public function notifyOverdueAccounts(): array
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $result = ['checked' => 0, 'notified' => 0];

        $candidates = $db->table('plans p')
            ->select('p.plan_id, p.next_due_date, ph.user_id')
            ->join('plan_holders ph', 'ph.plan_holder_id = p.plan_holder_id', 'inner')
            ->where('p.status', 'active')
            ->where('p.next_due_date IS NOT NULL', null, false)
            ->where('p.next_due_date <', $today)
            ->where('p.overdue_notified_at IS NULL', null, false)
            ->get()
            ->getResultArray();

        $notificationService = new NotificationService();

        foreach ($candidates as $plan) {
            $result['checked']++;

            $planId = (int) $plan['plan_id'];
            $userId = (int) ($plan['user_id'] ?? 0);
            $daysOverdue = (int) floor((strtotime($today) - strtotime((string) $plan['next_due_date'])) / 86400);
            $daysLeft = $this->daysUntilForfeiture($daysOverdue);

            if ($userId > 0) {
                // Panel brief, section 9: "Notify plan holder when overdue,"
                // delivered via email and/or SMS in addition to the in-app
                // record.
                $notificationService->notify(
                    $userId,
                    "Your monthly contribution is now overdue. Please settle it within {$daysLeft} day(s) to avoid forfeiting your paid months (a {$this->graceDays()}-day grace period applies from your due date).",
                    'general',
                    true,
                    true
                );
                $result['notified']++;
            }

            $db->table('plans')->where('plan_id', $planId)->update([
                'overdue_notified_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $result;
    }

    /**
     * For display (e.g. the Overdue Report): how many days a plan has left
     * before it hits the forfeiture threshold, given its current overdue
     * days. Returns 0 once the grace period has already passed.
     */
    public function daysUntilForfeiture(int $daysOverdue): int
    {
        return max(0, self::GRACE_PERIOD_DAYS - $daysOverdue);
    }

    private function graceDays(): int
    {
        return self::GRACE_PERIOD_DAYS;
    }
}
