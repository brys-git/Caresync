<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/branch-dashboard.css') ?>">

<?php
    $userName = esc(session()->get('first_name') ?? session()->get('user_name') ?? 'Staff');
?>

<div class="bd">

    <!-- ====== KPI Cards ====== -->
    <div class="bd-kpis">
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--blue"><i class="mdi mdi-account-group"></i></div>
            <div>
                <div class="bd-kpi__label">Total Clients</div>
                <div class="bd-kpi__value"><?= esc(number_format((int) ($total_clients ?? 0))) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--green"><i class="mdi mdi-account-check"></i></div>
            <div>
                <div class="bd-kpi__label">Active Clients</div>
                <div class="bd-kpi__value"><?= esc(number_format((int) ($active_clients ?? 0))) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--orange"><i class="mdi mdi-cash-multiple"></i></div>
            <div>
                <div class="bd-kpi__label">Collections This Month</div>
                <div class="bd-kpi__value">₱<?= esc(number_format((float) ($total_collections ?? 0), 2)) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--teal"><i class="mdi mdi-briefcase-outline"></i></div>
            <div>
                <div class="bd-kpi__label">Ongoing Services</div>
                <div class="bd-kpi__value"><?= esc(number_format((int) ($ongoing_services ?? 0))) ?></div>
            </div>
        </div>
    </div>

    <!-- ====== Quick Actions + Alert Feed ====== -->
    <div class="bd-quick-bar">
        <div class="bd-quick-actions">
            <h3 class="bd-quick-actions__title">Quick Actions</h3>
            <div class="bd-quick-actions__list">
                <a href="<?= site_url('staff/payment-management') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-credit-card-check"></i> Record Payment
                </a>
                <a href="<?= site_url('staff/client-management') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-account-plus"></i> Register Client
                </a>
                <a href="<?= site_url('staff/services/ongoing') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-tools"></i> View Ongoing Services
                </a>
                <a href="<?= site_url('staff/reports') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-chart-bar"></i> Generate Report
                </a>
            </div>
        </div>
        <div class="bd-alert-feed">
            <h3 class="bd-alert-feed__title">Alert Feed</h3>
            <?php if ((int) ($pending_payments ?? 0) > 0): ?>
                <div class="bd-alert-item">
                    <i class="mdi mdi-bell-ring-outline"></i>
                    <span>Alert: <?= (int) ($pending_payments ?? 0) ?> payment<?= (int) ($pending_payments ?? 0) > 1 ? 's' : '' ?> pending verification</span>
                </div>
            <?php elseif ((int) ($service_requests ?? 0) > 0): ?>
                <div class="bd-alert-item">
                    <i class="mdi mdi-bell-ring-outline"></i>
                    <span>Alert: <?= (int) ($service_requests ?? 0) ?> service request<?= (int) ($service_requests ?? 0) > 1 ? 's' : '' ?> pending</span>
                </div>
            <?php else: ?>
                <div class="bd-alert-item" style="color:var(--bd-green);">
                    <i class="mdi mdi-check-circle-outline" style="color:var(--bd-green);"></i>
                    <span>All clear. No pending alerts.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ====== Tabs ====== -->
    <div class="bd-tabs" id="bd-tabs">
        <button class="bd-tab bd-tab--active" data-tab="overview" onclick="bdSwitchTab(this)">Overview</button>
        <button class="bd-tab" data-tab="tasks" onclick="bdSwitchTab(this)">Today's Tasks</button>
        <button class="bd-tab" data-tab="activity" onclick="bdSwitchTab(this)">Recent Activity</button>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Overview                                                    -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-overview">
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">My Stats</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Total Clients</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($total_clients ?? 0))) ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Active Clients</span>
                    <span class="bd-stat-row__value" style="color:var(--bd-green);"><?= esc(number_format((int) ($active_clients ?? 0))) ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Inactive Clients</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($total_clients ?? 0) - (int) ($active_clients ?? 0))) ?></span>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Payments</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Collections This Month</span>
                    <span class="bd-stat-row__value">₱<?= esc(number_format((float) ($total_collections ?? 0), 2)) ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Pending Payments</span>
                    <span class="bd-stat-row__value" style="color:var(--bd-orange);"><?= esc(number_format((int) ($pending_payments ?? 0))) ?></span>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Services</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Pending Requests</span>
                    <span class="bd-stat-row__value" style="color:var(--bd-orange);"><?= esc(number_format((int) ($service_requests ?? 0))) ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Ongoing Services</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($ongoing_services ?? 0))) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Today's Tasks                                               -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-tasks" style="display:none;">
        <div class="bd-card" style="grid-column:1/-1;">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Today's Service Agenda</h3>
                <a href="<?= site_url('staff/services/ongoing') ?>" class="bd-card__link">View all</a>
            </div>
            <div class="bd-card__body" style="padding-top:4px;">
                <?php if (empty($today_tasks)): ?>
                    <div class="bd-empty">No scheduled services for today.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="bd-table">
                            <thead>
                                <tr><th>Time</th><th>Service</th><th>Client</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_tasks as $task): ?>
                                    <tr>
                                        <td><?= esc($task['event_time'] ?: 'TBD') ?></td>
                                        <td><strong><?= esc($task['service_name'] ?? $task['event_type'] ?? '-') ?></strong></td>
                                        <td><?= esc(trim(($task['first_name'] ?? '') . ' ' . ($task['last_name'] ?? ''))) ?: 'N/A' ?></td>
                                        <td>
                                            <span class="bd-badge <?= ($task['status'] ?? '') === 'in-progress' ? 'bd-badge--green' : 'bd-badge--slate' ?>">
                                                <?= esc(ucfirst($task['status'] ?? 'scheduled')) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Recent Activity                                             -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-activity" style="display:none;">
        <div class="bd-card" style="grid-column:1/-1;">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Recent Activity</h3>
            </div>
            <div class="bd-card__body">
                <?php if (empty($recent_activity)): ?>
                    <div class="bd-empty">No recent activity.</div>
                <?php else: ?>
                    <?php foreach ($recent_activity as $log): ?>
                        <div class="bd-stat-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
                            <span class="bd-stat-row__value" style="font-size:0.82rem;">
                                <?= esc(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?>
                                <span style="font-weight:600;color:var(--bd-ink-soft);"> <?= esc($log['action'] ?? '') ?> <?= esc($log['module'] ?? '') ?></span>
                            </span>
                            <span style="font-size:0.72rem;color:var(--bd-ink-faint);">
                                <?= esc(date('M d H:i', strtotime($log['created_at'] ?? ''))) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    window.bdSwitchTab = function (btn) {
        var tabId = btn.getAttribute('data-tab');
        document.querySelectorAll('#bd-tabs .bd-tab').forEach(function (t) { t.classList.remove('bd-tab--active'); });
        btn.classList.add('bd-tab--active');

        document.querySelectorAll('.bd-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('bd-tab-' + tabId);
        if (panel) panel.style.display = '';
    };
})();
</script>
<?= $this->endSection() ?>
