<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$tone = ['submitted' => 'text-bg-warning', 'confirmed' => 'text-bg-success', 'discrepancy' => 'text-bg-danger', 'cancelled' => 'text-bg-secondary'];
$isBa = ($mode ?? '') === 'branch_admin';
$open = in_array($r['status'], ['submitted', 'discrepancy'], true);
?>
<p class="mb-3"><a href="<?= site_url($back_url) ?>"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to remittances</a></p>

<?php if (session()->getFlashdata('success')): ?><?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><?= view('components/alert', ['type' => 'danger', 'message' => session()->getFlashdata('error')]) ?><?php endif; ?>

<section class="cs-panel mb-3">
    <div class="cs-panel__head" style="flex-wrap:wrap;gap:.5rem">
        <div><h2 class="cs-panel__title">Remittance #<?= (int) $r['remittance_id'] ?> <span class="badge <?= $tone[$r['status']] ?? 'text-bg-secondary' ?>"><?= esc((string) $r['status_label']) ?></span></h2>
            <p class="cs-panel__note">Collector: <?= esc((string) $r['collector_name']) ?> · Submitted <?= cs_date($r['submitted_at'], true) ?><?= $r['collector_note'] ? ' · Note: ' . esc((string) $r['collector_note']) : '' ?></p></div>
    </div>
    <div class="cs-panel__body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Gross collected (<?= (int) $r['payment_count'] ?> collection<?= (int) $r['payment_count'] === 1 ? '' : 's' ?>)</small><strong><?= cs_money($r['gross_amount']) ?></strong><div class="cs-table__sub">Cash <?= cs_peso($r['cash_amount'] ?? $r['gross_amount']) ?> · GCash <?= cs_peso($r['gcash_amount'] ?? 0) ?></div></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Commission</small><strong class="cs-money--brass"><?= cs_money($r['commission_amount']) ?></strong><div class="cs-table__sub">on <?= cs_peso($r['commission_base']) ?></div></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Net to hand over</small><strong class="fs-5"><?= cs_money($r['net_amount']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Commission is</small><strong><?= $r['status'] === 'confirmed' ? 'Earned' : ($r['status'] === 'discrepancy' ? 'On hold' : ($r['status'] === 'cancelled' ? 'Void' : 'Waiting for confirmation')) ?></strong></div>
        </div>
        <p class="cs-muted small mt-3 mb-0">Rule used for this remittance: <?= esc($rule) ?>.</p>

        <?php if ($r['status'] === 'confirmed'): ?>
            <p class="mt-3 mb-0 text-success">Confirmed by <?= esc(trim((string) $r['confirmed_first'] . ' ' . (string) $r['confirmed_last'])) ?> on <?= cs_date($r['confirmed_at'], true) ?>. Received <?= cs_peso($r['received_amount'] ?? $r['net_amount']) ?>.</p>
        <?php endif; ?>
        <?php if ($r['discrepancy_note']): ?>
            <div class="alert alert-danger mt-3 mb-0"><div><strong>Discrepancy:</strong> the Branch Admin received <?= cs_peso($r['received_amount']) ?> instead of <?= cs_peso($r['net_amount']) ?> (<?= cs_date($r['discrepancy_at'], true) ?>). Reason: <?= esc((string) $r['discrepancy_note']) ?></div></div>
        <?php endif; ?>
    </div>
</section>

<?php if ($isBa && $open): ?>
<section class="cs-panel mb-3">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Your decision</h2></div>
    <div class="cs-panel__body">
        <div class="row g-4">
            <div class="col-md-5">
                <form method="post" action="<?= site_url('branch-admin/remittances/' . (int) $r['remittance_id'] . '/confirm') ?>" data-cs-confirm="Confirm that you received <?= esc(cs_peso($r['net_amount']), 'attr') ?> from <?= esc((string) $r['collector_name'], 'attr') ?>? Their <?= (int) $r['payment_count'] ?> collection(s) become paid, and their commission of <?= esc(cs_peso($r['commission_amount']), 'attr') ?> becomes earned." data-cs-confirm-title="Confirm remittance" data-cs-confirm-ok="Confirm received" data-cs-once>
                    <?= csrf_field() ?>
                    <p class="mb-2">Received exactly <strong><?= cs_money($r['net_amount']) ?></strong>?</p>
                    <button type="submit" class="btn btn-primary">Confirm received <?= esc(cs_peso($r['net_amount'])) ?></button>
                    <p class="cs-muted small mt-2 mb-0">Confirming marks these <?= (int) $r['payment_count'] ?> payment(s) as paid and updates each client's ledger and receipt.</p>
                </form>
            </div>
            <div class="col-md-7">
                <form method="post" action="<?= site_url('branch-admin/remittances/' . (int) $r['remittance_id'] . '/discrepancy') ?>" data-cs-once>
                    <?= csrf_field() ?>
                    <p class="mb-2"><strong>Something is different?</strong> Say how much you actually received and why.</p>
                    <div class="row g-2">
                        <div class="col-sm-4"><label class="form-label small" for="rcv">Amount received</label><input id="rcv" name="received_amount" type="number" step="0.01" min="0" class="form-control" required></div>
                        <div class="col-sm-8"><label class="form-label small" for="dnote">What is different (required)</label><input id="dnote" name="note" type="text" maxlength="500" minlength="5" class="form-control" required></div>
                    </div>
                    <button type="submit" class="btn btn-ghost mt-2">Report discrepancy</button>
                    <span class="cs-muted small ms-1">The commission stays on hold and the collector is told.</span>
                </form>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cs-panel">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Collections in this remittance</h2></div>
    <div class="cs-panel__body cs-panel__body--flush">
        <div class="cs-tablewrap">
            <table class="cs-table">
                <thead><tr><th>Date</th><th>Client</th><th>Receipt / reference</th><th>Method</th><th class="cs-num">Collected</th><th class="cs-num">Plan</th><th class="cs-num">Package</th><th class="cs-num">Commission</th></tr></thead>
                <tbody>
                    <?php $tc = 0.0; ?>
                    <?php foreach ($lines as $l): $tc += (float) $l['commission']; ?>
                        <tr>
                            <td class="text-nowrap"><?= cs_date($l['payment_date']) ?></td>
                            <td><?= esc((string) $l['client_name']) ?><div class="cs-table__sub"><?= esc((string) ($l['unique_identifier'] ?: '-')) ?></div></td>
                            <td><?= esc((string) ($l['official_receipt_number'] ?: ($l['reference_number'] ?? '') ?: '-')) ?></td>
                            <td><?= esc(strtoupper((string) $l['payment_method'])) ?></td>
                            <td class="cs-num"><?= cs_money($l['amount']) ?></td>
                            <td class="cs-num"><?= cs_money($l['plan_part']) ?></td>
                            <td class="cs-num"><?= (float) $l['package_part'] > 0 ? cs_money($l['package_part']) : '-' ?></td>
                            <td class="cs-num"><?= cs_money($l['commission']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($lines === []): ?><tr><td colspan="8" class="cs-muted">No collections are attached to this remittance (it was cancelled).</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
