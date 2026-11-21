<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$money = static fn ($v): string => '₱' . number_format((float) $v, 2);
$person = static fn ($f, $l): string => trim((string) $f . ' ' . (string) $l);
$tone = static function (string $label): string {
    return match (true) {
        str_starts_with($label, 'Rejected'), str_starts_with($label, 'Cancelled') => 'text-bg-danger',
        str_starts_with($label, 'Pending'), str_contains($label, 'waiting') => 'text-bg-warning',
        $label === 'Completed' => 'text-bg-success',
        default => 'text-bg-primary',
    };
};
?>
<?= view('partials/two_part_amounts', ['plan_id' => (int) ($plan_id ?? 0), 'tpa_title' => 'What you owe now']) ?>
<?php if ($balances !== null): ?>
<section class="cs-panel mb-3">
    <div class="cs-panel__head"><h2 class="cs-panel__title">What you owe now</h2></div>
    <div class="cs-panel__body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Plan balance</small><strong><?= $money($balances['plan_balance']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Package balance</small><strong><?= $money($balances['package_balance']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Total balance</small><strong><?= $money($balances['total_balance']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Due each month</small><strong><?= $money($balances['monthly_total_due']) ?></strong></div>
        </div>
        <?php if ($balances['legacy_balance'] > 0): ?>
            <p class="cs-muted small mt-2 mb-0">Includes <?= $money($balances['legacy_balance']) ?> from an earlier claim made under the old rule (shown below as "old rule").</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($requests === []): ?>
    <?= view('components/empty_state', ['icon' => 'ti-clipboard-list', 'title' => 'No claims or requests yet', 'text' => 'When you claim your casket, avail a package or request a service, it appears here.', 'action' => ['label' => 'Request a service', 'url' => 'client/service']]) ?>
