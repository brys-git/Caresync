<?php
/**
 * Two-part amounts: plan + package. Renders NOTHING for a client with no avail, so those pages
 * look exactly as before.
 *
 * Expects: $plan_id (int). Optional: $tpa_title (string), $tpa_compact (bool).
 */
$tpaPlanId = (int) ($plan_id ?? 0);
$tpa = $tpaPlanId > 0 ? (new \App\Services\ClaimAvailService())->moneyView($tpaPlanId) : ['has_package' => false];
if (! empty($tpa['has_package'])):
    $m = static fn ($v): string => '₱' . number_format((float) $v, 2);
    $pl = $tpa['plan']; $pk = $tpa['package']; $tt = $tpa['total'];
    $planDue = (float) $pl['monthly'];
    $pkgDue  = (float) $pk['monthly'] + (float) $pk['down_due'];
    $totDue  = round($planDue + $pkgDue, 2);
    $dt = static fn ($d): string => $d ? cs_date($d) : '—';
?>
<section class="cs-panel mb-3 tpa" aria-label="Plan and package amounts">
    <div class="cs-panel__head"><h2 class="cs-panel__title"><?= esc($tpa_title ?? 'Plan and package amounts') ?></h2></div>
    <div class="cs-panel__body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-2 tpa__table">
                <thead><tr><th></th><th class="text-end">Plan</th><th class="text-end">Package</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    <tr><th scope="row" class="fw-normal">Due now</th><td class="text-end"><?= $m($planDue) ?></td><td class="text-end"><?= $m($pkgDue) ?></td><td class="text-end fw-bold"><?= $m($totDue) ?></td></tr>
                    <tr><th scope="row" class="fw-normal">Balance</th><td class="text-end"><?= $m($pl['balance']) ?></td><td class="text-end"><?= $m($pk['balance']) ?></td><td class="text-end fw-bold"><?= $m($tt['balance']) ?></td></tr>
                    <?php if ($tt['overdue'] > 0): ?>
                        <tr class="text-danger"><th scope="row" class="fw-normal">Overdue</th><td class="text-end"><?= $m($pl['overdue']) ?></td><td class="text-end"><?= $m($pk['overdue']) ?></td><td class="text-end fw-bold"><?= $m($tt['overdue']) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="mb-1"><strong>Plan contribution</strong> <?= $m($planDue) ?> + <strong>Package <?= (float) $pk['down_due'] > 0 ? 'down payment' : 'instalment' ?></strong> <?= $m($pkgDue) ?> = <strong>Total due <?= $m($totDue) ?></strong></p>
        <p class="mb-1 cs-muted small">Plan balance <?= $m($pl['balance']) ?> + Package balance <?= $m($pk['balance']) ?> = Total balance <?= $m($tt['balance']) ?></p>
        <?php foreach ($pk['items'] as $it): ?>
            <p class="mb-1 small">
                <span class="badge text-bg-secondary"><?= esc($it['label']) ?></span>
                <?php if ($it['status'] === 'awaiting_down_payment' && $it['down_due'] > 0): ?>
                    Down payment <?= $m($it['down_due']) ?> due by <?= esc($dt($it['down_due_by'])) ?>
                <?php elseif ($it['term'] > 0 && $it['next_number'] > 0): ?>
                    Instalment <?= (int) $it['next_number'] ?> of <?= (int) $it['term'] ?>, <?= $m($it['instalment']) ?>, due <?= esc($dt($it['next_due'])) ?>
                <?php endif; ?>
                <?php if ($it['overdue'] > 0): ?><span class="text-danger"> · <?= $m($it['overdue']) ?> overdue (package)</span><?php endif; ?>
                <?php if ($it['waived']): ?><span class="cs-muted"> · down payment waived</span><?php endif; ?>
            </p>
        <?php endforeach; ?>
        <?php if ($pl['overdue'] > 0): ?><p class="mb-1 small text-danger"><?= $m($pl['overdue']) ?> overdue (plan)</p><?php endif; ?>
        <?php if ($tpa['pending_count'] > 0): ?>
            <p class="mb-0 small cs-muted"><span class="badge text-bg-warning">pending</span> <?= $m($tpa['pending_amount']) ?> awaiting verification (not yet counted above)</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
