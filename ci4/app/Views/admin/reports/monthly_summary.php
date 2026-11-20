<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$base = (string) ($reports_base_path ?? '/admin/reports');
$q = 'month_from=' . urlencode((string) ($filters['month_from'] ?? '')) . '&month_to=' . urlencode((string) ($filters['month_to'] ?? ''));
if (! empty($filters['search'])) { $q .= '&search=' . urlencode((string) $filters['search']); }
if (! empty($filters['branch_id']) && ! empty($branches)) { $q .= '&branch_id=' . (int) $filters['branch_id']; }
$isBa = ! empty($detail_base);
?>
<div class="mb-3 text-end">
    <a class="btn btn-ghost btn-sm" href="<?= site_url($base . '/monthly-summary?mode=print&' . $q) ?>" target="_blank" rel="noopener"><i class="ti ti-printer me-1"></i> Print</a>
    <a class="btn btn-ghost btn-sm" href="<?= site_url($base . '/monthly-summary?mode=csv&' . $q) ?>"><i class="ti ti-file-spreadsheet me-1"></i> Export Excel</a>
    <a class="btn btn-primary btn-sm" href="<?= site_url($base . '/monthly-summary?mode=pdf&' . $q) ?>"><i class="ti ti-file-type-pdf me-1"></i> Export PDF</a>
</div>

<?php $this->setData(['reports_base_path' => $base]) ?>
<?= $this->include('partials/reports_nav') ?>

<form class="cs-filters mb-3" method="get" action="<?= site_url($base . '/monthly-summary') ?>">
    <div class="cs-filters__field cs-filters__field--wide"><label class="form-label">Search</label><input type="search" name="search" class="form-control" maxlength="100" value="<?= esc((string) ($filters['search'] ?? ''), 'attr') ?>" placeholder="Collector or branch"></div>
    <div class="cs-filters__field"><label class="form-label">From month</label><input type="month" name="month_from" class="form-control" value="<?= esc((string) ($filters['month_from'] ?? ''), 'attr') ?>"></div>
    <div class="cs-filters__field"><label class="form-label">To month</label><input type="month" name="month_to" class="form-control" value="<?= esc((string) ($filters['month_to'] ?? ''), 'attr') ?>"></div>
    <?php if (! empty($branches)): ?>
        <div class="cs-filters__field"><label class="form-label">Branch</label>
            <select class="form-select" name="branch_id"><option value="0">All Branches</option>
                <?php foreach ($branches as $branch): ?><option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>><?= esc((string) $branch['branch_name']) ?></option><?php endforeach; ?>
            </select></div>
    <?php endif; ?>
    <div class="cs-filters__actions"><button type="submit" class="btn btn-primary">Apply</button></div>
</form>

<p class="cs-muted mb-3"><i class="ti ti-info-circle" aria-hidden="true"></i> One line per collector and month, written when the collector closes the month (every plan holder accounted for and every collection confirmed).</p>
<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-calendar-off', 'title' => 'No closed months found']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr>
                        <th>Month</th><th>Collector</th><?php if (! empty($branches)): ?><th>Branch</th><?php endif; ?>
                        <th class="cs-num">Assigned</th><th class="cs-num">Collected</th><th class="cs-num">Not collected</th>
                        <th class="cs-num">Cash</th><th class="cs-num">GCash</th><th class="cs-num">Gross</th><th class="cs-num">Commission</th><th class="cs-num">Net remitted</th><th>Closed</th><th></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-nowrap"><?= esc((string) $r['month_label']) ?></td>
                                <td><?= esc((string) $r['collector_name']) ?></td>
                                <?php if (! empty($branches)): ?><td><?= esc((string) $r['branch_name']) ?></td><?php endif; ?>
                                <td class="cs-num"><?= (int) $r['members_assigned'] ?></td>
                                <td class="cs-num"><?= (int) $r['collected_count'] ?></td>
                                <td class="cs-num"><?= (int) $r['not_collected_count'] ?></td>
                                <td class="cs-num"><?= cs_money($r['cash_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['gcash_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['gross_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['commission_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['net_amount']) ?></td>
                                <td class="text-nowrap"><?= cs_date($r['closed_at']) ?></td>
                                <td class="text-end"><?php if ($isBa): ?><a class="btn btn-sm btn-ghost" href="<?= site_url($detail_base . '/' . (int) $r['summary_id']) ?>">Open</a><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
