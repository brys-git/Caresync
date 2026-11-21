<?php

namespace App\Services;

use App\Models\PlanModel;

/**
 * CareSync entitlement cycle model.
 *
 * A "cycle" is one plan-price window (the price of the client's own plan,
 * PlanLedgerService::targetFor(): PHP 14,500 on the Damayan Burial Program,
 * PHP 20,000 on a PHP 20,000 plan) of contributions toward the plan's package.
 * plans.current_cycle_number identifies which window a plan is currently
 * in; payments.cycle_number and service_applications.cycle_number record
 * which cycle a specific payment or claim belongs/belonged to.
 *
 * A cycle only advances (closeCycleIfSettled()) once BOTH of these are
 * true, whichever happens last:
 *   (a) the cycle's plan-price target is fully paid, and
 *   (b) the entitlement package has been claimed (approved) in this cycle
 *
 * This replaces the old behaviour where approving a claim zeroed
 * months_paid immediately (ClaimService::
 * resetContributionCycleIfEntitlementClaim(), now stampClaimCycle()) -
 * that destroyed a plan holder's progress if they claimed before finishing
 * paying off the current cycle. Two states fall out of the two conditions
 * above not being met at the same time:
 *
 *   'claimed_owing'  - claimed early, plan price not yet fully paid. Keeps
 *                       paying down the same cycle; CLAIM stays disabled
 *                       (already claimed this cycle).
 *   'paid_unclaimed' - plan price fully paid, no claim made yet. Owes
 *                       nothing further, CLAIM stays enabled indefinitely
 *                       until used. Nothing is overdue on a fully paid
 *                       plan: PlanLedgerService (the one overdue rule)
 *                       reports it as 'fully_paid', and
 *                       afterPaymentVerified() clears next_due_date.
 *
 * plans.contribution_cycle_started_at (Phase 0) keeps its existing job:
 * it marks "count progress from here forward only" *within* whichever
 * cycle is current. That's what let the old 60-day forfeiture reset progress (that reset is gone: a plan now just lapses and nothing is erased - see PlanLedgerService)
 * without also closing/advancing the cycle - forfeiture still zeroes
 * months_paid and bumps this marker exactly as it always has, but never
 * touches current_cycle_number, so a plan holder who already claimed in
 * cycle 3 and then gets forfeited stays in cycle 3, still marked claimed,
 * and has to earn back to the plan price within cycle 3 before it closes. Nothing
 * claws back an already-issued casket.
 */
class CycleService
{
    private PlanModel $planModel;
    private MembershipService $membershipService;

    public function __construct()
    {
        $this->planModel = new PlanModel();
        $this->membershipService = new MembershipService();
    }

    /**
     * The plan's current cycle: how much of the plan-price target has been
     * paid, whether the entitlement has been claimed in this cycle, and
     * the resulting state. months_paid/amount_paid come straight from
     * plans.months_paid, which MembershipService::recalculateMonthsPaid()
     * already keeps scoped to verified payments in the current cycle only
     * (see that method's own cycle_number + contribution_cycle_started_at
     * filter).
     */
    public function currentCycle(int $planId): array
    {
        // Used only when there is no plan row at all (planId <= 0 or not found).
        // Every real plan goes through targetFor($plan) below.
        $default = [
            'plan_id' => $planId,
            'cycle_number' => 1,
            'amount_paid' => 0.0,
            'months_paid' => 0,
            'target' => PlanLedgerService::targetFor([]),
            'remaining' => PlanLedgerService::targetFor([]),
            'package_remaining' => 0.0,
            'entitlement_claimed' => false,
            'is_fully_paid' => false,
            'state' => 'paying',
        ];

        if ($planId <= 0) {
            return $default;
        }

        $plan = $this->planModel->find($planId);
        if (! $plan) {
            return $default;
        }

        $cycleNumber = (int) ($plan['current_cycle_number'] ?? 1);
        $monthlyFee = (float) ($plan['monthly_fee'] ?? MembershipService::MONTHLY_FEE);
        if ($monthlyFee <= 0) {
            $monthlyFee = MembershipService::MONTHLY_FEE;
        }
        $target = PlanLedgerService::targetFor($plan);
        $monthsPaid = (int) ($plan['months_paid'] ?? 0);
        $amountPaid = $monthsPaid * $monthlyFee;
        $isFullyPaid = $amountPaid >= $target;

        // Claim of the plan's own package, or an avail (ClaimAvailService owns that rule).
        $claimAvail = new ClaimAvailService();
        $entitlementClaimed = $claimAvail->entitlementUsedInCycle((int) $plan['plan_holder_id'], $cycleNumber);
        // What is still owed on avails: the cycle only closes when this is paid too.
        $packageRemaining = $entitlementClaimed ? $claimAvail->packageOutstanding($planId) : 0.0;

        $state = 'paying';
        if ($entitlementClaimed && (! $isFullyPaid || $packageRemaining > 0.004)) {
            $state = 'claimed_owing';
        } elseif ($isFullyPaid && ! $entitlementClaimed) {
            $state = 'paid_unclaimed';
        }
        // If both are true, closeCycleIfSettled() should already have
        // advanced this plan to a fresh cycle before this is ever read -
        // afterPaymentVerified() and ClaimService::stampClaimCycle() both
        // call it immediately after whichever condition just became true.

        return [
            'plan_id' => $planId,
            'cycle_number' => $cycleNumber,
            'amount_paid' => $amountPaid,
            'months_paid' => $monthsPaid,
            'target' => $target,
            'remaining' => max(0.0, $target - $amountPaid),
            'package_remaining' => $packageRemaining,
            'entitlement_claimed' => $entitlementClaimed,
            'is_fully_paid' => $isFullyPaid,
            'state' => $state,
        ];
    }

