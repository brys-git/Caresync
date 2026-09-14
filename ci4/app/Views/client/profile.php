<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $editMode = $edit_mode ?? false; ?>

<?php if (! $editMode): ?>
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="<?= base_url('client/profile') ?>?edit=1" class="btn btn-primary btn-sm">
            <i class="ti ti-pencil me-1"></i> Edit Profile
        </a>
        <a href="#password-section" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-key me-1"></i> Change Password
        </a>
    </div>
<?php endif; ?>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<div class="row g-3 mb-3">
    <!-- Profile Card (Left) -->
    <div class="col-lg-4">
        <section class="cs-panel h-100">
            <div class="cs-panel__body text-center">
                <div class="cs-avatar cs-avatar--lg mx-auto mb-3" style="width:80px;height:80px;flex-basis:80px;font-size:2rem;">
                    <?= esc(cs_initials(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?>
                </div>

                <h5 class="mb-1">
                    <?= esc(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: 'User' ?>
                </h5>
                <p class="text-muted small mb-3">Plan Holder</p>

                <div class="mb-3">
                    <span class="badge bg-primary">Plan Holder</span>
                </div>

                <hr>
                <div class="text-start">
                    <div class="mb-2">
                        <small class="text-muted d-block">Member Since</small>
                        <strong class="small"><?= cs_date($plan_holder['created_at'] ?? null) ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Account Status</small>
                        <?= cs_status('active') ?>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Account Information (Right) -->
    <div class="col-lg-8">
        <section class="cs-panel h-100">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Account Information</h2></div>
            <div class="cs-panel__body">
                <?php if ($editMode): ?>
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

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                <?php else: ?>
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
        </section>
    </div>
</div>

<section class="cs-panel" id="password-section">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Login Credentials</h2></div>
    <div class="cs-panel__body">
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

        <?php if (! $editMode): ?>
            <div class="mt-3">
                <a href="<?= base_url('client/profile/change-password') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="ti ti-key me-1"></i> Change Password
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
