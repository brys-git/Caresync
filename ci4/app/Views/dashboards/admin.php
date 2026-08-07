<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin-dashboard.css') ?>">

<div class="ad" id="ad-dashboard">

    <!-- ====== Header ====== -->
    <div class="ad-header">
        <div class="ad-header__left">
            <h1 class="ad-header__title">System Admin Dashboard</h1>
            <p class="ad-header__sub">Executive management overview — approvals, collections, branches.</p>
        </div>
        <div class="ad-header__date" id="ad-date"><?= date('F j, Y') ?></div>
    </div>

    <!-- ====== KPI Cards ====== -->
    <div class="ad-kpis" id="ad-kpis">
        <div class="ad-kpi ad-kpi--blue">
            <div class="ad-kpi__top">
                <div class="ad-kpi__icon"><i class="mdi mdi-account-group"></i></div>
                <div class="ad-kpi__label">Plan Holders</div>
            </div>
            <div class="ad-kpi__value" id="ad-kpi-ph">—</div>
            <div class="ad-kpi__trend" id="ad-kpi-ph-trend"></div>
        </div>
        <div class="ad-kpi ad-kpi--teal">
            <div class="ad-kpi__top">
                <div class="ad-kpi__icon"><i class="mdi mdi-account-multiple-plus"></i></div>
                <div class="ad-kpi__label">Beneficiaries</div>
            </div>
            <div class="ad-kpi__value" id="ad-kpi-ben">—</div>
            <div class="ad-kpi__trend" id="ad-kpi-ben-trend"></div>
        </div>
        <div class="ad-kpi ad-kpi--highlight">
            <div class="ad-kpi__top">
                <div class="ad-kpi__icon"><i class="mdi mdi-file-clock"></i></div>
                <div class="ad-kpi__label">Pending Applications</div>
            </div>
            <div class="ad-kpi__value" id="ad-kpi-pend">—</div>
            <div class="ad-kpi__trend" id="ad-kpi-pend-trend"></div>
        </div>
        <div class="ad-kpi ad-kpi--indigo">
            <div class="ad-kpi__top">
                <div class="ad-kpi__icon"><i class="mdi mdi-cash-check"></i></div>
                <div class="ad-kpi__label">Monthly Collections</div>
            </div>
            <div class="ad-kpi__value" id="ad-kpi-coll">—</div>
            <div class="ad-kpi__trend" id="ad-kpi-coll-trend"></div>
        </div>
        <div class="ad-kpi ad-kpi--purple">
            <div class="ad-kpi__top">
                <div class="ad-kpi__icon"><i class="mdi mdi-office-building"></i></div>
                <div class="ad-kpi__label">Branches</div>
            </div>
            <div class="ad-kpi__value" id="ad-kpi-branch">—</div>
            <div class="ad-kpi__trend" id="ad-kpi-branch-trend"></div>
        </div>
        <div class="ad-kpi ad-kpi--green">
            <div class="ad-kpi__top">
                <div class="ad-kpi__icon"><i class="mdi mdi-badge-account"></i></div>
                <div class="ad-kpi__label">Active Staff</div>
            </div>
            <div class="ad-kpi__value" id="ad-kpi-staff">—</div>
            <div class="ad-kpi__trend" id="ad-kpi-staff-trend"></div>
        </div>
    </div>

    <!-- ====== Two-column layout ====== -->
    <div class="ad-grid-2">

        <!-- LEFT COLUMN -->
        <div class="ad-stack">

            <!-- Monthly Collections (Bar Chart) -->
            <div class="ad-card">
                <div class="ad-card__header">
                    <h2 class="ad-card__title">Monthly Collections</h2>
                </div>
                <div class="ad-card__body">
                    <div class="ad-chart-wrap">
                        <canvas id="ad-chart-collections"></canvas>
                    </div>
                </div>
            </div>

            <!-- Branch Performance -->
            <div class="ad-card">
                <div class="ad-card__header">
                    <h2 class="ad-card__title">Branch Performance</h2>
                </div>
                <div class="ad-card__body" style="padding-top:8px;">
                    <div class="table-responsive">
                        <table class="ad-perf-table" id="ad-perf-table">
                            <thead>
                                <tr>
                                    <th>Metrics</th>
                                    <th>Plan Holders</th>
                                    <th>Payments</th>
                                    <th>Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" class="ad-empty">Loading branch data…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="ad-stack">

            <!-- Pending Approvals -->
            <div class="ad-card">
                <div class="ad-card__header">
                    <h2 class="ad-card__title">Pending Approvals</h2>
                </div>
                <div class="ad-card__body" id="ad-pending-area">
                    <div class="ad-empty">Loading pending applications…</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="ad-card">
                <div class="ad-card__header">
                    <h2 class="ad-card__title">Quick Actions</h2>
                </div>
                <div class="ad-card__body">
                    <div class="ad-actions-grid">
                        <a href="<?= site_url('admin/client-management') ?>" class="ad-action-tile">
                            <div class="ad-action-tile__icon"><i class="mdi mdi-account-plus"></i></div>
                            <div class="ad-action-tile__label">+ Register<br>Client</div>
                        </a>
                        <a href="<?= site_url('admin/branches') ?>" class="ad-action-tile">
                            <div class="ad-action-tile__icon"><i class="mdi mdi-office-building-plus"></i></div>
                            <div class="ad-action-tile__label">+ Add<br>Branch</div>
                        </a>
                        <a href="<?= site_url('admin/reports') ?>" class="ad-action-tile">
                            <div class="ad-action-tile__icon"><i class="mdi mdi-file-chart-outline"></i></div>
                            <div class="ad-action-tile__label">+ Generate<br>Report</div>
                        </a>
                        <a href="<?= site_url('admin/payment-monitoring') ?>" class="ad-action-tile">
                            <div class="ad-action-tile__icon"><i class="mdi mdi-credit-card-outline"></i></div>
                            <div class="ad-action-tile__label">+ Generate<br>Report</div>
                        </a>
                        <a href="<?= site_url('notifications') ?>" class="ad-action-tile">
                            <div class="ad-action-tile__icon"><i class="mdi mdi-bell-plus-outline"></i></div>
                            <div class="ad-action-tile__label">+ Generate<br>Report</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="ad-card">
                <div class="ad-card__header">
                    <h2 class="ad-card__title">Recent Activity</h2>
                </div>
                <div class="ad-card__body" id="ad-activity-area">
                    <div class="ad-empty">Loading recent activity…</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== System Health Bar ====== -->
    <div class="ad-health-bar" id="ad-health-bar">
        <div class="ad-health-bar__left">System Health</div>
        <div class="ad-health-bar__items" id="ad-health-items">
            <span class="ad-health-item"><span class="ad-health-dot ad-health-dot--ok"></span> Database</span>
            <span class="ad-health-item"><span class="ad-health-dot ad-health-dot--ok"></span> Backup</span>
            <span class="ad-health-item"><span class="ad-health-dot ad-health-dot--ok"></span> Storage</span>
            <span class="ad-health-item"><span class="ad-health-dot ad-health-dot--ok"></span> Server</span>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    'use strict';

    var endpoint = <?= json_encode(site_url('admin/dashboard/data')) ?>;
    var adminUrl = <?= json_encode(site_url('admin/branch-management')) ?>;
    var reviewUrl = <?= json_encode(site_url('admin/reports')) ?>;

    var currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 });
    var numFmt = new Intl.NumberFormat('en-PH');
    var chart = null;

    function $(id) { return document.getElementById(id); }
    function setText(id, v) { var el = $(id); if (el) el.textContent = v; }
    function setHTML(id, v) { var el = $(id); if (el) el.innerHTML = v; }

    function fmtTrend(change) {
        if (change === undefined || change === null) return '';
        var n = Number(change);
        if (n > 0) return '<span class="ad-kpi__trend--up"><i class="mdi mdi-arrow-up"></i> +' + n + '% vs last month</span>';
        if (n < 0) return '<span class="ad-kpi__trend--down"><i class="mdi mdi-arrow-down"></i> ' + n + '% vs last month</span>';
        return '<span class="ad-kpi__trend--flat"><i class="mdi mdi-minus"></i> 0% vs last month</span>';
    }

    function renderKpis(data) {
        var kpis = data.kpis || {};
        var trends = data.kpi_trends || {};

        setText('ad-kpi-ph', numFmt.format(Number(kpis.total_plan_holders || 0)));
        setText('ad-kpi-ben', numFmt.format(Number(kpis.active_beneficiaries || 0)));
        setText('ad-kpi-pend', numFmt.format(Number(kpis.pending_applications || 0)));
        setText('ad-kpi-coll', currency.format(Number(kpis.monthly_collections || 0)));
        setText('ad-kpi-branch', numFmt.format(Number(kpis.active_branches || 0)));
        setText('ad-kpi-staff', numFmt.format(Number(kpis.active_staff || 0)));

        setHTML('ad-kpi-ph-trend', fmtTrend(trends.total_plan_holders?.change));
        setHTML('ad-kpi-ben-trend', fmtTrend(trends.active_beneficiaries?.change));
        setHTML('ad-kpi-pend-trend', fmtTrend(trends.pending_applications?.change));
        setHTML('ad-kpi-coll-trend', fmtTrend(trends.monthly_collections?.change));
        setHTML('ad-kpi-branch-trend', fmtTrend(trends.active_branches?.change));
        setHTML('ad-kpi-staff-trend', fmtTrend(trends.active_staff?.change));
    }

    function renderChart(data) {
        var pt = data.payment_trend || {};
        var labels = pt.months || [];
        var values = pt.totals || [];

        var ctx = $('ad-chart-collections');
        if (!ctx) return;
        if (chart) chart.destroy();

        chart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Collections',
                    data: values,
                    backgroundColor: '#7c6ce7',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.55,
                    categoryPercentage: 0.7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: '600', size: 11 }, color: '#a0aec0' }
                    },
                    y: {
                        grid: { color: '#f1f4f8' },
                        ticks: {
                            font: { weight: '600', size: 11 },
                            color: '#a0aec0',
                            callback: function (v) {
                                if (v >= 1000) return '₱' + (v / 1000) + 'k';
                                return '₱' + v;
                            }
                        },
                        beginAtZero: true,
                    }
                }
            }
        });
    }

    function renderBranchPerf(branchPerformance) {
        var tbody = document.querySelector('#ad-perf-table tbody');
        if (!tbody) return;

        if (!Array.isArray(branchPerformance) || branchPerformance.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="ad-empty">No branch data available.</td></tr>';
            return;
        }

        var maxPayments = Math.max.apply(null, branchPerformance.map(function (b) { return Number(b.payments || 0); }));
        if (maxPayments === 0) maxPayments = 1;

        var maxApps = Math.max.apply(null, branchPerformance.map(function (b) { return Number(b.applications || 0); }));
        if (maxApps === 0) maxApps = 1;

        tbody.innerHTML = branchPerformance.map(function (b) {
            var payPct = Math.min(100, (Number(b.payments || 0) / maxPayments) * 100);
            var appPct = Math.min(100, (Number(b.applications || 0) / maxApps) * 100);
            var phPct = Math.min(100, (Number(b.plan_holders || 0) / Math.max.apply(null, branchPerformance.map(function (x) { return Number(x.plan_holders || 0); }))) * 100);

            return '<tr>' +
                '<td><strong>' + (b.branch_name || '—') + '</strong></td>' +
                '<td>' +
                    '<div class="ad-perf__bar-wrap">' +
                        '<span class="ad-perf__amount">₱' + (Number(b.payments || 0) / 1000).toFixed(1) + 'k</span>' +
                        '<div class="ad-perf__bar"><div class="ad-perf__bar-fill ad-perf__bar-fill--teal" style="width:' + phPct + '%"></div></div>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="ad-perf__bar-wrap">' +
                        '<span class="ad-perf__amount">₱' + (Number(b.payments || 0) / 1000).toFixed(1) + 'k</span>' +
                        '<div class="ad-perf__bar"><div class="ad-perf__bar-fill" style="width:' + payPct + '%"></div></div>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="ad-perf__bar-wrap">' +
                        '<span class="ad-perf__amount">₱' + (Number(b.applications || 0) / 1000).toFixed(1) + 'k</span>' +
                        '<div class="ad-perf__bar"><div class="ad-perf__bar-fill ad-perf__bar-fill--green" style="width:' + appPct + '%"></div></div>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        }).join('');
    }

    function renderPending(pendingList) {
        var area = $('ad-pending-area');
        if (!area) return;

        if (!Array.isArray(pendingList) || pendingList.length === 0) {
            area.innerHTML = '<div class="ad-empty"><i class="mdi mdi-check-circle-outline"></i>No pending applications.</div>';
            return;
        }

        var html = '<div class="ad-pending-list">';
        pendingList.forEach(function (item) {
            var appNo = item.application_no || ('APP-' + (item.application_id || ''));
            var name = ((item.first_name || '') + ' ' + (item.last_name || '')).trim() || 'Unknown';
            var branch = item.branch_name || '';

            html += '<div class="ad-pending-item">' +
                '<span class="ad-pending-item__id">' + appNo + '</span>' +
                '<div class="ad-pending-item__body">' +
                    '<div class="ad-pending-item__name">' + name + '</div>' +
                    '<div class="ad-pending-item__branch">' + branch + '</div>' +
                '</div>' +
                '<div class="ad-pending-item__actions">' +
                    '<a href="' + reviewUrl + '" class="ad-btn ad-btn--green">Approve</a>' +
                    '<a href="' + adminUrl + '" class="ad-btn ad-btn--outline">Review</a>' +
                '</div>' +
            '</div>';
        });
        html += '</div>';
        area.innerHTML = html;
    }

    function renderActivity(recentActivity) {
        var area = $('ad-activity-area');
        if (!area) return;

        if (!Array.isArray(recentActivity) || recentActivity.length === 0) {
            area.innerHTML = '<div class="ad-empty">No recent activity.</div>';
            return;
        }

        var html = '<div class="ad-timeline">';
        recentActivity.slice(0, 8).forEach(function (item) {
            var action = (item.action || '').toLowerCase();
            var module = (item.module || '').toLowerCase();
            var dotClass = 'ad-timeline__dot--blue';
            var icon = 'mdi-information-outline';

            if (action.indexOf('payment') !== -1 || module.indexOf('payment') !== -1) {
                dotClass = 'ad-timeline__dot--green'; icon = 'mdi-credit-card-check-outline';
            } else if (action.indexOf('approved') !== -1 || action.indexOf('approve') !== -1) {
                dotClass = 'ad-timeline__dot--green'; icon = 'mdi-check-circle-outline';
            } else if (action.indexOf('registered') !== -1 || action.indexOf('register') !== -1 || module.indexOf('client') !== -1 || module.indexOf('plan') !== -1) {
                dotClass = 'ad-timeline__dot--blue'; icon = 'mdi-account-check-outline';
            } else if (action.indexOf('service') !== -1 || module.indexOf('service') !== -1) {
                dotClass = 'ad-timeline__dot--purple'; icon = 'mdi-wrench-outline';
            } else if (action.indexOf('rejected') !== -1) {
                dotClass = 'ad-timeline__dot--orange'; icon = 'mdi-close-circle-outline';
            }

            var who = ((item.first_name || '') + ' ' + (item.last_name || '')).trim() || 'System';
            var desc = item.description || item.action || 'Activity';
            var amount = item.amount ? ' on ' + currency.format(Number(item.amount)) : '';

            html += '<div class="ad-timeline__item">' +
                '<div class="ad-timeline__dot ' + dotClass + '"><i class="mdi ' + icon + '"></i></div>' +
                '<div class="ad-timeline__text"><strong>' + who + '</strong> ' + desc + amount + '</div>' +
            '</div>';
        });
        html += '</div>';
        area.innerHTML = html;
    }

    function renderHealth(health) {
        var items = $('ad-health-items');
        if (!items || !health) return;

        function dot(status, okLabel) {
            var cls = status === 'Online' || status === 'Healthy' ? 'ad-health-dot--ok' : (status === 'Maintenance' ? 'ad-health-dot--warn' : 'ad-health-dot--bad');
            return '<span class="ad-health-item"><span class="ad-health-dot ' + cls + '"></span> ' + okLabel + '</span>';
        }

        items.innerHTML =
            dot(health.database_status, 'Database') +
            dot(health.last_backup ? 'ok' : 'none', 'Backup') +
            dot(health.storage_usage ? 'ok' : 'none', 'Storage') +
            dot(health.server_status, 'Server');
    }

    function renderDashboard(data) {
        renderKpis(data);
        renderChart(data);
        renderBranchPerf(data.branch_performance || []);
        renderPending(data.pending_list || []);
        renderActivity(data.recent_activity || []);
        renderHealth(data.system_health || {});

        var now = new Date();
        setText('ad-date', now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }));
    }

    function loadDashboard() {
        fetch(endpoint + '?t=' + Date.now(), { headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== 'success') throw new Error(payload?.message || 'Invalid response');
                renderDashboard(payload.data || {});
            })
            .catch(function () {
                ['ad-kpi-ph','ad-kpi-ben','ad-kpi-pend','ad-kpi-coll','ad-kpi-branch','ad-kpi-staff'].forEach(function (id) {
                    setText(id, 'Error');
                });
                setHTML('ad-pending-area', '<div class="ad-empty">Failed to load data.</div>');
                setHTML('ad-activity-area', '<div class="ad-empty">Failed to load data.</div>');
            });
    }

    loadDashboard();
})();
</script>
<?= $this->endSection() ?>
