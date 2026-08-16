<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h4 class="mb-3">My Profile</h4>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Profile Details</div>
                <div class="card-body">
                    <form method="post" action="<?= site_url('/staff/profile/update') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="<?= esc($user['first_name'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" value="<?= esc($user['middle_name'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="<?= esc($user['last_name'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= esc($user['username'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" value="<?= esc(old('contact_number', $user['contact_number'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GCash Number (for client payments)</label>
                                <input type="text" name="gcash_number" class="form-control" value="<?= esc(old('gcash_number', $user['gcash_number'] ?? '')) ?>" placeholder="09XX XXX XXXX">
                                <small class="text-muted">Assigned clients will send their initial payment to this GCash account.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GCash Account Name</label>
                                <input type="text" name="gcash_name" class="form-control" value="<?= esc(old('gcash_name', $user['gcash_name'] ?? '')) ?>" placeholder="Name shown on the GCash account">
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Security</div>
                <div class="card-body">
                    <p class="text-muted small">Use this to change your password regularly for account safety.</p>
                    <a href="<?= site_url('/change-password') ?>" class="btn btn-outline-secondary w-100">Change Password</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
