<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="mb-3 text-end">
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/overdue?mode=csv') ?>">
        <i class="ti ti-download me-1"></i> Export CSV
    </a>
</div>

<?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
<?= $this->include('partials/reports_nav') ?>

<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', [
                'icon'  => 'ti-alert-triangle',
                'title' => 'No overdue accounts found',
            ]) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Plan Holder</th>
                            <th>Branch</th>
                            <th>Contact</th>
                            <th class="cs-num">Remaining Balance</th>
                            <th>Months Paid</th>
                            <th>Overdue Months</th>
                            <th>Days Overdue</th>
                            <th>Next Due Date</th>
                            <th>Forfeiture Countdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?>
                                    <div class="cs-table__sub"><?= esc((string) ($row['unique_identifier'] ?? '-')) ?></div>
                                </td>
                                <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['contact_number'] ?? '-')) ?></td>
                                <td class="cs-num"><?= cs_money($row['remaining_balance'] ?? 0) ?></td>
                                <td><?= esc((string) ($row['months_paid'] ?? 0)) ?></td>
                                <td><?= esc((string) ($row['overdue_months'] ?? 0)) ?></td>
                                <td><?= cs_status('overdue', (string) ($row['days_overdue'] ?? 0) . ' days') ?></td>
                                <td><?= cs_date($row['next_due_date'] ?? null) ?></td>
                                <td>
                                    <?php $daysLeft = $row['days_until_forfeiture'] ?? null; ?>
                                    <?php if ($daysLeft === null): ?>
                                        <span class="text-muted small">No months paid to forfeit</span>
                                    <?php elseif ($daysLeft <= 0): ?>
                                        <?= cs_status('failed', 'Forfeited on next run') ?>
                                    <?php elseif ($daysLeft <= 14): ?>
                                        <?= cs_status('pending', $daysLeft . ' days left') ?>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= esc((string) $daysLeft) ?> days left</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
