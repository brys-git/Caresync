<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$sm = $summary ?? [];
$quick = (string) ($range['quick'] ?? 'this_month');
$rangeLabel = 'All time';
if (! empty($range['from']) || ! empty($range['to'])) {
    $rangeLabel = cs_date($range['from'] ?? null) . ' - ' . cs_date($range['to'] ?? null);
}
$tone = ['submitted' => 'text-bg-warning', 'confirmed' => 'text-bg-success', 'discrepancy' => 'text-bg-danger'];
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="cs-muted mb-0">Showing remittances submitted: <?= esc($rangeLabel) ?></p>
    <div class="d-flex gap-2">
        <a class="btn btn-sm <?= $quick === 'this_month' ? 'btn-primary' : 'btn-ghost' ?>" href="?quick=this_month">This month</a>
        <a class="btn btn-sm <?= $quick === 'last_month' ? 'btn-primary' : 'btn-ghost' ?>" href="?quick=last_month">Last month</a>
        <a class="btn btn-sm <?= $quick === 'all_time' ? 'btn-primary' : 'btn-ghost' ?>" href="?quick=all_time">All time</a>
    </div>
</div>

<section class="cs-panel mb-3">
    <div class="cs-panel__body">
        <?= view('components/stat_rail', ['stats' => [
            ['label' => 'Commission earned', 'value' => $sm['earned'] ?? 0, 'money' => true, 'tone' => 'ok', 'icon' => 'ti-coin', 'meta' => 'confirmed by your Branch Admin'],
            ['label' => 'Waiting for confirmation', 'value' => $sm['pending'] ?? 0, 'money' => true, 'tone' => 'warn', 'icon' => 'ti-hourglass'],
            ['label' => 'On hold (discrepancy)', 'value' => $sm['on_hold'] ?? 0, 'money' => true, 'tone' => ((float) ($sm['on_hold'] ?? 0)) > 0 ? 'stop' : '', 'icon' => 'ti-alert-triangle'],
            ['label' => 'Remitted (gross)', 'value' => $sm['remitted_gross'] ?? 0, 'money' => true, 'icon' => 'ti-cash'],
            ['label' => 'Not remitted yet', 'value' => $sm['available_gross'] ?? 0, 'money' => true, 'icon' => 'ti-clock', 'meta' => '₱' . number_format((float) ($sm['available_commission'] ?? 0), 2) . ' commission once remitted'],
        ]]) ?>
        <p class="cs-muted small mt-3 mb-0">Commission: <?= esc($rule) ?>. <a href="<?= site_url('collector/remit') ?>">Remit collections</a></p>
    </div>
</section>

<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">My remittances</h2>
        <p class="cs-panel__note">Each remittance with the commission it earns.</p>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($remittances)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-coin-off', 'title' => 'No remittances in this range', 'text' => 'Remit the cash you collected to start earning commission.', 'action' => ['label' => 'Remit collections', 'url' => 'collector/remit']]) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>#</th><th>Submitted</th><th class="cs-num">Collected</th><th class="cs-num">Commission</th><th class="cs-num">Net handed over</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($remittances as $r): ?>
                            <tr>
                                <td><a href="<?= site_url('collector/remit/' . (int) $r['remittance_id']) ?>">#<?= (int) $r['remittance_id'] ?></a></td>
                                <td class="text-nowrap"><?= cs_date($r['submitted_at'], true) ?></td>
                                <td class="cs-num"><?= cs_money($r['gross_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['commission_amount'], true, 'cs-money--brass') ?></td>
                                <td class="cs-num"><?= cs_money($r['net_amount']) ?></td>
                                <td><span class="badge <?= $tone[$r['status']] ?? 'text-bg-secondary' ?>"><?= esc((string) $r['status_label']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (! empty($legacy_payouts)): ?>
<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Earlier commission payments</h2>
        <p class="cs-panel__note">Recorded under the old "mark as paid" system. Kept for your records; no new ones are added.</p>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <div class="cs-tablewrap">
            <table class="cs-table">
                <thead><tr><th>Period</th><th class="cs-num">Collected</th><th class="cs-num">Commission paid</th><th>Paid on</th></tr></thead>
                <tbody>
                    <?php foreach ($legacy_payouts as $p): ?>
                        <tr>
                            <td class="text-nowrap"><?= cs_date($p['period_start']) ?> - <?= cs_date($p['period_end']) ?></td>
                            <td class="cs-num"><?= cs_money($p['total_collected']) ?></td>
                            <td class="cs-num"><?= cs_money($p['commission_amount']) ?></td>
                            <td class="text-nowrap"><?= cs_date($p['paid_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>
<?= $this->endSection() ?>
