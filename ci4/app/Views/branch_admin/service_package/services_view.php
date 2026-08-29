<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Service Details</h4>
        <div>
            <a href="<?= site_url('/branch-admin/services/edit/' . (int) $service['service_id']) ?>" class="btn btn-primary btn-sm">Edit</a>
            <a href="<?= site_url('/branch-admin/service-package/services') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Plan Holder:</strong> <?= esc(($service['first_name'] ?? '') . ' ' . ($service['last_name'] ?? '')) ?> (<?= esc($service['unique_identifier'] ?? '-') ?>)</div>
                <div class="col-md-6"><strong>Contact:</strong> <?= esc($service['contact_number'] ?? '-') ?></div>
                <div class="col-md-4"><strong>Service Type:</strong> <?= esc($service['service_type'] ?? '-') ?></div>
                <div class="col-md-4"><strong>Schedule:</strong> <?= esc($service['service_date'] ?? '-') ?> <?= esc($service['service_time'] ?? '') ?></div>
                <div class="col-md-4"><strong>Status:</strong> <?= esc(ucfirst((string) ($service['status'] ?? '-'))) ?></div>
                <div class="col-md-6"><strong>Burial Location:</strong> <?= esc($service['burial_location'] ?? '-') ?></div>
                <div class="col-md-6"><strong>Assigned Staff:</strong> <?= esc(trim((string) (($service['staff_first_name'] ?? '') . ' ' . ($service['staff_last_name'] ?? '')))) ?: '-' ?></div>
                <div class="col-12"><strong>Notes:</strong> <?= esc($service['notes'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Costs</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Description</th><th>Amount</th></tr></thead>
                            <tbody>
                                <?php if (empty($service['costs'])): ?>
                                    <tr><td colspan="2" class="text-center py-3">No costs found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($service['costs'] as $cost): ?>
                                        <tr>
                                            <td><?= esc($cost['description'] ?? '-') ?></td>
                                            <td><?= number_format((float) ($cost['amount'] ?? 0), 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Add-ons</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Item</th><th>Price</th></tr></thead>
                            <tbody>
                                <?php if (empty($service['add_ons'])): ?>
                                    <tr><td colspan="2" class="text-center py-3">No add-ons found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($service['add_ons'] as $addOn): ?>
                                        <tr>
                                            <td><?= esc($addOn['item_name'] ?? '-') ?></td>
                                            <td><?= number_format((float) ($addOn['price'] ?? 0), 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
