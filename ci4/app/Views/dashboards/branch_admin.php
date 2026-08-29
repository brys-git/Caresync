<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= esc((string) ($page_title ?? 'Branch Operations Control Center')) ?></h1>
        <p class="text-muted mb-0">Operational dashboard for branch performance, approvals, payments, and service readiness.</p>
    </div>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Branch Operations</li>
        </ol>
    </nav>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1"><?= esc($branch_name) ?></h5>
                            <p class="text-muted mb-0">Welcome back, <?= esc($operator_name) ?>. Last updated <?= esc($current_time) ?>.</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">Branch Admin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Quick actions</h6>
                    <div class="list-group list-group-flush">
                        <a href="<?= base_url('branch-admin/service-package/requests') ?>" class="list-group-item list-group-item-action px-0 py-2">Review pending service applications</a>
                        <a href="<?= base_url('branch-admin/payment-tracking') ?>" class="list-group-item list-group-item-action px-0 py-2">Verify branch payments</a>
                        <a href="<?= base_url('branch-admin/service-package/ongoing') ?>" class="list-group-item list-group-item-action px-0 py-2">Monitor ongoing operations</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-2">Active Members</h6>
                    <h2 class="mb-0"><?= esc(number_format((int) ($member_stats['total_active'] ?? 0))) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-2">Pending Registrations</h6>
                    <h2 class="mb-0"><?= esc(number_format((int) ($pending_approvals ?? 0))) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-2">Collections This Month</h6>
                    <h2 class="mb-0">₱<?= esc(number_format((float) $collections_this_month, 2)) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-2">Ongoing Services</h6>
                    <h2 class="mb-0"><?= esc(number_format((int) $ongoing_services)) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Service Request Monitoring</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pending review</span>
                        <strong><?= esc(number_format((int) ($service_request_counts['pending'] ?? 0))) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Approved requests</span>
                        <strong><?= esc(number_format((int) ($service_request_counts['approved'] ?? 0))) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Rejected / returned</span>
                        <strong><?= esc(number_format((int) ($service_request_counts['rejected'] ?? 0))) ?></strong>
                    </div>
                    <div class="d-grid mt-3">
                        <a href="<?= base_url('branch-admin/service-package/requests') ?>" class="btn btn-outline-primary btn-sm">Open requests dashboard</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Operational Readiness</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Staff on duty</span>
                        <strong><?= esc(number_format((int) $staff_on_duty)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Upcoming events</span>
                        <strong><?= esc(number_format((int) $upcoming_services)) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delinquent members</span>
                        <strong><?= esc(number_format((int) ($member_stats['total_delinquent'] ?? 0))) ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted mb-3">Membership Health</h6>
                    <div class="mb-3">
                        <span class="text-muted">Active</span>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= esc(min(100, ($member_stats['total_active'] ?? 0) > 0 ? 100 : 0)) ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Pending</span>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= esc(min(100, ($member_stats['total_pending'] ?? 0) > 0 ? 100 : 0)) ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted">Suspended</span>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= esc(min(100, ($member_stats['total_suspended'] ?? 0) > 0 ? 100 : 0)) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Operational Alert Feed</h5>
                    <?php if (! empty($payment_alerts)): ?>
                        <?php foreach ($payment_alerts as $alert): ?>
                            <div class="alert alert-<?= esc($alert['type']) ?> d-flex justify-content-between align-items-center" role="alert">
                                <div>
                                    <strong><?= esc($alert['title']) ?>:</strong> <?= esc($alert['detail']) ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <div class="alert alert-success mb-0" role="alert">
                            No critical operational flags. Branch workflows are stable.
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Today's Service Agenda</h6>
                    <a href="<?= base_url('branch-admin/service-package/ongoing') ?>" class="text-decoration-none">View all</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($today_operations)): ?>
                        <div class="p-4 text-center text-muted">No scheduled services for today.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Time</th>
                                        <th scope="col">Service</th>
                                        <th scope="col">Client</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($today_operations as $operation): ?>
                                        <tr>
                                            <td><?= esc($operation['event_time'] ?: 'TBD') ?></td>
                                            <td><?= esc($operation['event_name']) ?></td>
                                            <td><?= esc(trim(($operation['first_name'] ?? '') . ' ' . ($operation['last_name'] ?? ''))) ?: esc($operation['unique_identifier'] ?? 'N/A') ?></td>
                                            <td><span class="badge bg-<?= $operation['status'] === 'in-progress' ? 'success' : 'secondary' ?>"><?= esc(ucfirst($operation['status'])) ?></span></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Recent Payments</h6>
                    <a href="<?= base_url('branch-admin/payment-tracking') ?>" class="text-decoration-none">All payments</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_payments)): ?>
                        <div class="p-4 text-center text-muted">No recent branch payments available.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Date</th>
                                        <th scope="col">Member</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?= esc(date('M d', strtotime($payment['payment_date'] ?? ''))) ?></td>
                                            <td><?= esc(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))) ?></td>
                                            <td>₱<?= esc(number_format((float) ($payment['amount'] ?? 0), 2)) ?></td>
                                            <td><span class="badge bg-<?= $payment['status'] === 'approved' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'secondary') ?>"><?= esc(ucfirst($payment['status'] ?? 'unknown')) ?></span></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Collections Trend</h6>
                    <?php if (! empty($payment_analytics['months'])): ?>
                        <?php foreach ($payment_analytics['months'] as $key => $label): ?>
                            <?php $value = $payment_analytics['totals'][$key] ?? 0; ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small><?= esc($label) ?></small>
                                    <strong>₱<?= esc(number_format($value, 2)) ?></strong>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= esc($value > 0 ? min(100, ($value / max(array_values($payment_analytics['totals']) ?: [1])) * 100) : 2) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <div class="text-muted">No approved collections recorded yet.</div>
                    <?php endif ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Payment Method Mix</h6>
                    <?php if (! empty($payment_analytics['method_breakdown'])): ?>
                        <?php foreach ($payment_analytics['method_breakdown'] as $method): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><?= esc($method['method']) ?></span>
                                    <small><?= esc($method['share']) ?>%</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= esc($method['share']) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php else: ?>
                        <div class="text-muted">No payment method breakdown available for the current month.</div>
                    <?php endif ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">Recent Branch Activity</h6>
                    <?php if (empty($activity_logs)): ?>
                        <div class="text-muted">No activity log entries available.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($activity_logs as $log): ?>
                                <div class="list-group-item px-0 py-2 border-0">
                                    <div class="small text-muted mb-1"><?= esc(date('M d H:i', strtotime($log['created_at'] ?? '')) ) ?></div>
                                    <div><strong><?= esc(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))) ?></strong> <?= esc($log['action']) ?> <?= esc($log['module']) ?></div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
