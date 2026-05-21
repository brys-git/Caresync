<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Package Details</h4>
        <div>
            <a href="<?= site_url('/branch-admin/packages/edit/' . (int) $package['package_id']) ?>" class="btn btn-primary btn-sm">Edit</a>
            <a href="<?= site_url('/branch-admin/service-package/packages') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Package Name:</strong> <?= esc($package['package_name']) ?></div>
                <div class="col-md-3"><strong>Base Price:</strong> <?= number_format((float) ($package['base_price'] ?? 0), 2) ?></div>
                <div class="col-md-3"><strong>Customizable:</strong> <?= ((int) ($package['is_customizable'] ?? 0) === 1) ? 'Yes' : 'No' ?></div>
                <div class="col-12"><strong>Description:</strong> <?= esc($package['description'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Services Included</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Service Name</th><th>Description</th></tr></thead>
                            <tbody>
                                <?php if (empty($package['items'])): ?>
                                    <tr><td colspan="2" class="text-center py-3">No services found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($package['items'] as $item): ?>
                                        <tr>
                                            <td><?= esc($item['item_name'] ?? '-') ?></td>
                                            <td><?= esc($item['description'] ?? '-') ?></td>
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
                <div class="card-header">Price Versions</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Price</th><th>Effective Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (empty($package['versions'])): ?>
                                    <tr><td colspan="3" class="text-center py-3">No versions found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($package['versions'] as $version): ?>
                                        <tr>
                                            <td><?= number_format((float) ($version['price'] ?? 0), 2) ?></td>
                                            <td><?= esc($version['effective_date'] ?? '-') ?></td>
                                            <td><?= esc(ucfirst((string) ($version['status'] ?? '-'))) ?></td>
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

    <div class="card mt-3">
        <div class="card-header">Add Package Service</div>
        <div class="card-body">
            <form method="post" action="<?= site_url('/branch-admin/packages/add-item/' . (int) $package['package_id']) ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Service Name</label>
                        <select name="service_list_id" class="form-select service-select" required>
                            <option value="">Select service</option>
                            <?php foreach (($service_list ?? []) as $service): ?>
                                <option value="<?= (int) $service['service_list_id'] ?>" data-description="<?= esc($service['description'] ?? '') ?>">
                                    <?= esc($service['service_name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control service-description" readonly>
                    </div>
                    <div class="col-md-2 d-grid align-items-end">
                        <button type="submit" class="btn btn-primary">Add Service</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const serviceSelect = document.querySelector('.service-select');
    const descriptionInput = document.querySelector('.service-description');

    if (!serviceSelect || !descriptionInput) {
        return;
    }

    serviceSelect.addEventListener('change', function () {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        descriptionInput.value = selectedOption ? (selectedOption.dataset.description || '') : '';
    });
})();
</script>
<?= $this->endSection() ?>
