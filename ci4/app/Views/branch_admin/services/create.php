<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Create Service</h1>
            <small class="text-muted">Define a service offering for this branch</small>
        </div>
        <a href="<?= base_url('branch-admin/service-package/services') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= base_url('branch-admin/services/store') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="service_name" class="form-label">Service Name</label>
                        <input
                            type="text"
                            id="service_name"
                            name="service_name"
                            class="form-control"
                            value="<?= esc(old('service_name')) ?>"
                            required
                        >
                    </div>
                    <div class="col-md-6">
                        <label for="base_price" class="form-label">Base Price</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="base_price"
                            name="base_price"
                            class="form-control"
                            value="<?= esc(old('base_price')) ?>"
                            required
                        >
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="4"
                        ><?= esc(old('description')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= base_url('branch-admin/service-package/services') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>