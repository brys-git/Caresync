<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $a = $analytics ?? []; ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= esc((string) ($page_title ?? 'System Admin Dashboard')) ?></h1>
        <p class="text-muted mb-0">System-wide overview across all branches.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Members</div><div class="h4 mb-0"><?= esc((string) ($a['total_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Active Members</div><div class="h4 mb-0 text-success"><?= esc((string) ($a['active_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Approvals</div><div class="h4 mb-0 text-warning"><?= esc((string) ($a['pending_approvals'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Collections</div><div class="h4 mb-0">P<?= esc(number_format((float) ($a['total_collections'] ?? 0), 2)) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="<?= base_url('admin/registration-approvals') ?>" class="list-group-item list-group-item-action px-3 py-2">Registration Approvals <?php if (($a['pending_approvals'] ?? 0) > 0): ?><span class="badge bg-warning float-end"><?= esc((string) $a['pending_approvals']) ?></span><?php endif; ?></a>
                    <a href="<?= base_url('admin/branch-management') ?>" class="list-group-item list-group-item-action px-3 py-2">Branch Management</a>
                    <a href="<?= base_url('admin/client-management') ?>" class="list-group-item list-group-item-action px-3 py-2">Client Management</a>
                    <a href="<?= base_url('admin/payment-monitoring') ?>" class="list-group-item list-group-item-action px-3 py-2">Payment Monitoring</a>
                    <a href="<?= base_url('admin/service-offer') ?>" class="list-group-item list-group-item-action px-3 py-2">Service &amp; Package Offers</a>
                    <a href="<?= base_url('admin/reports') ?>" class="list-group-item list-group-item-action px-3 py-2">Reports</a>
                    <a href="<?= base_url('admin/analytics') ?>" class="list-group-item list-group-item-action px-3 py-2">Full System Analytics</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">Branches / Staff / Collectors</div>
                    <div class="h5 mb-0"><?= esc((string) ($a['total_branches'] ?? 0)) ?> / <?= esc((string) ($a['total_staff'] ?? 0)) ?> / <?= esc((string) ($a['total_collectors'] ?? 0)) ?></div>
                    <div class="text-muted small mt-2">Pending service requests: <strong><?= esc((string) ($a['pending_service_requests'] ?? 0)) ?></strong></div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent System Activity</span>
                    <a href="<?= base_url('admin/reports') ?>" class="text-decoration-none small">View reports</a>
                </div>
                <div class="list-group list-group-flush">
                    <?php $logs = $recent_activity ?? []; ?>
                    <?php if (empty($logs)): ?>
                        <div class="list-group-item text-center text-muted py-4">No recent activity recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="list-group-item px-3 py-2 d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="small fw-semibold"><?= esc(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: 'System') ?></div>
                                    <div class="small text-muted"><?= esc((string) ($log['description'] ?? ($log['action'] ?? '-'))) ?></div>
                                </div>
                                <div class="small text-muted text-nowrap ms-2"><?= esc((string) ($log['created_at'] ?? '-')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
