<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<p class="cs-muted mb-3">Welcome back, <?= esc($operator_name) ?> — <?= esc($branch_name) ?>. Last updated <?= esc($current_time) ?>.</p>

<?php
$an         = $analytics ?? [];
$pk         = $an['package'] ?? [];
$mem        = $an['members'] ?? [];
$overdue    = (int) ($an['overdue_accounts'] ?? 0);
$pendingSrv = (int) ($an['pending_service_requests'] ?? 0);
$pendingPay = (int) ($an['pending_payments'] ?? 0);
$unassigned = (int) ($unassigned_services ?? 0);

// What the branch should act on now; a line with nothing to do is hidden.
$attention = [
    ['branch-admin/registration-approvals',            'ti-user-check',         'Registrations waiting for approval',    (int) ($pending_approvals ?? 0)],
    ['branch-admin/payment-tracking?status=pending',   'ti-cash',               'Payments waiting to be verified',       $pendingPay],
    ['branch-admin/service-package/requests',          'ti-clipboard-list',     'Claims and service requests to review', $pendingSrv],
    ['branch-admin/reports/overdue',                   'ti-alert-triangle',     'Accounts with overdue payments (plan or package)',   $overdue],
    ['branch-admin/reports/avails?status=awaiting_down_payment', 'ti-clock-exclamation', 'Avails waiting for a down payment' . ($pk['next_deadline'] ? ' (next deadline ' . date('j M', strtotime($pk['next_deadline'])) . ')' : ''), (int) ($pk['awaiting_down_payment'] ?? 0)],
    ['branch-admin/reports/overdue', 'ti-alert-triangle', 'Package instalments overdue (₱' . number_format((float) ($pk['overdue_amount'] ?? 0), 2) . ')', (int) ($pk['overdue_instalments'] ?? 0)],
    ['branch-admin/service-package/ongoing',           'ti-calendar-event',     'Service events today without a crew',   $unassigned],
];
// Collectors: money held too long, and months not closed in time.
$ca = $collection_alerts ?? ['unremitted' => [], 'unclosed' => [], 'max_days' => 7];
if (! empty($ca['unremitted'])) {
    $names = array_values(array_unique(array_filter(array_map(static fn (array $r): string => (string) $r['collector_name'], $ca['unremitted']))));
    $attention[] = ['branch-admin/remittances', 'ti-clock-exclamation', 'Collected payments not remitted for more than ' . (int) $ca['max_days'] . ' days (' . implode(', ', $names) . ')', count($ca['unremitted'])];
}
foreach (($ca['unclosed'] ?? []) as $u) {
    $attention[] = ['branch-admin/reports/monthly-summary', 'ti-calendar-x', 'Collector ' . $u['collector_name'] . ' hasn\'t closed ' . $u['month_label'], 1];
}
$attention = array_values(array_filter($attention, static fn (array $row): bool => $row[3] > 0));

// Daily collections this month, every day of the month (days with no payment are empty bars).
$byDay = [];
foreach (($an['daily_collections'] ?? []) as $row) {
    $byDay[(string) $row['date']] = (float) $row['total'];
}
$dayItems = [];
$daysInMonth = (int) date('t');
for ($d = 1; $d <= $daysInMonth; $d++) {
    $date = date('Y-m-') . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
    $dayItems[] = [
        'label' => ($d === 1 || $d % 5 === 0) ? (string) $d : '',
        'full'  => date('M j, Y', strtotime($date)),
        'value' => $byDay[$date] ?? 0.0,
    ];
}
?>

