<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $a = $analytics ?? []; ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= esc((string) ($page_title ?? 'Staff Dashboard')) ?></h1>
        <p class="text-muted mb-0">Your branch's day-to-day operations at a glance.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Branch Members</div><div class="h4 mb-0"><?= esc((string) ($a['staff_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Requests</div><div class="h4 mb-0 text-warning"><?= esc((string) ($a['pending_requests'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Approved Services</div><div class="h4 mb-0 text-success"><?= esc((string) ($a['assigned_services'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Cash Collected Today</div><div class="h4 mb-0">P<?= esc(number_format((float) ($a['cash_collected_today'] ?? 0), 2)) ?></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Quick Links</div>
        <div class="list-group list-group-flush">
            <a href="<?= base_url('staff/services/requests') ?>" class="list-group-item list-group-item-action px-3 py-2">Review Service Requests <?php if (($a['pending_requests'] ?? 0) > 0): ?><span class="badge bg-warning float-end"><?= esc((string) $a['pending_requests']) ?></span><?php endif; ?></a>
            <a href="<?= base_url('staff/client-management') ?>" class="list-group-item list-group-item-action px-3 py-2">Client Management</a>
            <a href="<?= base_url('staff/payment-management') ?>" class="list-group-item list-group-item-action px-3 py-2">Payment Management</a>
            <a href="<?= base_url('staff/services') ?>" class="list-group-item list-group-item-action px-3 py-2">Services &amp; Packages</a>
            <a href="<?= base_url('staff/services/ongoing') ?>" class="list-group-item list-group-item-action px-3 py-2">Ongoing Services</a>
            <a href="<?= base_url('staff/reports') ?>" class="list-group-item list-group-item-action px-3 py-2">Reports</a>
            <a href="<?= base_url('staff/analytics') ?>" class="list-group-item list-group-item-action px-3 py-2">My Analytics</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
