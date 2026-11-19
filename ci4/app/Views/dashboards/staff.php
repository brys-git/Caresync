<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$a = $analytics ?? [];
$pk = $a['package'] ?? [];

// What this branch needs done now; a line with nothing to do is hidden.
$attention = [
    ['staff/services/requests',                     'ti-clipboard-list', 'Service requests to review',      (int) ($a['pending_requests'] ?? 0)],
    ['staff/payment-management?status=pending',     'ti-cash',           'Payments waiting to be verified', (int) ($a['pending_payments'] ?? 0)],
    ['staff/payment-management', 'ti-clock-exclamation', 'Avails waiting for a down payment' . ($pk['next_deadline'] ? ' (next deadline ' . date('j M', strtotime($pk['next_deadline'])) . ')' : ''), (int) ($pk['awaiting_down_payment'] ?? 0)],
    ['staff/client-management', 'ti-alert-triangle', 'Package instalments overdue (₱' . number_format((float) ($pk['overdue_amount'] ?? 0), 2) . ')', (int) ($pk['overdue_instalments'] ?? 0)],
];
$attention = array_values(array_filter($attention, static fn (array $row): bool => $row[3] > 0));
?>

<?= view('components/stat_rail', ['stats' => [
    [
        'label' => 'Branch members',
        'icon'  => 'ti-users',
        'value' => $a['staff_members'] ?? 0,
    ],
    [
        'label' => 'Pending requests',
        'icon'  => 'ti-clipboard-list',
        'value' => $a['pending_requests'] ?? 0,
        'tone'  => ((int) ($a['pending_requests'] ?? 0) > 0) ? 'warn' : '',
    ],
    [
        'label' => 'Approved services',
        'icon'  => 'ti-checkbox',
        'value' => $a['assigned_services'] ?? 0,
        'tone'  => 'ok',
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
        'label' => 'Cash collected today',
        'icon'  => 'ti-cash',
        'value' => $a['cash_collected_today'] ?? 0,
        'money' => true,
        'tone'  => 'money',
        'meta'  => number_format((float) ($a['cash_payments_today'] ?? 0)) . ' cash payment' . ((int) ($a['cash_payments_today'] ?? 0) === 1 ? '' : 's') . ' today',
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

<section class="cs-panel">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Quick links</h2>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php
        $links = [
            ['staff/services/requests', 'ti-clipboard-list', 'Review service requests', (int) ($a['pending_requests'] ?? 0)],
            ['staff/client-management', 'ti-users', 'Plan holders', 0],
            ['staff/payment-management', 'ti-cash', 'Payment management', 0],
            ['staff/services', 'ti-package', 'Services & packages', 0],
            ['staff/services/ongoing', 'ti-clock', 'Ongoing services', 0],
            ['staff/reports', 'ti-report', 'Reports', 0],
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
<?= $this->endSection() ?>