<?= view('components/stat_rail', ['stats' => [
    [
        'label' => 'Plan holders',
        'icon'  => 'ti-users',
        'value' => $an['branch_members'] ?? 0,
        'meta'  => number_format((float) ($mem['active'] ?? 0)) . ' active · ' . number_format((float) ($mem['grace'] ?? 0)) . ' delinquent · ' . number_format((float) ($mem['suspended'] ?? 0)) . ' suspended',
    ],
    [
        'label' => 'Collected this month',
        'icon'  => 'ti-cash',
        'value' => $collections_this_month ?? 0,
        'money' => true,
        'tone'  => 'money',
    ],
    [
        'label' => 'Package balances',
        'icon'  => 'ti-package',
        'value' => $pk['package_balance'] ?? 0,
        'money' => true,
        'tone'  => ((int) ($pk['overdue_instalments'] ?? 0) > 0) ? 'warn' : 'money',
        'meta'  => number_format((float) ($pk['active_avails'] ?? 0)) . ' active · ' . number_format((float) ($pk['overdue_instalments'] ?? 0)) . ' instalment' . ((int) ($pk['overdue_instalments'] ?? 0) === 1 ? '' : 's') . ' overdue (₱' . number_format((float) ($pk['overdue_amount'] ?? 0), 2) . ')',
    ],
    [
        'label' => 'Awaiting approval',
        'icon'  => 'ti-user-plus',
        'value' => $pending_approvals ?? 0,
        'tone'  => ((int) ($pending_approvals ?? 0) > 0) ? 'warn' : '',
        'meta'  => ((int) ($pending_approvals ?? 0) > 0) ? 'Registrations to review' : 'Nothing waiting',
    ],
    [
        'label' => 'Open service requests',
        'icon'  => 'ti-clipboard-list',
        'value' => $pendingSrv,
        'tone'  => $pendingSrv > 0 ? 'warn' : '',
    ],
    [
        'label' => 'Ongoing services',
        'icon'  => 'ti-progress',
        'value' => $ongoing_services ?? 0,
    ],
]]) ?>

