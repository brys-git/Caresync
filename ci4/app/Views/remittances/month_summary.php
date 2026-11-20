<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $s = $summary; ?>
<p class="mb-3 d-print-none"><a href="<?= site_url('branch-admin/reports/monthly-summary') ?>"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to monthly collection summaries</a>
    <button type="button" class="btn btn-sm btn-ghost ms-3" onclick="window.print()"><i class="ti ti-printer me-1"></i> Print</button></p>

<section class="cs-panel mb-3">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Monthly collection summary - <?= esc((string) $s['month_label']) ?></h2>
        <p class="cs-panel__note">Collector: <?= esc((string) $s['collector_name']) ?> · <?= esc((string) $s['branch_name']) ?> · Closed <?= cs_date($s['closed_at'], true) ?> by <?= esc(trim((string) $s['closed_first'] . ' ' . (string) $s['closed_last'])) ?></p></div>
    <div class="cs-panel__body">
        <div class="row g-3">
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Plan holders assigned</small><strong><?= (int) $s['members_assigned'] ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Collected</small><strong><?= (int) $s['collected_count'] ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Not collected</small><strong><?= (int) $s['not_collected_count'] ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Remittances</small><strong><?= (int) $s['remittance_count'] ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Cash</small><strong><?= cs_money($s['cash_amount']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">GCash</small><strong><?= cs_money($s['gcash_amount']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Gross collected</small><strong><?= cs_money($s['gross_amount']) ?></strong></div>
            <div class="col-6 col-md-3"><small class="cs-muted d-block">Commission</small><strong class="cs-money--brass"><?= cs_money($s['commission_amount']) ?></strong></div>
            <div class="col-12"><small class="cs-muted d-block">Net remitted to the branch</small><strong class="fs-5"><?= cs_money($s['net_amount']) ?></strong></div>
        </div>
    </div>
</section>

<section class="cs-panel">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Not collected</h2></div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($s['not_collected'] === []): ?>
            <p class="cs-muted p-3 mb-0">Everyone assigned was collected from.</p>
        <?php else: ?>
            <div class="cs-tablewrap"><table class="cs-table"><thead><tr><th>Plan holder</th><th>ID</th><th>Reason</th></tr></thead><tbody>
                <?php foreach ($s['not_collected'] as $n): ?><tr><td><?= esc((string) $n['name']) ?></td><td><?= esc((string) ($n['unique_identifier'] ?? '')) ?></td><td><?= esc((string) $n['reason']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
