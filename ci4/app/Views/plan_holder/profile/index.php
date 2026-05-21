<?= $this->extend($role_layout ?? 'layouts/plan_holder') ?>

<?= $this->section('content') ?>
<?php
$fullName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['middle_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? '') . ' ' . (string) ($profile['name_extension'] ?? ''));
$address = trim((string) ($profile['address_street'] ?? '') . ', ' . (string) ($profile['address_barangay'] ?? '') . ', ' . (string) ($profile['address_city'] ?? ''));
?>

<style>
    .client-profile-shell {
        background:
            radial-gradient(circle at 10% 0%, rgba(15, 118, 110, 0.12), transparent 36%),
            radial-gradient(circle at 90% 30%, rgba(37, 99, 235, 0.12), transparent 40%);
        border-radius: 26px;
        padding: 1rem;
    }

    .profile-hero {
        border-radius: 26px;
        padding: 1.6rem;
        color: #fff;
        background:
            radial-gradient(circle at 75% 30%, rgba(255, 255, 255, 0.18), transparent 40%),
            linear-gradient(135deg, #0f766e, #2563eb);
        box-shadow: 0 24px 52px rgba(15, 23, 42, 0.16);
    }

    .profile-section-card {
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .profile-section-head {
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        border-bottom: 1px solid rgba(226, 232, 240, 0.95);
        padding: 1rem 1.25rem;
    }

    .info-grid {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }

    .info-tile {
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 16px;
        padding: 0.85rem 0.95rem;
        background: #fff;
    }

    .info-label {
        display: block;
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .info-value {
        color: #0f172a;
        font-weight: 700;
        word-break: break-word;
    }

    .profile-avatar {
        width: 6rem;
        height: 6rem;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.35);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }
</style>

<div class="container-fluid client-profile-shell">
    <div class="profile-hero mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar">
                    <i class="ti ti-user"></i>
                </div>
                <div>
                    <div class="text-white-50 small text-uppercase fw-bold mb-1">My Profile</div>
                    <h1 class="h2 fw-bold mb-1"><?= esc($fullName !== '' ? $fullName : 'Plan Holder') ?></h1>
                    <div class="text-white-75">Member ID: <?= esc((string) ($profile['unique_identifier'] ?? 'Not available')) ?></div>
                </div>
            </div>
            <div class="text-lg-end">
                <div class="small text-white-50 text-uppercase fw-bold mb-1">Profile picture</div>
                <div class="text-white-75">Reserved for future upload feature</div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12">
            <section class="profile-section-card">
                <div class="profile-section-head d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h5 mb-1">Account Credentials</h2>
                        <p class="text-secondary mb-0">Account-level details from the users table.</p>
                    </div>
                    <a class="btn btn-outline-primary" href="<?= base_url('client/profile/edit') ?>">
                        <i class="ti ti-edit me-1"></i>Edit Account
                    </a>
                </div>
                <div class="p-3 p-lg-4">
                    <div class="info-grid">
                        <div class="info-tile"><span class="info-label">Username</span><div class="info-value"><?= esc((string) ($profile['username'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Email</span><div class="info-value"><?= esc((string) ($profile['email'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Contact Number</span><div class="info-value"><?= esc((string) ($profile['contact_number'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Account Status</span><div class="info-value text-capitalize"><?= esc((string) ($profile['account_status'] ?? '-')) ?></div></div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="profile-section-card">
                <div class="profile-section-head d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h5 mb-1">Personal Information</h2>
                        <p class="text-secondary mb-0">Personal profile details from the plan_holders table.</p>
                    </div>
                    <a class="btn btn-outline-primary" href="<?= base_url('client/profile/edit') ?>#profile-edit-form">
                        <i class="ti ti-user-edit me-1"></i>Edit Profile
                    </a>
                </div>
                <div class="p-3 p-lg-4">
                    <div class="info-grid">
                        <div class="info-tile"><span class="info-label">Full Name</span><div class="info-value"><?= esc($fullName !== '' ? $fullName : '-') ?></div></div>
                        <div class="info-tile"><span class="info-label">Address</span><div class="info-value"><?= esc($address !== ', ,' ? $address : '-') ?></div></div>
                        <div class="info-tile"><span class="info-label">Date of Birth</span><div class="info-value"><?= esc((string) ($profile['date_of_birth'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Place of Birth</span><div class="info-value"><?= esc((string) ($profile['place_of_birth'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Age</span><div class="info-value"><?= esc((string) ($profile['age'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Gender</span><div class="info-value text-capitalize"><?= esc((string) ($profile['gender'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Civil Status</span><div class="info-value"><?= esc((string) ($profile['civil_status'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Citizenship</span><div class="info-value"><?= esc((string) ($profile['citizenship'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Height / Weight</span><div class="info-value"><?= esc((string) ($profile['height'] ?? '-')) ?> / <?= esc((string) ($profile['weight'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Spouse Info</span><div class="info-value"><?= esc((string) ($profile['spouse_name'] ?? '-')) ?>, <?= esc((string) ($profile['spouse_occupation'] ?? '-')) ?></div></div>
                        <div class="info-tile"><span class="info-label">Organization Affiliation</span><div class="info-value"><?= esc((string) ($profile['organization_affiliation'] ?? '-')) ?></div></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <?php if (! empty($edit_mode)): ?>
        <div class="profile-section-card mt-4" id="profile-edit-form">
            <div class="profile-section-head">
                <h3 class="h5 mb-1">Edit Account and Profile</h3>
                <p class="text-secondary mb-0">Only allowed fields are editable. Username and system-generated values are read-only.</p>
            </div>

            <form method="post" action="<?= base_url('client/profile/update') ?>" class="p-3 p-lg-4">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username (Read-only)</label>
                        <input type="text" id="username" class="form-control" value="<?= esc((string) ($profile['username'] ?? '')) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="account_status">Account Status (Read-only)</label>
                        <input type="text" id="account_status" class="form-control" value="<?= esc((string) ($profile['account_status'] ?? '')) ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= esc(old('email', (string) ($profile['email'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" class="form-control" value="<?= esc(old('contact_number', (string) ($profile['contact_number'] ?? ''))) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="address_street">Street</label>
                        <input type="text" id="address_street" name="address_street" class="form-control" value="<?= esc(old('address_street', (string) ($profile['address_street'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_barangay">Barangay</label>
                        <input type="text" id="address_barangay" name="address_barangay" class="form-control" value="<?= esc(old('address_barangay', (string) ($profile['address_barangay'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_city">City</label>
                        <input type="text" id="address_city" name="address_city" class="form-control" value="<?= esc(old('address_city', (string) ($profile['address_city'] ?? ''))) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="civil_status">Civil Status</label>
                        <input type="text" id="civil_status" name="civil_status" class="form-control" value="<?= esc(old('civil_status', (string) ($profile['civil_status'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="citizenship">Citizenship</label>
                        <input type="text" id="citizenship" name="citizenship" class="form-control" value="<?= esc(old('citizenship', (string) ($profile['citizenship'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="height">Height</label>
                        <input type="text" id="height" name="height" class="form-control" value="<?= esc(old('height', (string) ($profile['height'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="weight">Weight</label>
                        <input type="text" id="weight" name="weight" class="form-control" value="<?= esc(old('weight', (string) ($profile['weight'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="spouse_name">Spouse Name</label>
                        <input type="text" id="spouse_name" name="spouse_name" class="form-control" value="<?= esc(old('spouse_name', (string) ($profile['spouse_name'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="spouse_birthdate">Spouse Birthdate</label>
                        <input type="date" id="spouse_birthdate" name="spouse_birthdate" class="form-control" value="<?= esc(old('spouse_birthdate', (string) ($profile['spouse_birthdate'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="spouse_occupation">Spouse Occupation</label>
                        <input type="text" id="spouse_occupation" name="spouse_occupation" class="form-control" value="<?= esc(old('spouse_occupation', (string) ($profile['spouse_occupation'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="organization_affiliation">Organization Affiliation</label>
                        <input type="text" id="organization_affiliation" name="organization_affiliation" class="form-control" value="<?= esc(old('organization_affiliation', (string) ($profile['organization_affiliation'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="new_password">New Password (Optional)</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" autocomplete="new-password">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
