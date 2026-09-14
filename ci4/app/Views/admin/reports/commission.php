<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="mb-3 text-end">
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/commission?mode=csv&date_from=' . urlencode((string) ($filters['date_from'] ?? '')) . '&date_to=' . urlencode((string) ($filters['date_to'] ?? ''))) ?>">
        <i class="ti ti-download me-1"></i> Export CSV
    </a>
</div>

<?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
<?= $this->include('partials/reports_nav') ?>

<form class="cs-filters mb-3" method="get" action="<?= site_url(($reports_base_path ?? '/admin/reports') . '/commission') ?>">
    <div class="cs-filters__field">
        <label class="form-label">Date From</label>
        <input type="date" name="date_from" class="form-control" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
    </div>
    <div class="cs-filters__field">
        <label class="form-label">Date To</label>
        <input type="date" name="date_to" class="form-control" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
    </div>
    <div class="cs-filters__actions">
        <button type="submit" class="btn btn-primary">Apply</button>
    </div>
</form>

<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', [
                'icon'  => 'ti-cash',
                'title' => 'No collections found for this period',
            ]) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Collector</th>
                            <th>Role</th>
                            <th>Transactions</th>
                            <th class="cs-num">Total Collected</th>
                            <th class="cs-num">Commission (10%)</th>
                            <th class="cs-num">Net Remitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalCommission = 0.0;
                        foreach ($rows as $row):
                            $totalCommission += (float) ($row['commission'] ?? 0);
                        ?>
                            <tr>
                                <td><?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?></td>
                                <td><?= esc((string) ($row['role_name'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['transaction_count'] ?? 0)) ?></td>
                                <td class="cs-num"><?= cs_money($row['total_collected'] ?? 0) ?></td>
                                <td class="cs-num"><?= cs_money($row['commission'] ?? 0) ?></td>
                                <td class="cs-num"><?= cs_money($row['net_remitted'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="fw-semibold">
                            <td colspan="4" class="text-end">Total Commission</td>
                            <td class="cs-num"><?= cs_money($totalCommission) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