    /**
     * Advances the plan to a fresh cycle, but only when both conditions
     * are met - safe to call unconditionally after either one might have
     * just become true, since it's a no-op otherwise.
     */
    public function closeCycleIfSettled(int $planId): void
    {
        if ($planId <= 0) {
            return;
        }

        $cycle = $this->currentCycle($planId);
        // Closes only when the plan is fully paid, the entitlement was used (claim or avail) AND
        // nothing is still owed on an avail.
        if (! $cycle['is_fully_paid'] || ! $cycle['entitlement_claimed'] || ($cycle['package_remaining'] ?? 0.0) > 0.004) {
            return;
        }

        $this->closeCycle($planId);
    }

    /**
     * Closes the current cycle NOW and starts the next one at 0 months (full plan price again). Used when a claim is
     * approved: the claim waives what is left of the plan price (PlanClosureService), so the cycle does not wait to be
     * paid off. Money already paid stays with the cycle it was paid in.
     */
    public function closeCycle(int $planId): void
    {
        $plan = $planId > 0 ? $this->planModel->find($planId) : null;
        if (! $plan) {
            return;
        }
        $today = date('Y-m-d');

        $this->planModel->update($planId, [
            'current_cycle_number' => (int) ($plan['current_cycle_number'] ?? 1) + 1,
            'months_paid' => 0,
            'contribution_cycle_started_at' => $today,
            'overdue_months' => 0,
            'membership_state' => 'active',
            'payment_coverage_until' => $today,
            'next_due_date' => date('Y-m-d', strtotime('+1 day', strtotime($today))),
        ]);
        // Dates, lifecycle and months of the fresh cycle come from the ledger.
        (new PlanLedgerService())->sync($planId, true);
    }

    /**
     * Call immediately after any payment's status becomes 'paid'/verified
     * - before that, plans.months_paid must not be trusted. Stamps which
     * cycle this specific payment counts toward (must happen before the
     * recalculation below, or the payment would be excluded from its own
     * cycle's sum), recalculates months_paid, then checks whether this
     * payment just completed the current cycle.
     */
    public function afterPaymentVerified(int $paymentId, int $planId): void
    {
        if ($paymentId <= 0 || $planId <= 0) {
            return;
        }

        $plan = $this->planModel->find($planId);
        if (! $plan) {
            return;
        }

        // A payment keeps the cycle it was first counted in (a collector's collection is counted when it is collected,
        // and counted again, unchanged, when the office confirms it).
        db_connect()->table('payments')
            ->where('payment_id', $paymentId)
            ->where('cycle_number', null)
            ->update(['cycle_number' => (int) ($plan['current_cycle_number'] ?? 1)]);

        // A client with an avail: split this payment between plan contributions and package
        // instalments (the ledger then counts only the plan part). No-op for everyone else.
        (new ClaimAvailService())->onPaymentVerified($paymentId, $planId);

        $this->membershipService->recalculateMonthsPaid($planId);

        $this->closeCycleIfSettled($planId);

        // closeCycleIfSettled() may have just advanced the cycle, in which
        // case the plan is back to 'paying' with a real next_due_date
        // already set above - nothing more to do. Otherwise, check whether
        // this payment brought the plan into paid-unclaimed: fully paid,
        // not yet claimed, owes nothing further until claimed. Clear the
        // due date so it's never shown as owing (PlanLedgerService reports
        // the plan as fully_paid, which has no overdue and no reminders).
        $cycle = $this->currentCycle($planId);
        if ($cycle['state'] === 'paid_unclaimed') {
            $this->planModel->update($planId, [
                'next_due_date' => null,
            ]);
        }
    }
}
