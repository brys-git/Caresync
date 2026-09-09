<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h1 class="h4 mb-3">My Analytics</h1>

    <?php $a = $analytics ?? []; ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Branch Members</div><div class="h4 mb-0"><?= esc((string) ($a['staff_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Approved Services</div><div class="h4 mb-0"><?= esc((string) ($a['assigned_services'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Requests</div><div class="h4 mb-0"><?= esc((string) ($a['pending_requests'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Cash Payments Today</div><div class="h4 mb-0"><?= esc((string) ($a['cash_payments_today'] ?? 0)) ?></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="text-muted small">Cash Collected Today (Pending Verification)</div>
            <div class="h4 mb-0">P<?= esc(number_format((float) ($a['cash_collected_today'] ?? 0), 2)) ?></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
