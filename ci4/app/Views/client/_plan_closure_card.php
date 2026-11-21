<?php
/**
 * After a claim for a beneficiary: what was waived and the choice (continue, or ask the branch to close the plan);
 * and the "Request to close my plan" button for any active plan. Expects: $claim_notice, $closure_request, $state.
 */
use App\Services\PlanClosureService;

if (($state ?? '') !== 'active' || ! PlanClosureService::ready()) {
    return;
}
$notice  = $claim_notice ?? null;
$pending = $closure_request ?? null;
$min     = (int) config('CareSync')->claimMinMonthsPaid;
?>
<?php if ($notice !== null): ?>
    <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'title' => 'Your claim was approved', 'message' => 'The remaining ' . cs_money($notice['amount']) . ' was waived. You can continue your plan (pay at least ' . $min . ' months to be eligible to claim again) or ask your branch to close it.']) ?>
<?php endif; ?>

<section class="cs-panel mb-3" id="plan-closure">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Close my plan</h2></div>
    <div class="cs-panel__body">
        <?php if ($pending !== null): ?>
            <p class="mb-2">You asked your branch to close your plan on <?= esc(date('F j, Y', strtotime((string) $pending['created_at']))) ?>. It is waiting for the Branch Admin.<?= ! empty($pending['reason']) ? ' Your reason: ' . esc((string) $pending['reason']) : '' ?></p>
            <form method="post" action="<?= site_url('client/plan-closure/cancel/' . (int) $pending['request_id']) ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm" data-cs-confirm="Cancel your request to close the plan? Your plan continues as before.">Cancel my request</button>
            </form>
        <?php else: ?>
            <p class="cs-muted small mb-2">If you no longer want to continue, ask your branch to close the plan. Nothing happens until the Branch Admin closes it; you can cancel your request before then. Your records, ledger and receipts are kept.</p>
            <form method="post" action="<?= site_url('client/plan-closure/request') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label" for="closure_reason">Reason (optional)</label>
                    <input id="closure_reason" name="reason" class="form-control" maxlength="255" placeholder="Tell your branch why, if you like">
                </div>
                <button type="submit" class="btn btn-ghost btn-sm" data-cs-confirm="Ask your branch to close your plan?">Request to close my plan</button>
            </form>
        <?php endif; ?>
    </div>
</section>
