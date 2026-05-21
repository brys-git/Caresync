<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Service Details</h4>
        <div>
            <a href="<?= site_url('/branch-admin/services/edit/' . (int) ($service['offer_id'] ?? 0)) ?>" class="btn btn-primary btn-sm">Edit</a>
            <a href="<?= site_url('/branch-admin/service-package/services') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Service Name:</strong> <?= esc($service['service_name'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Base Price:</strong> <?= number_format((float) ($service['base_price'] ?? 0), 2) ?></div>
                <div class="col-md-3"><strong>Status:</strong> <?= esc(ucfirst((string) ($service['status'] ?? '-'))) ?></div>
                <div class="col-12"><strong>Description:</strong> <?= esc($service['description'] ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
