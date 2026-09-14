<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$beneficiaries = $beneficiaries ?? [];
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$planId = (int) ($plan_id ?? 0);
$applicationDate = (string) old('application_date', (string) ($plan_holder['application_date'] ?? date('Y-m-d')));
$civilStatus = (string) old('civil_status', (string) ($plan_holder['civil_status'] ?? ''));
$gender = (string) old('gender', (string) ($plan_holder['gender'] ?? ''));
$idTypes = $id_types ?? [];
$latestVerification = $latest_verification ?? null;
?>
<div style="max-width: 1000px;">
    <?php // Plain error/success flash already handled by layouts/_shell.php's toast. The "errors" list below is a validation-array flash, a different shape the shell's toast loop doesn't cover, so it stays. ?>
    <?php $errors = session()->getFlashdata('errors') ?? []; ?>
    <?php if (! empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $message): ?>
                    <li><?= esc((string) $message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Progress indicator -->
    <ol class="wizard-progress mb-4" id="wizardProgress">
        <li class="wizard-progress__item is-active" data-progress-step="1"><span class="wizard-progress__badge">1</span><span class="wizard-progress__label">Applicant Info</span></li>
        <li class="wizard-progress__item" data-progress-step="2"><span class="wizard-progress__badge">2</span><span class="wizard-progress__label">Government ID</span></li>
        <li class="wizard-progress__item" data-progress-step="3"><span class="wizard-progress__badge">3</span><span class="wizard-progress__label">Beneficiaries</span></li>
        <li class="wizard-progress__item" data-progress-step="4"><span class="wizard-progress__badge">4</span><span class="wizard-progress__label">Emergency Contact</span></li>
        <li class="wizard-progress__item" data-progress-step="5"><span class="wizard-progress__badge">5</span><span class="wizard-progress__label">Review</span></li>
    </ol>

    <form method="post" action="<?= base_url('plan-registration') ?>" enctype="multipart/form-data" id="registrationForm">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= $planId ?>">
        <input type="hidden" name="package_id" value="<?= $planId ?>">
        <input type="hidden" name="government_id_verification_id" id="government_id_verification_id" value="<?= esc((string) ($latestVerification['verification_id'] ?? '')) ?>">

        <!-- ============ STEP 1: Applicant Information ============ -->
        <div class="wizard-panel" data-step-panel="1">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-2">KAAGAPAY MO KARAMAY FUNERAL HOMES CO.</h2>
                    <p class="mb-1"><strong>Main Office Address:</strong> #65 J.F. Diaz Ave. Ampid 1, San Mateo, Rizal</p>
                    <p class="mb-1"><strong>Branch Offices:</strong> Dakila Cor. Constitutional Rd. Batasan Hills Q.C.; Sta Isabel, Calapan City; Babangonan, Victoria; Poblacion, Bansud; C-5 Diversion Rd. Bongabong; Upper Odiong, Roxas; Don Pedro, Mansalay Oriental Mindoro</p>
                    <p class="mb-1"><strong>Contact Numbers:</strong> Smart 0962-571-9780; Globe 0997-512-7828 / 0927-735-0239</p>
                    <p class="mb-1"><strong>Website &amp; Facebook Page:</strong> KaagapayMoKaramayFuneralHomes</p>
                    <p class="mb-1"><strong>Registration Details:</strong> SEC. REG. PG 201520567, TIN # 009-196-436-0000</p>
                    <p class="mb-0"><strong>Founder/CEO:</strong> Ricardo C. Ramilo</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Instructions</h2>
                    <ul class="mb-0">
                        <li>Fill out correctly and neatly.</li>
                        <li>Use capital letters for clarity (e.g., JUAN DELA CRUZ).</li>
                        <li>Do not use stapler; use glue, tape, or paste for attaching the photo.</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-primary mb-3">Applicant Information</h3>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" for="id_control_no">ID Control No.</label>
                            <input id="id_control_no" name="id_control_no" class="form-control" value="<?= esc(old('id_control_no', (string) ($plan_holder['id_control_no'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="coordinator">Coordinator</label>
                            <input id="coordinator" name="coordinator" class="form-control" value="<?= esc(old('coordinator', (string) ($plan_holder['coordinator'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="application_date">Date of Application</label>
                            <input id="application_date" name="application_date" type="date" class="form-control" value="<?= esc($applicationDate) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input id="last_name" name="last_name" class="form-control" value="<?= esc(old('last_name', (string) ($user['last_name'] ?? ''))) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="first_name">Given Name</label>
                            <input id="first_name" name="first_name" class="form-control" value="<?= esc(old('first_name', (string) ($user['first_name'] ?? ''))) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="middle_name">Middle Name</label>
                            <input id="middle_name" name="middle_name" class="form-control" value="<?= esc(old('middle_name', (string) ($user['middle_name'] ?? ''))) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="address_no">Address No.</label>
                            <input id="address_no" name="address_no" class="form-control" value="<?= esc(old('address_no', (string) ($plan_holder['address_no'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="address_street">Street</label>
                            <input id="address_street" name="address_street" class="form-control" value="<?= esc(old('address_street', (string) ($plan_holder['address_street'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="city_search">Town/City</label>
                            <div class="psgc-field position-relative">
                                <input
                                    id="city_search"
                                    type="text"
                                    class="form-control"
                                    autocomplete="off"
                                    placeholder="Search town/city..."
                                    value="<?= esc(old('address_city', (string) ($plan_holder['address_city'] ?? ''))) ?>"
                                >
                                <input type="hidden" id="address_city" name="address_city" value="<?= esc(old('address_city', (string) ($plan_holder['address_city'] ?? ''))) ?>">
                                <input type="hidden" id="city_municipality_code" name="city_municipality_code" value="<?= esc(old('city_municipality_code', (string) ($plan_holder['city_municipality_code'] ?? ''))) ?>">
                                <div class="psgc-dropdown list-group shadow-sm d-none"></div>
                            </div>
                            <small class="text-danger d-none psgc-error" data-field="city"></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="barangay_search">Barangay</label>
                            <div class="psgc-field position-relative">
                                <input
                                    id="barangay_search"
                                    type="text"
                                    class="form-control"
                                    autocomplete="off"
                                    placeholder="Select a Town/City first"
                                    value="<?= esc(old('address_barangay', (string) ($plan_holder['address_barangay'] ?? ''))) ?>"
                                    disabled
                                >
                                <input type="hidden" id="address_barangay" name="address_barangay" value="<?= esc(old('address_barangay', (string) ($plan_holder['address_barangay'] ?? ''))) ?>">
                                <input type="hidden" id="barangay_code" name="barangay_code" value="<?= esc(old('barangay_code', (string) ($plan_holder['barangay_code'] ?? ''))) ?>">
                                <div class="psgc-dropdown list-group shadow-sm d-none"></div>
                            </div>
                            <small class="text-danger d-none psgc-error" data-field="barangay"></small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="date_of_birth">Date of Birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="<?= esc(old('date_of_birth', (string) ($plan_holder['date_of_birth'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="place_of_birth">Place of Birth</label>
                            <input id="place_of_birth" name="place_of_birth" class="form-control" value="<?= esc(old('place_of_birth', (string) ($plan_holder['place_of_birth'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="age">Age</label>
                            <input id="age" name="age" type="number" class="form-control" value="<?= esc(old('age', (string) ($plan_holder['age'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="civil_status">Civil Status</label>
                            <select id="civil_status" name="civil_status" class="form-select">
                                <option value="">Select</option>
                                <option value="Single" <?= $civilStatus === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= $civilStatus === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Divorced" <?= $civilStatus === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                <option value="Widowed" <?= $civilStatus === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="citizenship">Citizenship</label>
                            <input id="citizenship" name="citizenship" class="form-control" value="<?= esc(old('citizenship', (string) ($plan_holder['citizenship'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="height">Height</label>
                            <input id="height" name="height" type="number" step="0.01" class="form-control" value="<?= esc(old('height', (string) ($plan_holder['height'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="weight">Weight</label>
                            <input id="weight" name="weight" type="number" step="0.01" class="form-control" value="<?= esc(old('weight', (string) ($plan_holder['weight'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="branch_id">Branch Office</label>
                            <select id="branch_id" name="branch_id" class="form-select" required>
                                <option value="">Select Branch</option>
                                <?php $selectedBranch = (int) old('branch_id', (string) ($plan_holder['branch_id'] ?? 0)); ?>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= (int) $branch['branch_id'] ?>" <?= $selectedBranch === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                        <?= esc((string) $branch['branch_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h3 class="h6 text-primary mb-3">Spouse Information</h3>
                    <p class="text-muted small mb-2" id="spouseHint" style="display:none;">Required when Civil Status is Married.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" for="spouse_name">Name of Spouse</label>
                            <input id="spouse_name" name="spouse_name" class="form-control" value="<?= esc(old('spouse_name', (string) ($plan_holder['spouse_name'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="spouse_birthdate">Date of Birth (Spouse)</label>
                            <input id="spouse_birthdate" name="spouse_birthdate" type="date" class="form-control" value="<?= esc(old('spouse_birthdate', (string) ($plan_holder['spouse_birthdate'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="spouse_occupation">Occupation (Spouse)</label>
                            <input id="spouse_occupation" name="spouse_occupation" class="form-control" value="<?= esc(old('spouse_occupation', (string) ($plan_holder['spouse_occupation'] ?? ''))) ?>">
                        </div>
                    </div>

                    <h3 class="h6 text-primary mb-3">Contact Info</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="contact_number">Cellphone No.</label>
                            <input id="contact_number" name="contact_number" class="form-control" value="<?= esc(old('contact_number', (string) ($user['contact_number'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" name="email" type="email" class="form-control" value="<?= esc(old('email', (string) ($user['email'] ?? ''))) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="senior_citizen_id">Senior Citizen ID No.</label>
                            <input id="senior_citizen_id" name="senior_citizen_id" class="form-control" value="<?= esc(old('senior_citizen_id', (string) ($plan_holder['senior_citizen_id'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="organization_affiliation">Organization Affiliation</label>
                            <input id="organization_affiliation" name="organization_affiliation" class="form-control" value="<?= esc(old('organization_affiliation', (string) ($plan_holder['organization_affiliation'] ?? ''))) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-nav">
                <span></span>
                <button type="button" class="btn btn-primary wizard-next">Next: Government ID <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 2: Government ID Verification ============ -->
        <div class="wizard-panel d-none" data-step-panel="2">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-primary mb-1">Government ID Verification</h3>
                    <p class="text-muted small mb-3">Upload or take a photo of a valid government-issued ID so we can confirm it matches the information you entered.</p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label" for="id_type">Government ID Type</label>
                            <select id="id_type" class="form-select">
                                <option value="">Select ID type</option>
                                <?php foreach ($idTypes as $value => $label): ?>
                                    <option value="<?= esc((string) $value) ?>" <?= (string) ($latestVerification['id_type'] ?? '') === (string) $value ? 'selected' : '' ?>><?= esc((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnChooseUpload"><i class="ti ti-upload me-1"></i>Upload Government ID</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnChooseCamera"><i class="ti ti-camera me-1"></i>Take Photo</button>
                        <input type="file" id="idFileInput" accept="image/jpeg,image/png,image/webp" class="d-none">
                    </div>

                    <!-- Camera capture panel -->
                    <div id="cameraPanel" class="d-none mb-3">
                        <div class="camera-frame mb-2">
                            <video id="cameraVideo" autoplay playsinline muted></video>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" id="btnCapture"><i class="ti ti-camera me-1"></i>Capture</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelCamera">Cancel</button>
                        </div>
                        <p class="text-danger small mt-2 d-none" id="cameraError"></p>
                    </div>

                    <!-- Preview -->
                    <div id="idPreviewWrap" class="d-none mb-3">
                        <img id="idPreviewImg" alt="ID preview" class="id-preview">
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRetake"><i class="ti ti-refresh me-1"></i>Retake / Choose Another</button>
                        </div>
                    </div>

                    <canvas id="captureCanvas" class="d-none"></canvas>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button type="button" class="btn btn-success" id="btnVerifyId" disabled>Verify ID</button>
                        <span class="text-muted small" id="verifyHint">Choose or capture an image first.</span>
                    </div>

                    <div id="verificationResult" class="d-none"></div>
                </div>
            </div>

            <div class="wizard-nav">
                <button type="button" class="btn btn-outline-secondary wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="button" class="btn btn-primary wizard-next">Next: Beneficiaries <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 3: Beneficiaries ============ -->
        <div class="wizard-panel d-none" data-step-panel="3">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-primary mb-1">Beneficiaries <span class="text-danger">*</span></h3>
                    <p class="text-muted small mb-3">Add at least one beneficiary. You can leave empty rows blank.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Complete Name</th>
                                    <th style="width: 25%;">Birthday</th>
                                    <th style="width: 35%;">Relationship</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < 10; $i++): ?>
                                    <?php
                                    $beneficiary = $beneficiaries[$i] ?? [];
                                    $nameParts = array_filter([
                                        (string) ($beneficiary['first_name'] ?? ''),
                                        (string) ($beneficiary['middle_name'] ?? ''),
                                        (string) ($beneficiary['last_name'] ?? ''),
                                        (string) ($beneficiary['name_extension'] ?? ''),
                                    ]);
                                    $fullName = trim(implode(' ', $nameParts));
                                    ?>
                                    <tr>
                                        <td>
                                            <input
                                                class="form-control beneficiary-name"
                                                name="beneficiaries[<?= $i ?>][name]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.name', $fullName)) ?>"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="date"
                                                class="form-control"
                                                name="beneficiaries[<?= $i ?>][birthday]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.birthday', (string) ($beneficiary['date_of_birth'] ?? ''))) ?>"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                class="form-control beneficiary-relationship"
                                                name="beneficiaries[<?= $i ?>][relationship]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.relationship', (string) ($beneficiary['relationship'] ?? ''))) ?>"
                                            >
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-danger small mb-0 d-none" id="beneficiaryError">Please provide at least one beneficiary with both a name and a relationship.</p>
                </div>
            </div>

            <div class="wizard-nav">
                <button type="button" class="btn btn-outline-secondary wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="button" class="btn btn-primary wizard-next">Next: Emergency Contact <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 4: Emergency Contact ============ -->
        <div class="wizard-panel d-none" data-step-panel="4">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-primary mb-3">Emergency Contact</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="emergency_contact_name">Name</label>
                            <input id="emergency_contact_name" name="emergency_contact_name" class="form-control" value="<?= esc(old('emergency_contact_name', (string) ($plan_holder['emergency_contact_name'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="emergency_contact_number">Contact #</label>
                            <input id="emergency_contact_number" name="emergency_contact_number" class="form-control" value="<?= esc(old('emergency_contact_number', (string) ($plan_holder['emergency_contact_number'] ?? ''))) ?>">
                            <small class="text-danger d-none" id="emergencyContactError">Please enter a valid contact number (at least 10 digits).</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="emergency_contact_address">Address</label>
                            <input id="emergency_contact_address" name="emergency_contact_address" class="form-control" value="<?= esc(old('emergency_contact_address', (string) ($plan_holder['emergency_contact_address'] ?? ''))) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-nav">
                <button type="button" class="btn btn-outline-secondary wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="button" class="btn btn-primary wizard-next">Next: Review <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 5: Review & Certification ============ -->
        <div class="wizard-panel d-none" data-step-panel="5">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Applicant Information</span>
                    <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="1">Edit</button>
                </div>
                <div class="card-body small" id="reviewApplicant"></div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Government ID Verification</span>
                    <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="2">Edit</button>
                </div>
                <div class="card-body small" id="reviewIdVerification"></div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Beneficiaries</span>
                    <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="3">Edit</button>
                </div>
                <div class="card-body small" id="reviewBeneficiaries"></div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Emergency Contact</span>
                    <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="4">Edit</button>
                </div>
                <div class="card-body small" id="reviewEmergency"></div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-primary mb-2">Certification</h3>
                    <p class="text-muted small mb-2">This is to certify that the above information is TRUE AND CORRECT to the best of my knowledge.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="certify" name="certify" value="1" <?= old('certify') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="certify">
                            I certify that the information I provided is true and correct to the best of my knowledge.
                        </label>
                    </div>
                    <div class="wizard-nav mb-0">
                        <button type="button" class="btn btn-outline-secondary wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                        <button type="submit" class="btn btn-primary">Submit Registration</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .wizard-progress {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
        gap: .5rem;
        overflow-x: auto;
    }
    .wizard-progress__item {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex: 1 1 0;
        min-width: 140px;
        color: #94a3b8;
        font-size: .8rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .wizard-progress__badge {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e2e8f0;
        color: #64748b;
        flex-shrink: 0;
    }
    .wizard-progress__item.is-active .wizard-progress__badge,
    .wizard-progress__item.is-done .wizard-progress__badge {
        background: #2563eb;
        color: #fff;
    }
    .wizard-progress__item.is-active .wizard-progress__label,
    .wizard-progress__item.is-done .wizard-progress__label {
        color: #1e293b;
    }
    .wizard-nav {
        display: flex;
        justify-content: space-between;
        margin: 1.25rem 0;
    }
    .camera-frame {
        max-width: 480px;
        background: #000;
        border-radius: 8px;
        overflow: hidden;
    }
    .camera-frame video {
        width: 100%;
        display: block;
    }
    .id-preview {
        max-width: 480px;
        max-height: 320px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        object-fit: contain;
    }
    .psgc-dropdown {
        position: absolute;
        z-index: 1050;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 240px;
        overflow-y: auto;
        margin-top: .25rem;
    }
    .psgc-dropdown .list-group-item {
        cursor: pointer;
        border-left: 0;
        border-right: 0;
    }
    .psgc-dropdown .list-group-item:first-child { border-top: 0; }
    .psgc-dropdown .list-group-item:last-child { border-bottom: 0; }
    .psgc-dropdown .psgc-empty,
    .psgc-dropdown .psgc-loading,
    .psgc-dropdown .psgc-retry {
        padding: .5rem .75rem;
        color: #6c757d;
        font-size: .875rem;
    }
    .psgc-dropdown .psgc-retry button {
        padding: 0;
    }
</style>

<script src="<?= base_url('assets/js/id-verification-widget.js') ?>"></script>
<script>
(function () {
    // ==================================================================
    // Wizard step navigation
    // ==================================================================
    const TOTAL_STEPS = 5;
    let currentStep = 1;

    const panels = Array.from(document.querySelectorAll('.wizard-panel'));
    const progressItems = Array.from(document.querySelectorAll('.wizard-progress__item'));

    function showStep(step) {
        currentStep = Math.min(Math.max(step, 1), TOTAL_STEPS);

        panels.forEach(function (panel) {
            panel.classList.toggle('d-none', parseInt(panel.dataset.stepPanel, 10) !== currentStep);
        });

        progressItems.forEach(function (item) {
            const n = parseInt(item.dataset.progressStep, 10);
            item.classList.toggle('is-active', n === currentStep);
            item.classList.toggle('is-done', n < currentStep);
        });

        if (currentStep === 5) {
            populateReview();
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        if (step === 1) {
            const requiredIds = ['last_name', 'first_name', 'branch_id', 'email'];
            for (const id of requiredIds) {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) {
                    el.reportValidity ? el.reportValidity() : null;
                    el.focus();
                    return false;
                }
            }

            const civilStatus = document.getElementById('civil_status').value;
            const spouseName = document.getElementById('spouse_name');
            const spouseHint = document.getElementById('spouseHint');
            if (civilStatus === 'Married' && !spouseName.value.trim()) {
                spouseHint.style.display = 'block';
                spouseHint.classList.add('text-danger');
                spouseName.focus();
                return false;
            }
            spouseHint.style.display = civilStatus === 'Married' ? 'block' : 'none';
            spouseHint.classList.remove('text-danger');

            if (document.getElementById('city_municipality_code').value.trim() === ''
                || document.getElementById('barangay_code').value.trim() === '') {
                document.querySelectorAll('.psgc-error').forEach(function (el) {
                    if (document.getElementById('city_municipality_code').value.trim() === '' && el.dataset.field === 'city') {
                        el.textContent = 'Please select a Town/City from the list.';
                        el.classList.remove('d-none');
                    }
                    if (document.getElementById('barangay_code').value.trim() === '' && el.dataset.field === 'barangay') {
                        el.textContent = 'Please select a Barangay from the list.';
                        el.classList.remove('d-none');
                    }
                });
                return false;
            }

            return true;
        }

        if (step === 2) {
            // ID verification is encouraged, not force-blocked - a
            // provider outage or a low-confidence result shouldn't trap
            // the applicant. We do require that an attempt was made.
            if (!idVerificationWidget || !idVerificationWidget.wasAttempted()) {
                document.getElementById('verifyHint').textContent = 'Please verify your ID before continuing (or contact staff if you are unable to).';
                document.getElementById('verifyHint').classList.add('text-danger');
                return false;
            }
            return true;
        }

        if (step === 3) {
            const names = document.querySelectorAll('.beneficiary-name');
            const relationships = document.querySelectorAll('.beneficiary-relationship');
            let hasOne = false;
            for (let i = 0; i < names.length; i++) {
                const name = names[i].value.trim();
                const rel = relationships[i].value.trim();
                if (name !== '' && rel !== '') {
                    hasOne = true;
                }
                if ((name !== '' && rel === '') || (name === '' && rel !== '')) {
                    document.getElementById('beneficiaryError').textContent = 'Each beneficiary row needs both a name and a relationship.';
                    document.getElementById('beneficiaryError').classList.remove('d-none');
                    return false;
                }
            }
            if (!hasOne) {
                document.getElementById('beneficiaryError').textContent = 'Please provide at least one beneficiary with both a name and a relationship.';
                document.getElementById('beneficiaryError').classList.remove('d-none');
                return false;
            }
            document.getElementById('beneficiaryError').classList.add('d-none');
            return true;
        }

        if (step === 4) {
            const numberEl = document.getElementById('emergency_contact_number');
            const errorEl = document.getElementById('emergencyContactError');
            const value = numberEl.value.trim();
            if (value !== '' && !/^[0-9+\-()\s]{10,20}$/.test(value)) {
                errorEl.classList.remove('d-none');
                numberEl.focus();
                return false;
            }
            errorEl.classList.add('d-none');
            return true;
        }

        return true;
    }

    document.querySelectorAll('.wizard-next').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (validateStep(currentStep)) {
                showStep(currentStep + 1);
            }
        });
    });

    document.querySelectorAll('.wizard-back').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showStep(currentStep - 1);
        });
    });

    document.querySelectorAll('.wizard-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showStep(parseInt(btn.dataset.gotoStep, 10));
        });
    });

    function fieldValue(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    function row(label, value) {
        return '<div class="d-flex justify-content-between border-bottom py-1"><span class="text-muted">' + label + '</span><span class="fw-medium text-end">' + (value || '&mdash;') + '</span></div>';
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function populateReview() {
        const applicantHtml = [
            row('Name', esc([fieldValue('last_name'), fieldValue('first_name'), fieldValue('middle_name')].filter(Boolean).join(', '))),
            row('Address', esc([fieldValue('address_no'), fieldValue('address_street'), fieldValue('address_barangay'), fieldValue('address_city')].filter(Boolean).join(', '))),
            row('Date of Birth', esc(fieldValue('date_of_birth'))),
            row('Gender', esc(fieldValue('gender'))),
            row('Civil Status', esc(fieldValue('civil_status'))),
            row('Citizenship', esc(fieldValue('citizenship'))),
            row('Branch Office', esc(document.getElementById('branch_id').selectedOptions[0] ? document.getElementById('branch_id').selectedOptions[0].text : '')),
            row('Cellphone', esc(fieldValue('contact_number'))),
            row('Email', esc(fieldValue('email'))),
        ].join('');
        document.getElementById('reviewApplicant').innerHTML = applicantHtml;

        const idTypeSelect = document.getElementById('id_type');
        const idTypeLabel = idTypeSelect.selectedOptions[0] ? idTypeSelect.selectedOptions[0].text : 'Not selected';
        const currentIdStatus = idVerificationWidget ? idVerificationWidget.getStatus() : null;
        const statusText = currentIdStatus
            ? currentIdStatus.charAt(0).toUpperCase() + currentIdStatus.slice(1).replace('_', ' ')
            : 'Not yet verified';
        document.getElementById('reviewIdVerification').innerHTML = row('ID Type', esc(idTypeLabel)) + row('Status', esc(statusText));

        const beneficiaryRows = [];
        document.querySelectorAll('.beneficiary-name').forEach(function (input, i) {
            const name = input.value.trim();
            const rel = document.querySelectorAll('.beneficiary-relationship')[i].value.trim();
            if (name !== '') {
                beneficiaryRows.push(row(esc(name), esc(rel)));
            }
        });
        document.getElementById('reviewBeneficiaries').innerHTML = beneficiaryRows.length ? beneficiaryRows.join('') : '<span class="text-muted">None added.</span>';

        document.getElementById('reviewEmergency').innerHTML = [
            row('Name', esc(fieldValue('emergency_contact_name'))),
            row('Contact #', esc(fieldValue('emergency_contact_number'))),
            row('Address', esc(fieldValue('emergency_contact_address'))),
        ].join('');
    }

    document.getElementById('civil_status').addEventListener('change', function () {
        document.getElementById('spouseHint').style.display = this.value === 'Married' ? 'block' : 'none';
    });

    // ==================================================================
    // Government ID Verification (Step 2) - shared widget, see
    // public/assets/js/id-verification-widget.js. The same widget backs
    // users/create.php's Government ID step.
    // ==================================================================
    const idVerificationWidget = initIdVerificationWidget({
        endpoint: '<?= base_url('api/id-verification/verify') ?>',
        csrfName: document.querySelector('input[name="<?= csrf_token() ?>"]').name,
        csrfValue: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
        getIdentity: function () {
            return {
                first_name: fieldValue('first_name'),
                middle_name: fieldValue('middle_name'),
                last_name: fieldValue('last_name'),
                date_of_birth: fieldValue('date_of_birth'),
            };
        },
        resultFieldId: 'government_id_verification_id',
        resultFieldKey: 'verification_id',
        initialStatus: <?= $latestVerification ? json_encode((string) $latestVerification['verification_status']) : 'null' ?>,
    });

    // ==================================================================
    // PSGC Cloud address dropdowns (Town/City -> Barangay)
    // ==================================================================
    const ADDRESS_API_BASE = '<?= base_url('api/address') ?>';

    const citySearch = document.getElementById('city_search');
    const addressCityInput = document.getElementById('address_city');
    const cityCodeInput = document.getElementById('city_municipality_code');
    const cityDropdown = citySearch.closest('.psgc-field').querySelector('.psgc-dropdown');
    const cityError = document.querySelector('.psgc-error[data-field="city"]');

    const barangaySearch = document.getElementById('barangay_search');
    const addressBarangayInput = document.getElementById('address_barangay');
    const barangayCodeInput = document.getElementById('barangay_code');
    const barangayDropdown = barangaySearch.closest('.psgc-field').querySelector('.psgc-dropdown');
    const barangayError = document.querySelector('.psgc-error[data-field="barangay"]');

    let cityDebounce = null;
    let citySearchToken = 0;
    let barangayOptions = [];
    let lastSelectedCityName = addressCityInput.value || '';
    let lastSelectedBarangayName = addressBarangayInput.value || '';

    function showDropdown(el) { el.classList.remove('d-none'); }
    function hideDropdown(el) { el.classList.add('d-none'); el.innerHTML = ''; }

    function renderMessage(el, text, isError, retryFn) {
        el.innerHTML = '';
        const rowEl = document.createElement('div');
        rowEl.className = isError ? 'psgc-retry' : 'psgc-loading';
        rowEl.textContent = text + ' ';
        if (isError && retryFn) {
            const retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.className = 'btn btn-link btn-sm p-0 align-baseline';
            retryBtn.textContent = 'Retry';
            retryBtn.addEventListener('click', retryFn);
            rowEl.appendChild(retryBtn);
        }
        el.appendChild(rowEl);
        showDropdown(el);
    }

    function clearFieldError(el) {
        el.classList.add('d-none');
        el.textContent = '';
    }

    function setFieldError(el, message) {
        el.textContent = message;
        el.classList.remove('d-none');
    }

    function searchCities(query) {
        const token = ++citySearchToken;
        renderMessage(cityDropdown, 'Loading towns/cities...', false);

        fetch(ADDRESS_API_BASE + '/cities?q=' + encodeURIComponent(query))
            .then((res) => {
                if (!res.ok) { throw new Error('bad_status'); }
                return res.json();
            })
            .then((payload) => {
                if (token !== citySearchToken) { return; }
                const results = Array.isArray(payload.data) ? payload.data : [];
                renderCityResults(results);
            })
            .catch(() => {
                if (token !== citySearchToken) { return; }
                renderMessage(cityDropdown, 'Unable to load Philippine address data. Please try again.', true, function () {
                    searchCities(query);
                });
            });
    }

    function renderCityResults(results) {
        cityDropdown.innerHTML = '';
        if (results.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'psgc-empty';
            empty.textContent = 'No matching town/city found.';
            cityDropdown.appendChild(empty);
            showDropdown(cityDropdown);
            return;
        }

        results.forEach(function (rowData) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action py-2';
            item.textContent = rowData.name;
            item.addEventListener('click', function () {
                selectCity(rowData);
            });
            cityDropdown.appendChild(item);
        });
        showDropdown(cityDropdown);
    }

    function selectCity(rowData) {
        citySearch.value = rowData.name;
        addressCityInput.value = rowData.name;
        cityCodeInput.value = rowData.code;
        lastSelectedCityName = rowData.name;
        clearFieldError(cityError);
        hideDropdown(cityDropdown);
        resetBarangay();
        loadBarangays(rowData.code);
    }

    function resetBarangay() {
        barangaySearch.value = '';
        barangaySearch.placeholder = 'Loading barangays...';
        barangaySearch.disabled = true;
        addressBarangayInput.value = '';
        barangayCodeInput.value = '';
        lastSelectedBarangayName = '';
        barangayOptions = [];
        hideDropdown(barangayDropdown);
        clearFieldError(barangayError);
    }

    citySearch.addEventListener('input', function () {
        if (citySearch.value !== lastSelectedCityName) {
            addressCityInput.value = '';
            cityCodeInput.value = '';
        }

        const query = citySearch.value.trim();
        window.clearTimeout(cityDebounce);

        if (query.length < 2) {
            hideDropdown(cityDropdown);
            return;
        }

        cityDebounce = window.setTimeout(function () {
            searchCities(query);
        }, 300);
    });

    citySearch.addEventListener('focus', function () {
        if (citySearch.value.trim().length >= 2) {
            searchCities(citySearch.value.trim());
        }
    });

    function loadBarangays(cityCode, preselectCode) {
        renderMessage(barangayDropdown, 'Loading barangays...', false);

        fetch(ADDRESS_API_BASE + '/barangays/' + encodeURIComponent(cityCode))
            .then((res) => {
                if (!res.ok) { throw new Error('bad_status'); }
                return res.json();
            })
            .then((payload) => {
                barangayOptions = Array.isArray(payload.data) ? payload.data : [];
                barangaySearch.disabled = false;
                barangaySearch.placeholder = 'Search barangay...';
                hideDropdown(barangayDropdown);

                if (preselectCode) {
                    const match = barangayOptions.find(function (b) { return b.code === preselectCode; });
                    if (match) {
                        selectBarangay(match);
                    }
                }
            })
            .catch(() => {
                barangaySearch.placeholder = 'Unable to load barangays';
                renderMessage(barangayDropdown, 'Unable to load Philippine address data. Please try again.', true, function () {
                    loadBarangays(cityCode, preselectCode);
                });
            });
    }

    function filterBarangays(query) {
        const needle = query.trim().toLowerCase();
        const results = needle === ''
            ? barangayOptions
            : barangayOptions.filter(function (b) { return b.name.toLowerCase().indexOf(needle) !== -1; });

        barangayDropdown.innerHTML = '';
        if (results.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'psgc-empty';
            empty.textContent = 'No matching barangay found.';
            barangayDropdown.appendChild(empty);
            showDropdown(barangayDropdown);
            return;
        }

        results.forEach(function (rowData) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action py-2';
            item.textContent = rowData.name;
            item.addEventListener('click', function () {
                selectBarangay(rowData);
            });
            barangayDropdown.appendChild(item);
        });
        showDropdown(barangayDropdown);
    }

    function selectBarangay(rowData) {
        barangaySearch.value = rowData.name;
        addressBarangayInput.value = rowData.name;
        barangayCodeInput.value = rowData.code;
        lastSelectedBarangayName = rowData.name;
        clearFieldError(barangayError);
        hideDropdown(barangayDropdown);
    }

    barangaySearch.addEventListener('input', function () {
        if (barangaySearch.value !== lastSelectedBarangayName) {
            addressBarangayInput.value = '';
            barangayCodeInput.value = '';
        }
        filterBarangays(barangaySearch.value);
    });

    barangaySearch.addEventListener('focus', function () {
        if (!barangaySearch.disabled) {
            filterBarangays(barangaySearch.value);
        }
    });

    document.addEventListener('click', function (event) {
        if (!citySearch.closest('.psgc-field').contains(event.target)) {
            hideDropdown(cityDropdown);
        }
        if (!barangaySearch.closest('.psgc-field').contains(event.target)) {
            hideDropdown(barangayDropdown);
        }
    });

    const savedCityCode = cityCodeInput.value.trim();
    const savedBarangayCode = barangayCodeInput.value.trim();
    if (savedCityCode !== '') {
        barangaySearch.value = lastSelectedBarangayName;
        loadBarangays(savedCityCode, savedBarangayCode || null);
    } else {
        barangaySearch.placeholder = 'Select a Town/City first';
    }

    showStep(1);
})();
</script>
<?= $this->endSection() ?>
