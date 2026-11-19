<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$commissionQuery = 'date_from=' . urlencode((string) ($filters['date_from'] ?? '')) . '&date_to=' . urlencode((string) ($filters['date_to'] ?? ''));
if (! empty($filters['search'])) {
    $commissionQuery .= '&search=' . urlencode((string) $filters['search']);
}
// Branch filter is System Admin only ($branches is only passed by Reports::commission).
if (! empty($filters['branch_id'])) {
    $commissionQuery .= '&branch_id=' . (int) $filters['branch_id'];
}
?>
<div class="mb-3 text-end">
    <a class="btn btn-ghost btn-sm" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/commission?mode=print&' . $commissionQuery) ?>" target="_blank" rel="noopener">
        <i class="ti ti-printer me-1"></i> Print
    </a>
    <a class="btn btn-ghost btn-sm" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/commission?mode=csv&' . $commissionQuery) ?>">
        <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
    </a>
    <a class="btn btn-primary btn-sm" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/commission?mode=pdf&' . $commissionQuery) ?>">
        <i class="ti ti-file-type-pdf me-1"></i> Export PDF
    </a>
</div>

<?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
<?= $this->include('partials/reports_nav') ?>

<form class="cs-filters mb-3" method="get" action="<?= site_url(($reports_base_path ?? '/admin/reports') . '/commission') ?>">
    <div class="cs-filters__field cs-filters__field--wide">
        <label class="form-label">Search</label>
        <input type="search" name="search" class="form-control" maxlength="100" value="<?= esc((string) ($filters['search'] ?? ''), 'attr') ?>" placeholder="Collector name">
    </div>
    <div class="cs-filters__field">
        <label class="form-label">Date From</label>
        <input type="date" name="date_from" class="form-control" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
    </div>
    <div class="cs-filters__field">
        <label class="form-label">Date To</label>
        <input type="date" name="date_to" class="form-control" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
    </div>
    <?php if (! empty($branches)): ?>
        <div class="cs-filters__field">
            <label class="form-label">Branch</label>
            <select class="form-select" name="branch_id">
                <option value="0">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                        <?= esc((string) $branch['branch_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="cs-filters__actions">
        <button type="submit" class="btn btn-primary">Apply</button>
    </div>
</form>

<?php $rem = new \App\Services\RemittanceService(); ?>
<p class="cs-muted mb-3"><i class="ti ti-info-circle" aria-hidden="true"></i> Rule in force: <?= esc($rem->ruleText()) ?>. Commission counts as <strong>earned</strong> when the Branch Admin confirms the remittance; waiting and on-hold remittances are shown separately. (Dates are the dates the remittances were submitted.)</p>
<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', [
                'icon'  => 'ti-cash',
                'title' => 'No remittances found for this period',
            ]) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Collector</th>
                            <?php if (! empty($branches)): ?><th>Branch</th><?php endif; ?>
                            <th class="cs-num">Remittances</th>
                            <th class="cs-num">Total Collected</th>
                            <th class="cs-num">Commission Earned</th>
                            <th class="cs-num">Commission Waiting</th>
                            <th class="cs-num">On Hold</th>
                            <th class="cs-num">Net Remitted</th>
                            <th class="cs-num">Net Waiting</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $t = ['n' => 0, 'g' => 0.0, 'ce' => 0.0, 'cp' => 0.0, 'ch' => 0.0, 'nc' => 0.0, 'np' => 0.0]; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $t['n'] += (int) $row['remittances']; $t['g'] += (float) $row['gross']; $t['ce'] += (float) $row['commission_earned'];
                            $t['cp'] += (float) $row['commission_pending']; $t['ch'] += (float) $row['commission_on_hold'];
                            $t['nc'] += (float) $row['net_confirmed']; $t['np'] += (float) $row['net_pending'];
                            ?>
                            <tr>
                                <td><?= esc((string) $row['collector_name']) ?><?php if ((int) $row['discrepancies'] > 0): ?> <span class="badge text-bg-danger"><?= (int) $row['discrepancies'] ?> discrepanc<?= (int) $row['discrepancies'] === 1 ? 'y' : 'ies' ?></span><?php endif; ?></td>
                                <?php if (! empty($branches)): ?><td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td><?php endif; ?>
                                <td class="cs-num"><?= (int) $row['remittances'] ?></td>
                                <td class="cs-num"><?= cs_money($row['gross']) ?></td>
                                <td class="cs-num"><?= cs_money($row['commission_earned']) ?></td>
                                <td class="cs-num"><?= cs_money($row['commission_pending']) ?></td>
                                <td class="cs-num"><?= cs_money($row['commission_on_hold']) ?></td>
                                <td class="cs-num"><?= cs_money($row['net_confirmed']) ?></td>
                                <td class="cs-num"><?= cs_money($row['net_pending']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th<?= ! empty($branches) ? ' colspan="2"' : '' ?>>Total</th>
                            <th class="cs-num"><?= $t['n'] ?></th>
                            <th class="cs-num"><?= cs_money($t['g']) ?></th>
                            <th class="cs-num"><?= cs_money($t['ce']) ?></th>
                            <th class="cs-num"><?= cs_money($t['cp']) ?></th>
                            <th class="cs-num"><?= cs_money($t['ch']) ?></th>
                            <th class="cs-num"><?= cs_money($t['nc']) ?></th>
                            <th class="cs-num"><?= cs_money($t['np']) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
