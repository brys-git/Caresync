<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Register Plan Holder</h1>
            <small class="text-muted">Add a new plan holder to your branch</small>
        </div>
        <div>
            <a href="<?= base_url('staff/client') ?>" class="btn btn-outline-secondary btn-sm">Back to List</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= base_url('staff/client/store') ?>" novalidate>
                <?= csrf_field() ?>

                <!-- Personal Information Section -->
                <div class="mb-4">
                    <h5 class="h6 mb-3 text-primary">Personal Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control <?= isset($validation) && $validation->hasError('first_name') ? 'is-invalid' : '' ?>" 
                                id="first_name" 
                                name="first_name" 
                                value="<?= old('first_name') ?>" 
                                required
                            >
                            <?php if (isset($validation) && $validation->hasError('first_name')): ?>
                                <div class="invalid-feedback"><?= $validation->getError('first_name') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="middle_name" 
                                name="middle_name" 
                                value="<?= old('middle_name') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control <?= isset($validation) && $validation->hasError('last_name') ? 'is-invalid' : '' ?>" 
                                id="last_name" 
                                name="last_name" 
                                value="<?= old('last_name') ?>" 
                                required
                            >
                            <?php if (isset($validation) && $validation->hasError('last_name')): ?>
                                <div class="invalid-feedback"><?= $validation->getError('last_name') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="contact_number" 
                                name="contact_number" 
                                value="<?= old('contact_number') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input 
                                type="email" 
                                class="form-control <?= isset($validation) && $validation->hasError('email') ? 'is-invalid' : '' ?>" 
                                id="email" 
                                name="email" 
                                value="<?= old('email') ?>" 
                                required
                            >
                            <?php if (isset($validation) && $validation->hasError('email')): ?>
                                <div class="invalid-feedback"><?= $validation->getError('email') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Address Section -->
                <div class="mb-4">
                    <h5 class="h6 mb-3 text-primary">Address</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="address_no" class="form-label">House/Block No.</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="address_no" 
                                name="address_no" 
                                value="<?= old('address_no') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="address_street" class="form-label">Street</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="address_street" 
                                name="address_street" 
                                value="<?= old('address_street') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="address_barangay" class="form-label">Barangay</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="address_barangay" 
                                name="address_barangay" 
                                value="<?= old('address_barangay') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="address_city" class="form-label">City/Municipality</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="address_city" 
                                name="address_city" 
                                value="<?= old('address_city') ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- Personal Details Section -->
                <div class="mb-4">
                    <h5 class="h6 mb-3 text-primary">Personal Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="date_of_birth" class="form-label">Birthdate</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="date_of_birth" 
                                name="date_of_birth" 
                                value="<?= old('date_of_birth') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="place_of_birth" class="form-label">Place of Birth</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="place_of_birth" 
                                name="place_of_birth" 
                                value="<?= old('place_of_birth') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="age" class="form-label">Age</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="age" 
                                name="age" 
                                value="<?= old('age') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">-- Select Gender --</option>
                                <option value="Male" <?= old('gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= old('gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= old('gender') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="civil_status" class="form-label">Civil Status</label>
                            <select class="form-select" id="civil_status" name="civil_status">
                                <option value="">-- Select Civil Status --</option>
                                <option value="Single" <?= old('civil_status') === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= old('civil_status') === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Divorced" <?= old('civil_status') === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                <option value="Widowed" <?= old('civil_status') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="citizenship" class="form-label">Citizenship</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="citizenship" 
                                name="citizenship" 
                                value="<?= old('citizenship') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="height" class="form-label">Height (cm)</label>
                            <input 
                                type="number" 
                                step="0.01"
                                class="form-control" 
                                id="height" 
                                name="height" 
                                value="<?= old('height') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input 
                                type="number" 
                                step="0.01"
                                class="form-control" 
                                id="weight" 
                                name="weight" 
                                value="<?= old('weight') ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- Spouse Information Section -->
                <div class="mb-4">
                    <h5 class="h6 mb-3 text-primary">Spouse Information (Optional)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="spouse_name" class="form-label">Spouse Name</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="spouse_name" 
                                name="spouse_name" 
                                value="<?= old('spouse_name') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="spouse_birthdate" class="form-label">Spouse Birthdate</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="spouse_birthdate" 
                                name="spouse_birthdate" 
                                value="<?= old('spouse_birthdate') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="spouse_occupation" class="form-label">Spouse Occupation</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="spouse_occupation" 
                                name="spouse_occupation" 
                                value="<?= old('spouse_occupation') ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="mb-4">
                    <h5 class="h6 mb-3 text-primary">Additional Information (Optional)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="senior_citizen_id" class="form-label">Senior Citizen ID</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="senior_citizen_id" 
                                name="senior_citizen_id" 
                                value="<?= old('senior_citizen_id') ?>"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="organization_affiliation" class="form-label">Organization Affiliation</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="organization_affiliation" 
                                name="organization_affiliation" 
                                value="<?= old('organization_affiliation') ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- Membership Program Section -->
                <div class="mb-4">
                    <h5 class="h6 mb-3 text-primary">Membership Program</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Program</label>
                            <div class="form-control-plaintext fw-semibold">
                                <?= esc((string) (($program['name'] ?? '') ?: 'Damayan Burial Program')) ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monthly Fee</label>
                            <div class="form-control-plaintext fw-semibold">
                                P<?= number_format((float) ($program['monthly_fee'] ?? 240), 2) ?>
                            </div>
                            <small class="text-muted">Standard Damayan membership rate</small>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= base_url('staff/client') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Register Plan Holder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
