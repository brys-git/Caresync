<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$a    = $analytics ?? [];
$logs = $recent_activity ?? [];

$delinquent = (int) ($a['delinquent_members'] ?? 0);
$suspended  = (int) ($a['suspended_members'] ?? 0);

// Things somebody should act on, each linking to the page where it is handled.
// A line with nothing to do is not shown.
$attention = [
    ['admin/registration-approvals',           'ti-user-check',         'Registrations waiting for approval', (int) ($a['pending_approvals'] ?? 0)],
    ['admin/payment-monitoring?status=pending', 'ti-cash',               'Payments waiting to be verified',    (int) ($a['pending_payments'] ?? 0)],
    ['admin/client-management?open_claim=1',   'ti-clipboard-list',     'Open claims (handled by branches)',   (int) ($a['open_claims'] ?? 0)],
    ['admin/reports/overdue',                  'ti-alert-triangle',     'Accounts with overdue contributions', (int) ($a['overdue_accounts'] ?? 0)],
    ['admin/branch-management?tab=branches',   'ti-building-community', 'Branches with no manager yet',      (int) ($a['branches_without_manager'] ?? 0)],
];
$attention = array_values(array_filter($attention, static fn (array $row): bool => $row[3] > 0));

$monthItems = array_map(static fn (array $m): array => [
    'label' => (string) $m['month'],
    'full'  => (string) ($m['label'] ?? $m['month']),
    'value' => (float) $m['amount'],
], $a['monthly_collections'] ?? []);

$branchItems = array_map(static fn (array $b): array => [
    'label' => (string) $b['branch_name'],
    'value' => (float) $b['member_count'],
], $a['members_by_branch'] ?? []);
?>

<?= view('components/stat_rail', ['stats' => [
    [
        'label' => 'Plan holders',
        'icon'  => 'ti-users',
        'value' => $a['total_members'] ?? 0,
        'meta'  => number_format((float) ($a['active_members'] ?? 0)) . ' active · ' . number_format($delinquent) . ' delinquent · ' . number_format($suspended) . ' suspended',
    ],
    [
        'label' => 'Collected this month',
        'icon'  => 'ti-calendar-dollar',
        'value' => $a['collected_this_month'] ?? 0,
        'money' => true,
        'tone'  => 'money',
    ],
    [
        'label' => 'Collected to date',
        'icon'  => 'ti-cash',
        'value' => $a['total_collections'] ?? 0,
        'money' => true,
        'tone'  => 'money',
    ],
    [
        'label' => 'Awaiting approval',
        'icon'  => 'ti-user-check',
        'value' => $a['pending_approvals'] ?? 0,
        'tone'  => ((int) ($a['pending_approvals'] ?? 0) > 0) ? 'warn' : '',
        'meta'  => ((int) ($a['pending_approvals'] ?? 0) > 0) ? 'Registrations to review' : 'Nothing waiting',
    ],
    [
        'label' => 'Open service requests',
        'icon'  => 'ti-clipboard-list',
        'value' => $a['pending_service_requests'] ?? 0,
        'tone'  => ((int) ($a['pending_service_requests'] ?? 0) > 0) ? 'warn' : '',
    ],
]]) ?>

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

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <section class="cs-panel h-100">
            <div class="cs-panel__head">
                <div>
                    <h2 class="cs-panel__title">Monthly collections</h2>
                    <p class="cs-panel__note">Verified payments, last 12 months.</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/reports/collections') ?>">Report <i class="ti ti-arrow-narrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?= view('components/bar_chart', ['title' => 'Monthly collections, last 12 months', 'items' => $monthItems, 'money' => true, 'empty' => 'No collections recorded yet', 'updated' => $a['charts_updated_at'] ?? null]) ?>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="cs-panel h-100">
            <div class="cs-panel__head">
                <div>
                    <h2 class="cs-panel__title">Members by branch</h2>
                    <p class="cs-panel__note">Plan holders registered in each branch.</p>
                </div>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?= view('components/hbar_chart', ['title' => 'Members by branch', 'items' => $branchItems, 'empty' => 'No branch data yet', 'updated' => $a['charts_updated_at'] ?? null]) ?>
            </div>
        </section>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <section class="cs-panel">
            <div class="cs-panel__head">
                <div>
                    <h2 class="cs-panel__title">Recent activity</h2>
                    <p class="cs-panel__note">Actions recorded across every branch.</p>
                </div>
                <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/reports') ?>">
                    Reports <i class="ti ti-arrow-narrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="cs-panel__body cs-panel__body--flush">
                <?php if ($logs === []): ?>
                    <?= view('components/empty_state', [
                        'icon'  => 'ti-history',
                        'title' => 'No activity recorded yet',
                        'text'  => 'Approvals, payments, and record changes will show up here as staff work.',
                    ]) ?>
                <?php else: ?>
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th>Who</th>
                                    <th>What happened</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <?php $who = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'System'; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="cs-avatar" aria-hidden="true"><?= esc(cs_initials($who)) ?></span>
                                                <span class="cs-table__primary"><?= esc($who) ?></span>
                                            </div>
                                        </td>
                                        <td><?= esc((string) ($log['description'] ?? ($log['action'] ?? '—'))) ?></td>
                                        <td class="cs-table__sub"><?= cs_date($log['created_at'] ?? null, true) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Network</h2>
            </div>
            <div class="cs-panel__body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Branches</span>
                    <strong class="cs-num"><?= esc(number_format((float) ($a['total_branches'] ?? 0))) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="cs-muted">Staff</span>
                    <strong class="cs-num"><?= esc(number_format((float) ($a['total_staff'] ?? 0))) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-baseline">
                    <span class="cs-muted">Collectors</span>
                    <strong class="cs-num"><?= esc(number_format((float) ($a['total_collectors'] ?? 0))) ?></strong>
                </div>
            </div>
        </section>

        <section class="cs-panel">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Jump to</h2>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php
                $links = [
                    ['admin/registration-approvals', 'ti-user-check',        'Registration approvals', (int) ($a['pending_approvals'] ?? 0)],
                    ['admin/client-management',      'ti-users',             'Plan holders',           0],
                    ['admin/payment-monitoring',     'ti-cash',              'Payment monitoring',     0],
                    ['admin/branch-management',      'ti-building-community','Branches',               0],
                    ['admin/catalog',                'ti-package',           'Packages & services',    0],
                ];
                ?>
                <?php foreach ($links as [$url, $icon, $label, $count]): ?>
                    <a class="cs-linkrow" href="<?= base_url($url) ?>">
                        <i class="ti <?= $icon ?> cs-muted" aria-hidden="true"></i>
                        <span><?= esc($label) ?></span>
                        <?php if ($count > 0): ?>
                            <span class="cs-status cs-status--pending ms-auto"><?= esc((string) $count) ?></span>
                        <?php else: ?>
                            <i class="ti ti-chevron-right cs-muted ms-auto" aria-hidden="true"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
