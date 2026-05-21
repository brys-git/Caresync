<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
                        <div>
                            <h1 class="h3 mb-2">Register for Damayan Burial Program Plan</h1>
                            <p class="text-muted mb-0">Complete this form to enroll in the Damayan Burial Program plan. This page is for plan registration only.</p>
                        </div>
                        <span class="badge text-bg-warning px-3 py-2">Action Required</span>
                    </div>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                    <?php endif; ?>

                    <?php if (! empty($already_registered)): ?>
                        <div class="alert alert-success">
                            You are already registered in the Damayan Burial Program plan.
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($already_profiled_without_plan)): ?>
                        <div class="alert alert-warning">
                            Your account is already linked to a plan holder profile, but no Damayan Burial Program plan is assigned yet.
                            <br>Please contact your branch admin to assign your Damayan plan.
                        </div>
                    <?php endif; ?>

                    <?php $pendingStatus = (string) ($pending_registration['status'] ?? ''); ?>
                    <?php if ($pendingStatus === 'pending'): ?>
                        <div class="alert alert-info">
                            Your Damayan Burial Program plan registration was submitted on <?= esc((string) ($pending_registration['created_at'] ?? '-')) ?> and is currently pending approval.
                        </div>
                    <?php elseif ($pendingStatus === 'rejected'): ?>
                        <div class="alert alert-warning">
                            Your last Damayan Burial Program plan registration was rejected.
                            <?php if (! empty($pending_registration['rejection_notes'])): ?>
                                <br>Reason: <?= esc((string) $pending_registration['rejection_notes']) ?>
                            <?php endif; ?>
                            <br>Please update your details below and submit again.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= base_url('plan-holder-registration') ?>">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="branch_id">Preferred Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <option value="">Select branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= esc((string) $branch['branch_id']) ?>" <?= old('branch_id', (string) ($user['branch_id'] ?? '')) === (string) $branch['branch_id'] ? 'selected' : '' ?>>
                                            <?= esc((string) $branch['branch_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="first_name">First Name</label>
                                <input type="text" id="first_name" class="form-control" value="<?= esc((string) ($user['first_name'] ?? '')) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text" id="last_name" class="form-control" value="<?= esc((string) ($user['last_name'] ?? '')) ?>" readonly>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="address_no">Address No</label>
                                <input type="text" id="address_no" name="address_no" class="form-control" value="<?= old('address_no') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="address_street">Street</label>
                                <input type="text" id="address_street" name="address_street" class="form-control" value="<?= old('address_street') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="address_barangay">Barangay</label>
                                <input type="text" id="address_barangay" name="address_barangay" class="form-control" value="<?= old('address_barangay') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address_city">City</label>
                                <input type="text" id="address_city" name="address_city" class="form-control" value="<?= old('address_city') ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= old('date_of_birth') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="age">Age</label>
                                <input type="number" min="0" id="age" name="age" class="form-control" value="<?= old('age') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="place_of_birth">Place of Birth</label>
                                <input type="text" id="place_of_birth" name="place_of_birth" class="form-control" value="<?= old('place_of_birth') ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="gender">Gender</label>
                                <input type="text" id="gender" name="gender" class="form-control" value="<?= old('gender') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="civil_status">Civil Status</label>
                                <input type="text" id="civil_status" name="civil_status" class="form-control" value="<?= old('civil_status') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="citizenship">Citizenship</label>
                                <input type="text" id="citizenship" name="citizenship" class="form-control" value="<?= old('citizenship') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="senior_citizen_id">Senior Citizen ID</label>
                                <input type="text" id="senior_citizen_id" name="senior_citizen_id" class="form-control" value="<?= old('senior_citizen_id') ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="height">Height</label>
                                <input type="text" id="height" name="height" class="form-control" value="<?= old('height') ?>" placeholder="cm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="weight">Weight</label>
                                <input type="text" id="weight" name="weight" class="form-control" value="<?= old('weight') ?>" placeholder="kg">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="spouse_name">Spouse Name</label>
                                <input type="text" id="spouse_name" name="spouse_name" class="form-control" value="<?= old('spouse_name') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="spouse_birthdate">Spouse Birthdate</label>
                                <input type="date" id="spouse_birthdate" name="spouse_birthdate" class="form-control" value="<?= old('spouse_birthdate') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="spouse_occupation">Spouse Occupation</label>
                                <input type="text" id="spouse_occupation" name="spouse_occupation" class="form-control" value="<?= old('spouse_occupation') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="organization_affiliation">Organization Affiliation</label>
                                <input type="text" id="organization_affiliation" name="organization_affiliation" class="form-control" value="<?= old('organization_affiliation') ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4" <?= ($pendingStatus === 'pending' || ! empty($already_registered) || ! empty($already_profiled_without_plan)) ? 'disabled' : '' ?>>
                                <?= $pendingStatus === 'pending' ? 'Awaiting Approval' : (! empty($already_registered) ? 'Already Registered' : (! empty($already_profiled_without_plan) ? 'Profile Linked - Waiting for Plan Assignment' : 'Submit Damayan Plan Registration')) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
