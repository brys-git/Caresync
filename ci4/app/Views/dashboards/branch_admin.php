<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/branch-dashboard.css') ?>">

<?php
    $activeMembers = (int) ($member_stats['total_active'] ?? 0);
    $pendingMembers = (int) ($member_stats['total_pending'] ?? 0);
    $suspendedMembers = (int) ($member_stats['total_suspended'] ?? 0);
    $totalMembers = max(1, $activeMembers + $pendingMembers + $suspendedMembers);
    $pendingReview = (int) ($service_request_counts['pending'] ?? 0);
    $pendingCount = count($payment_alerts ?? []);
?>

<div class="bd">

    <!-- ====== KPI Cards ====== -->
    <div class="bd-kpis">
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--blue"><i class="mdi mdi-account-group"></i></div>
            <div>
                <div class="bd-kpi__label">Active Members</div>
                <div class="bd-kpi__value"><?= esc(number_format($activeMembers)) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--teal"><i class="mdi mdi-file-document-check"></i></div>
            <div>
                <div class="bd-kpi__label">Pending Registrations</div>
                <div class="bd-kpi__value"><?= esc(number_format((int) ($pending_approvals ?? 0))) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--orange"><i class="mdi mdi-cash-multiple"></i></div>
            <div>
                <div class="bd-kpi__label">Collections This Month</div>
                <div class="bd-kpi__value">₱<?= esc(number_format((float) ($collections_this_month ?? 0), 2)) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--green"><i class="mdi mdi-briefcase-outline"></i></div>
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
                <a href="<?= site_url('branch-admin/payment-tracking') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-credit-card-check"></i> Verify Payment
                </a>
                <a href="<?= site_url('branch-admin/client-management') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-account-plus"></i> Record Member
                </a>
                <a href="<?= site_url('branch-admin/reports') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-chart-bar"></i> Generate Report
                </a>
                <a href="<?= site_url('branch-admin/payment-tracking') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-cash-check"></i> Verify Branch Payments
                </a>
            </div>
        </div>
        <div class="bd-alert-feed">
            <h3 class="bd-alert-feed__title">Alert Feed</h3>
            <?php if ($pendingCount > 0): ?>
                <div class="bd-alert-item">
                    <i class="mdi mdi-bell-ring-outline"></i>
                    <span>Alert: <?= $pendingCount ?> Pending Service Application<?= $pendingCount > 1 ? 's' : '' ?></span>
                </div>
            <?php elseif ($pendingReview > 0): ?>
                <div class="bd-alert-item">
                    <i class="mdi mdi-bell-ring-outline"></i>
                    <span>Alert: <?= $pendingReview ?> service request<?= $pendingReview > 1 ? 's' : '' ?> pending review</span>
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
        <button class="bd-tab bd-tab--active" data-tab="readiness" onclick="bdSwitchTab(this)">Operational Readiness</button>
        <button class="bd-tab" data-tab="financials" onclick="bdSwitchTab(this)">Financials</button>
        <button class="bd-tab" data-tab="operations" onclick="bdSwitchTab(this)">Operations</button>
        <button class="bd-tab" data-tab="membership" onclick="bdSwitchTab(this)">Membership</button>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Operational Readiness                                       -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-readiness">
        <!-- Membership Health (Donut) -->
        <div class="bd-card">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Membership Health</h3>
            </div>
            <div class="bd-card__body">
                <div class="bd-donut-wrap">
                    <div class="bd-donut">
                        <canvas id="bd-chart-membership"></canvas>
                    </div>
                    <div class="bd-legend">
                        <div class="bd-legend__item"><span class="bd-legend__dot" style="background:#38a169;"></span> Active</div>
                        <div class="bd-legend__item"><span class="bd-legend__dot" style="background:#f59e0b;"></span> Pending</div>
                        <div class="bd-legend__item"><span class="bd-legend__dot" style="background:#a0aec0;"></span> Suspended</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delinquent Members -->
        <div class="bd-card">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Delinquent Members</h3>
            </div>
            <div class="bd-card__body">
                <table class="bd-mini-table">
                    <thead>
                        <tr>
                            <th>Active</th>
                            <th>Pending</th>
                            <th>Suspended</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $activeMembers ?></td>
                            <td><?= $pendingMembers ?></td>
                            <td><?= $suspendedMembers ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Operational Alerts -->
        <div class="bd-card">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Operational Alerts</h3>
            </div>
            <div class="bd-card__body">
                <?php if ($pendingReview > 0): ?>
                    <a href="<?= site_url('branch-admin/service-package/requests') ?>" class="bd-alert-link">
                        <?= $pendingReview ?> pending review
                    </a>
                <?php else: ?>
                    <div class="bd-empty">No operational alerts.</div>
                <?php endif; ?>

                <?php if (! empty($payment_alerts)): ?>
                    <div class="bd-alerts-list" style="margin-top:14px;">
                        <?php foreach (array_slice($payment_alerts, 0, 3) as $alert): ?>
                            <div class="bd-alert-card bd-alert-card--<?= esc($alert['type'] ?? 'info') ?>">
                                <i class="mdi mdi-alert-circle-outline"></i>
                                <span><strong><?= esc($alert['title'] ?? '') ?>:</strong> <?= esc($alert['detail'] ?? '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Financials                                                  -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-financials" style="display:none;">
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Collections Trend</h3></div>
            <div class="bd-card__body">
                <?php if (! empty($payment_analytics['months'])): ?>
                    <?php foreach ($payment_analytics['months'] as $key => $label): ?>
                        <?php $value = $payment_analytics['totals'][$key] ?? 0; ?>
                        <div class="bd-stat-row">
                            <span class="bd-stat-row__label"><?= esc($label) ?></span>
                            <span class="bd-stat-row__value">₱<?= esc(number_format($value, 2)) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bd-empty">No collections data.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Payment Method Mix</h3></div>
            <div class="bd-card__body">
                <?php if (! empty($payment_analytics['method_breakdown'])): ?>
                    <?php foreach ($payment_analytics['method_breakdown'] as $method): ?>
                        <div class="bd-stat-row">
                            <span class="bd-stat-row__label"><?= esc($method['method']) ?></span>
                            <span class="bd-stat-row__value"><?= esc($method['share']) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bd-empty">No payment method data.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Recent Payments</h3></div>
            <div class="bd-card__body">
                <?php if (empty($recent_payments)): ?>
                    <div class="bd-empty">No recent payments.</div>
                <?php else: ?>
                    <?php foreach (array_slice($recent_payments, 0, 5) as $payment): ?>
                        <div class="bd-stat-row">
                            <span class="bd-stat-row__label"><?= esc(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))) ?></span>
                            <span class="bd-stat-row__value">₱<?= esc(number_format((float) ($payment['amount'] ?? 0), 2)) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Operations                                                  -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-operations" style="display:none;">
        <div class="bd-card" style="grid-column:1/-1;">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Today's Service Agenda</h3>
                <a href="<?= site_url('branch-admin/service-package/ongoing') ?>" class="bd-card__link">View all</a>
            </div>
            <div class="bd-card__body" style="padding-top:4px;">
                <?php if (empty($today_operations)): ?>
                    <div class="bd-empty">No scheduled services for today.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="bd-table">
                            <thead>
                                <tr><th>Time</th><th>Service</th><th>Client</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_operations as $op): ?>
                                    <tr>
                                        <td><?= esc($op['event_time'] ?: 'TBD') ?></td>
                                        <td><strong><?= esc($op['event_name']) ?></strong></td>
                                        <td><?= esc(trim(($op['first_name'] ?? '') . ' ' . ($op['last_name'] ?? ''))) ?: esc($op['unique_identifier'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="bd-badge <?= $op['status'] === 'in-progress' ? 'bd-badge--green' : 'bd-badge--slate' ?>">
                                                <?= esc(ucfirst($op['status'])) ?>
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
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Service Requests</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Pending review</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($service_request_counts['pending'] ?? 0))) ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Approved</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($service_request_counts['approved'] ?? 0))) ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Rejected</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($service_request_counts['rejected'] ?? 0))) ?></span>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Staff On Duty</h3></div>
            <div class="bd-card__body">
                <div class="bd-kpi__value" style="font-size:2.2rem;text-align:center;padding:16px 0;">
                    <?= esc(number_format((int) ($staff_on_duty ?? 0))) ?>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Upcoming events</span>
                    <span class="bd-stat-row__value"><?= esc(number_format((int) ($upcoming_services ?? 0))) ?></span>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Recent Activity</h3></div>
            <div class="bd-card__body">
                <?php if (empty($activity_logs)): ?>
                    <div class="bd-empty">No activity log entries.</div>
                <?php else: ?>
                    <?php foreach (array_slice($activity_logs, 0, 5) as $log): ?>
                        <div class="bd-stat-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
                            <span class="bd-stat-row__value" style="font-size:0.82rem;">
                                <?= esc(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?>
                                <span style="font-weight:600;color:var(--bd-ink-soft);"> <?= esc($log['action']) ?> <?= esc($log['module']) ?></span>
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

    <!-- ================================================================ -->
    <!-- TAB: Membership                                                  -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-membership" style="display:none;">
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Membership Overview</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Total Active</span>
                    <span class="bd-stat-row__value"><?= $activeMembers ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Total Pending</span>
                    <span class="bd-stat-row__value"><?= $pendingMembers ?></span>
                </div>
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Total Suspended</span>
                    <span class="bd-stat-row__value"><?= $suspendedMembers ?></span>
                </div>
                <div class="bd-stat-row" style="border-top:1px solid var(--bd-border);padding-top:12px;margin-top:4px;">
                    <span class="bd-stat-row__label" style="font-weight:800;">Total Members</span>
                    <span class="bd-stat-row__value" style="font-size:1.1rem;"><?= $activeMembers + $pendingMembers + $suspendedMembers ?></span>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Delinquent Accounts</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Delinquent members</span>
                    <span class="bd-stat-row__value" style="color:var(--bd-red);"><?= esc(number_format((int) ($member_stats['total_delinquent'] ?? 0))) ?></span>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Pending Approvals</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row">
                    <span class="bd-stat-row__label">Awaiting registration</span>
                    <span class="bd-stat-row__value" style="color:var(--bd-orange);"><?= esc(number_format((int) ($pending_approvals ?? 0))) ?></span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    'use strict';
    var membershipChart = null;

    /* --- Tab switching --- */
    window.bdSwitchTab = function (btn) {
        var tabId = btn.getAttribute('data-tab');
        document.querySelectorAll('#bd-tabs .bd-tab').forEach(function (t) { t.classList.remove('bd-tab--active'); });
        btn.classList.add('bd-tab--active');

        document.querySelectorAll('.bd-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('bd-tab-' + tabId);
        if (panel) panel.style.display = '';

        if (tabId === 'readiness' && !membershipChart) renderDonut();
    };

    /* --- Donut chart --- */
    var active = <?= json_encode($activeMembers) ?>;
    var pending = <?= json_encode($pendingMembers) ?>;
    var suspended = <?= json_encode($suspendedMembers) ?>;

    function renderDonut() {
        if (typeof Chart === 'undefined') return;
        var ctx = document.getElementById('bd-chart-membership');
        if (!ctx) return;
        if (membershipChart) membershipChart.destroy();

        membershipChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Pending', 'Suspended'],
                datasets: [{
                    data: [active, pending, suspended],
                    backgroundColor: ['#38a169', '#f59e0b', '#a0aec0'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var total = active + pending + suspended;
                                var pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: function (chart) {
                    var total = active + pending + suspended;
                    var ctx2 = chart.ctx;
                    var area = chart.chartArea;
                    var x = (area.left + area.right) / 2;
                    var y = (area.top + area.bottom) / 2;
                    ctx2.save();
                    ctx2.textAlign = 'center';
                    ctx2.textBaseline = 'middle';
                    ctx2.fillStyle = '#4a5568';
                    ctx2.font = '600 12px Manrope, sans-serif';
                    ctx2.fillText('Total', x, y - 10);
                    ctx2.fillStyle = '#1a202c';
                    ctx2.font = '800 20px Manrope, sans-serif';
                    ctx2.fillText(total, x, y + 12);
                    ctx2.restore();
                }
            }]
        });
    }

    renderDonut();
})();
</script>
<?= $this->endSection() ?>