<div class="row g-3">
    <div class="col-lg-8">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Needs attention</h2>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if ($attention === []): ?>
                    <p class="cs-muted mb-0 p-3"><i class="ti ti-circle-check" aria-hidden="true"></i> Nothing needs attention right now.</p>
                <?php else: ?>
                    <?php foreach ($attention as [$url, $icon, $label, $count]): ?>
                        <a class="cs-linkrow" href="<?= esc(base_url($url), 'attr') ?>">
                            <i class="ti <?= esc($icon, 'attr') ?> cs-muted" aria-hidden="true"></i>
                            <span><?= esc($label) ?></span>
                            <span class="cs-status cs-status--pending ms-auto"><?= esc(number_format($count)) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <div>
                    <h2 class="cs-panel__title">Daily collections this month</h2>
                    <p class="cs-panel__note">Verified payments in your branch, day by day.</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="<?= base_url('branch-admin/reports/remittance') ?>">Report <i class="ti ti-arrow-narrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?= view('components/bar_chart', ['title' => 'Daily collections this month', 'items' => $dayItems, 'money' => true, 'unit' => 'Day', 'empty' => 'No collections recorded yet this month', 'updated' => $an['charts_updated_at'] ?? null]) ?>
            </div>
        </section>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Today's service agenda</h2>
                <a class="btn btn-ghost btn-sm" href="<?= base_url('branch-admin/service-package/ongoing') ?>">View all</a>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if (empty($today_operations)): ?>
                    <?= view('components/empty_state', ['icon' => 'ti-calendar-off', 'title' => 'No scheduled services for today']) ?>
                <?php else: ?>
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Client</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_operations as $operation): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= esc($operation['event_time'] ?: 'TBD') ?></td>
                                        <td><?= esc($operation['event_name']) ?></td>
                                        <td><?= esc(trim(($operation['first_name'] ?? '') . ' ' . ($operation['last_name'] ?? ''))) ?: esc($operation['unique_identifier'] ?? 'N/A') ?></td>
                                        <td><?= cs_status((string) $operation['status']) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </section>

        <section class="cs-panel">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Recent payments</h2>
                <a class="btn btn-ghost btn-sm" href="<?= base_url('branch-admin/payment-tracking') ?>">All payments</a>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if (empty($recent_payments)): ?>
                    <?= view('components/empty_state', ['icon' => 'ti-cash-off', 'title' => 'No recent branch payments available']) ?>
                <?php else: ?>
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Member</th>
                                    <th class="cs-num">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= cs_date($payment['payment_date'] ?? null) ?></td>
                                        <td><?= esc(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))) ?></td>
                                        <td class="cs-num"><?= cs_money($payment['amount'] ?? 0) ?></td>
                                        <td><?= cs_status((string) ($payment['status'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Quick actions</h2>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <a class="cs-linkrow" href="<?= base_url('branch-admin/service-package/requests') ?>">Review pending service applications</a>
                <a class="cs-linkrow" href="<?= base_url('branch-admin/payment-tracking') ?>">Verify branch payments</a>
                <a class="cs-linkrow" href="<?= base_url('branch-admin/service-package/ongoing') ?>">Monitor ongoing operations</a>
            </div>
        </section>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Service request monitoring</h2>
            </div>
            <div class="cs-panel__body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Pending review</span>
                    <strong class="cs-num"><?= esc(number_format((int) ($service_request_counts['pending'] ?? 0))) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Approved requests</span>
                    <strong class="cs-num"><?= esc(number_format((int) ($service_request_counts['approved'] ?? 0))) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <span class="cs-muted">Rejected / returned</span>
                    <strong class="cs-num"><?= esc(number_format((int) ($service_request_counts['rejected'] ?? 0))) ?></strong>
                </div>
                <a href="<?= base_url('branch-admin/service-package/requests') ?>" class="btn btn-outline-primary btn-sm w-100">Open requests dashboard</a>
            </div>
        </section>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Operational readiness</h2>
            </div>
            <div class="cs-panel__body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Staff on duty</span>
                    <strong class="cs-num"><?= esc(number_format((int) $staff_on_duty)) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Upcoming events</span>
                    <strong class="cs-num"><?= esc(number_format((int) $upcoming_services)) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Active staff</span>
                    <strong class="cs-num"><?= esc(number_format((int) ($an['staff_count'] ?? 0))) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline">
                    <span class="cs-muted">Delinquent members</span>
                    <strong class="cs-num"><?= esc(number_format((int) ($member_stats['total_delinquent'] ?? 0))) ?></strong>
                </div>
            </div>
        </section>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Collections trend</h2>
            </div>
            <div class="cs-panel__body">
                <?php if (! empty($payment_analytics['months'])): ?>
                    <?php $maxTotal = max(array_values($payment_analytics['totals']) ?: [1]); ?>
                    <?php foreach ($payment_analytics['months'] as $key => $label): ?>
                        <?php $value = $payment_analytics['totals'][$key] ?? 0; ?>
                        <div class="cs-meterrow">
                            <span><?= esc($label) ?></span>
                            <strong><?= cs_money($value) ?></strong>
                        </div>
                        <div class="cs-meter mb-3">
                            <div class="cs-meter__fill" style="width: <?= esc($value > 0 ? min(100, ($value / $maxTotal) * 100) : 2) ?>%"></div>
                        </div>
                    <?php endforeach ?>
                <?php else: ?>
                    <p class="cs-muted mb-0">No approved collections recorded yet.</p>
                <?php endif ?>
            </div>
        </section>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Payment method mix</h2>
            </div>
            <div class="cs-panel__body">
                <?php if (! empty($payment_analytics['method_breakdown'])): ?>
                    <?php foreach ($payment_analytics['method_breakdown'] as $method): ?>
                        <div class="cs-meterrow">
                            <span><?= esc($method['method']) ?></span>
                            <strong><?= esc($method['share']) ?>%</strong>
                        </div>
                        <div class="cs-meter mb-3">
                            <div class="cs-meter__fill" style="width: <?= esc($method['share']) ?>%"></div>
                        </div>
                    <?php endforeach ?>
                <?php else: ?>
                    <p class="cs-muted mb-0">No payment method breakdown available for the current month.</p>
                <?php endif ?>
            </div>
        </section>

        <section class="cs-panel">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Recent branch activity</h2>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if (empty($activity_logs)): ?>
                    <?= view('components/empty_state', ['icon' => 'ti-history', 'title' => 'No activity log entries available']) ?>
                <?php else: ?>
                    <?php foreach ($activity_logs as $log): ?>
                        <div class="cs-linkrow cs-linkrow--block">
                            <div class="cs-table__sub mb-1"><?= cs_datetime($log['created_at'] ?? null) ?></div>
                            <div><strong><?= esc(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?></strong> <?= esc($log['action']) ?> <?= esc($log['module']) ?></div>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
