<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'new'); ?>
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Service Details</h1>
            <p class="text-muted mb-0">Review the service information.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=services') ?>">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-2"><?= esc((string) ($service['service_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($service['description'] ?? 'No description available.')) ?></p>
            <div class="fw-semibold">Price: P<?= esc(number_format((float) ($service['base_price'] ?? 0), 2)) ?></div>
        </div>
    </div>

    <div class="mt-3">
            <?php if ($state === 'approved'): ?>
            <a class="btn btn-primary" href="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">Apply for Service</a>
        <?php elseif ($state === 'pending'): ?>
            <div class="alert alert-warning">Approval required before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
