<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Service</h4>
        <a href="<?= site_url('/branch-admin/services/view/' . (int) $service['service_id']) ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= site_url('/branch-admin/services/update/' . (int) $service['service_id']) ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Package</label>
                        <select name="package_id" class="form-select" required>
                            <?php foreach (($packages ?? []) as $package): ?>
                                <option value="<?= (int) $package['package_id'] ?>" <?= (int) old('package_id', $service['package_id']) === (int) $package['package_id'] ? 'selected' : '' ?>>
                                    <?= esc($package['package_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service Type</label>
                        <input type="text" name="service_type" class="form-control" value="<?= esc(old('service_type', $service['service_type'])) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Service Date</label>
                        <input type="date" name="service_date" class="form-control" value="<?= esc(old('service_date', $service['service_date'])) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Service Time</label>
                        <input type="time" name="service_time" class="form-control" value="<?= esc(old('service_time', $service['service_time'])) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Burial Location</label>
                        <input type="text" name="burial_location" class="form-control" value="<?= esc(old('burial_location', $service['burial_location'])) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= esc(old('notes', $service['notes'])) ?></textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