<?php else: ?>
    <?php foreach ($requests as $r): ?>
        <?php $av = $r['avail']; $label = (string) $r['status_label']; ?>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head" style="flex-wrap:wrap;gap:.5rem">
                <div>
                    <h2 class="cs-panel__title"><span class="badge text-bg-secondary me-1"><?= esc((string) $r['type_label']) ?></span><?= esc((string) $r['what']) ?></h2>
                    <p class="cs-panel__note">Request #<?= (int) $r['application_id'] ?> · <?= esc(cs_date($r['created_at'])) ?></p>
                </div>
                <span class="badge <?= $tone($label) ?>"><?= esc($label) ?></span>
            </div>
            <div class="cs-panel__body">
                <?php if (! empty($r['waived']) && $r['waived'] > 0): ?>
                    <p class="mb-2"><i class="ti ti-scissors" aria-hidden="true"></i> <strong>Waived on claim - <?= cs_money($r['waived']) ?></strong> <span class="cs-muted">(the remaining plan balance; a new cycle started)</span></p>
                <?php endif; ?>
                <?php if (! empty($r['filed_on_behalf'])): ?>
                    <p class="mb-2">Filed by <strong><?= esc($person($r['filer_first'] ?? '', $r['filer_last'] ?? '')) ?></strong> (<?= esc((string) ($r['filer_role'] ?: 'branch')) ?>) for <strong><?= esc((string) $r['claimant_name']) ?></strong> (<?= esc((string) $r['claimant_relationship']) ?>)</p>
                <?php endif; ?>

                <?php if ($r['status'] === 'rejected'): ?>
                    <div class="alert alert-danger"><div><strong>Rejected.</strong> Reason: <?= esc((string) ($r['rejection_reason'] ?: 'No reason was recorded.')) ?></div></div>
                <?php endif; ?>

                <?php if ($r['addons'] !== []): ?>
                    <p class="mb-2">Add-ons: <?= esc(implode(', ', array_map(static fn ($a) => $a['label'] . ' ₱' . number_format($a['price'], 2), $r['addons']))) ?></p>
                <?php endif; ?>

                <?php if ($r['old_rule']): ?>
                    <p class="cs-muted mb-2"><span class="badge text-bg-light border">Old rule</span> This was approved before the current rules, so it keeps its own balance<?= $r['amount_added'] > 0 ? ' (' . $money($r['amount_added']) . ' still owed)' : '' ?>. It is not recalculated.</p>
                <?php endif; ?>

                <?php if ($av !== null): ?>
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><small class="cs-muted d-block">Added to your balance</small><strong><?= $money($av['package_price']) ?></strong></div>
                        <div class="col-6 col-md-3"><small class="cs-muted d-block">Down payment</small><strong><?= $av['down_payment_status'] === 'waived' ? 'Waived' : $money($av['down_payment_amount']) ?></strong></div>
                        <div class="col-6 col-md-3"><small class="cs-muted d-block">Monthly instalment</small><strong><?= (int) $av['term_months'] > 0 ? $money($av['monthly_instalment']) . ' × ' . (int) $av['term_months'] : 'None (paid in full)' ?></strong></div>
                        <div class="col-6 col-md-3"><small class="cs-muted d-block">First instalment due</small><strong><?= $av['first_due_date'] ? esc(cs_date($av['first_due_date'])) : '—' ?></strong></div>
                    </div>
                    <?php if ($av['status'] === 'awaiting_down_payment'): ?>
                        <div class="alert alert-warning mt-3 mb-0"><div>Pay the <strong><?= $money($r['down_left']) ?></strong> down payment by <strong><?= esc(cs_date($av['down_payment_due_by'])) ?></strong>, or this request is cancelled. Your service starts once it is paid and verified.</div></div>
                    <?php elseif ($av['status'] === 'cancelled' && ($av['kind'] ?? 'package') === 'addons'): ?>
                        <div class="alert alert-warning mt-3 mb-0"><div>The add-on was cancelled because it was not paid in time, and its amount was removed from your balance. <strong>Your casket claim is still approved.</strong></div></div>
                    <?php elseif ($av['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger mt-3 mb-0"><div>Cancelled - the down payment was not paid in time. The amount was removed from your balance.</div></div>
                    <?php elseif ($av['down_payment_status'] === 'paid'): ?>
                        <p class="mt-3 mb-0 text-success">Down payment received<?= $av['down_payment_paid_at'] ? ' on ' . esc(cs_date($av['down_payment_paid_at'])) : '' ?>.</p>
                    <?php endif; ?>
                    <?php if ($av['down_payment_status'] === 'waived'): ?>
                        <p class="mt-3 mb-0"><strong>Down payment waived</strong> by your branch<?= $av['waived_at'] ? ' on ' . esc(cs_date($av['waived_at'])) : '' ?>. Note: <?= esc((string) $av['waiver_reason']) ?></p>
                    <?php endif; ?>
                <?php elseif ($r['status'] === 'approved' && ! $r['old_rule']): ?>
                    <p class="mb-0">Nothing is added to your balance. You keep paying your plan contribution.</p>
                <?php elseif ($r['status'] === 'pending'): ?>
                    <p class="mb-0 cs-muted">Your branch is reviewing this. You will be told when it is approved, with the amounts and deadlines.</p>
                <?php endif; ?>

                <?php $pr = $r['process'] ?? null; ?>
                <?php if ($pr !== null && ! empty($pr['plan']) && ! empty($pr['plan']['burial_at'])): ?>
                    <div class="mt-3 p-3 border rounded">
                        <strong>Your service schedule</strong>
                        <div class="small mt-1">Wake: <?= ! empty($pr['plan']['wake_start']) ? esc(date('M j, g:ia', strtotime((string) $pr['plan']['wake_start']))) : '-' ?> at <?= esc((string) ($pr['plan']['wake_address'] ?? '-')) ?><br>
                        Burial: <?= esc(date('M j, g:ia', strtotime((string) $pr['plan']['burial_at']))) ?> at <?= esc((string) ($pr['plan']['cemetery'] ?? '-')) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($pr !== null && $pr['can_rate']): ?>
                    <form method="post" action="<?= site_url('client/claims/rate/' . (int) $pr['service_id']) ?>" class="mt-3 p-3 border rounded" data-cs-once>
                        <?= csrf_field() ?>
                        <strong>How was our service?</strong>
                        <div class="d-flex gap-3 my-2" role="radiogroup" aria-label="Rating from 1 to 5">
                            <?php for ($i = 1; $i <= 5; $i++): ?><label class="form-check-label"><input class="form-check-input me-1" type="radio" name="rating" value="<?= $i ?>" required><?= $i ?> ★</label><?php endfor; ?>
                        </div>
                        <textarea class="form-control mb-2" name="feedback" rows="2" maxlength="1000" placeholder="Anything you would like to tell us (optional)"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Send my rating</button>
                    </form>
                <?php elseif ($pr !== null && $pr['rated']): ?>
                    <p class="mt-3 mb-0 text-success">Thank you - you rated this service <?= (int) $pr['plan']['rating'] ?>/5.</p>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>
