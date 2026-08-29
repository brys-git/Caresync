<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0">My Profile</h4>
            <p class="text-muted mb-0 small">Login credentials and personal information</p>
        </div>
        <?php if (! ($edit_mode ?? false)): ?>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= base_url('client/profile/edit') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                </a>
                <a href="#password-section" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-key me-1"></i> Change Password
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i> <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Profile Section: Left Card + Right Account Info -->
    <div class="row g-3 mb-4">
        <!-- Profile Card (Left) -->
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <!-- Profile Picture -->
                    <div class="mb-3">
                        <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;">
                            <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1)) ?>
                        </div>
                    </div>

                    <!-- User Info -->
                    <h5 class="card-title mb-1">
                        <?= esc(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: 'User' ?>
                    </h5>
                    <p class="text-muted small mb-3">Plan Holder</p>

                    <!-- Role Badge -->
                    <div class="mb-3">
                        <span class="badge bg-primary">Plan Holder</span>
                    </div>

                    <!-- Metadata -->
                    <hr>
                    <div class="text-start">
                        <div class="mb-2">
                            <small class="text-muted d-block">Member Since</small>
                            <strong class="small">
                                <?= esc(! empty($plan_holder['created_at']) ? date('M d, Y', strtotime((string) $plan_holder['created_at'])) : 'N/A') ?>
                            </strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Account Status</small>
                            <span class="badge bg-success small">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information (Right) -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0">Account Information</h6>
                </div>
                <div class="card-body">
                    <?php if ($edit_mode ?? false): ?>
                        <!-- Edit Mode -->
                        <form method="post" action="<?= base_url('client/profile/update') ?>">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= esc(old('first_name', (string) ($user['first_name'] ?? ''))) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= esc(old('last_name', (string) ($user['last_name'] ?? ''))) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" value="<?= esc(old('email', (string) ($user['email'] ?? ''))) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" value="<?= esc(old('contact_number', (string) ($user['contact_number'] ?? ''))) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay</label>
                                    <input type="text" name="address_barangay" class="form-control" value="<?= esc(old('address_barangay', (string) ($plan_holder['address_barangay'] ?? ''))) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="address_city" class="form-control" value="<?= esc(old('address_city', (string) ($plan_holder['address_city'] ?? ''))) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Civil Status</label>
                                    <input type="text" name="civil_status" class="form-control" value="<?= esc(old('civil_status', (string) ($plan_holder['civil_status'] ?? ''))) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Citizenship</label>
                                    <input type="text" name="citizenship" class="form-control" value="<?= esc(old('citizenship', (string) ($plan_holder['citizenship'] ?? ''))) ?>">
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- View Mode -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">First Name</label>
                                <div class="fw-semibold"><?= esc($user['first_name'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Last Name</label>
                                <div class="fw-semibold"><?= esc($user['last_name'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Username</label>
                                <div class="fw-semibold"><?= esc($user['username'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Email Address</label>
                                <div class="fw-semibold"><?= esc($user['email'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Contact Number</label>
                                <div class="fw-semibold"><?= esc($user['contact_number'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">City</label>
                                <div class="fw-semibold"><?= esc($plan_holder['address_city'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Barangay</label>
                                <div class="fw-semibold"><?= esc($plan_holder['address_barangay'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted mb-1">Civil Status</label>
                                <div class="fw-semibold"><?= esc($plan_holder['civil_status'] ?? '-') ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Section -->
    <div class="card shadow-sm" id="password-section">
        <div class="card-header bg-light border-bottom">
            <h6 class="mb-0">Login Credentials</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted mb-1">Username</label>
                    <div class="fw-semibold"><?= esc($user['username'] ?? '-') ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted mb-1">Password</label>
                    <div class="fw-semibold">••••••••••••</div>
                </div>
            </div>

            <!-- Change Password Button -->
            <?php if (! ($edit_mode ?? false)): ?>
                <div class="mt-3">
                    <a href="<?= base_url('client/profile/change-password') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-key me-1"></i> Change Password
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card {
        border: none;
        border-radius: 0.5rem;
    }

    .card-header {
        border-radius: 0.5rem 0.5rem 0 0;
    }
</style>

<?= $this->endSection() ?>
