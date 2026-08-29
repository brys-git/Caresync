<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Staff</h4>
        <a href="<?= site_url('/branch-admin/staff-management') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= site_url('/branch-admin/staff-management/update/' . (int) $staff['user_id']) ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" value="<?= esc($staff['first_name'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" value="<?= esc($staff['last_name'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= esc(old('email', $staff['email'] ?? '')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" value="<?= esc(old('contact_number', $staff['contact_number'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?= old('status', $staff['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status', $staff['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
