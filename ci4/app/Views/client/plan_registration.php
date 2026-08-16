<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/registration-wizard.css') ?>">
<script src="<?= base_url('assets/vendor/tesseract/tesseract.min.js') ?>"></script>

<?php
$beneficiaries = $beneficiaries ?? [];
$coordinators = $coordinators ?? [];
$id_types = $id_types ?? [];
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$planId = (int) ($plan_id ?? 0);
$applicationDate = (string) old('application_date', (string) ($plan_holder['application_date'] ?? date('Y-m-d')));
$civilStatus = (string) old('civil_status', (string) ($plan_holder['civil_status'] ?? ''));
$gender = (string) old('gender', (string) ($plan_holder['gender'] ?? ''));
$selectedBranch = (int) old('branch_id', (string) ($plan_holder['branch_id'] ?? 0));
$selectedCoordinator = (int) old('coordinator_user_id', (string) ($plan_holder['coordinator_user_id'] ?? 0));
?>

<div class="rw">

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="rw-alert rw-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="rw-alert rw-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
    <?php $errors = session()->getFlashdata('errors') ?? []; ?>
    <?php if (! empty($errors)): ?>
        <div class="rw-alert rw-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul style="margin:4px 0 0;padding-left:18px;">
                    <?php foreach ($errors as $message): ?>
                        <li><?= esc((string) $message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- ====== Progress Steps ====== -->
    <div class="rw-steps" id="rw-steps">
        <div class="rw-step rw-step--active" data-rw-step="1">
            <div class="rw-step__circle">1</div>
            <div class="rw-step__label">Applicant Information</div>
        </div>
        <div class="rw-step__line"></div>
        <div class="rw-step" data-rw-step="2">
            <div class="rw-step__circle">2</div>
            <div class="rw-step__label">Beneficiaries</div>
        </div>
        <div class="rw-step__line"></div>
        <div class="rw-step" data-rw-step="3">
            <div class="rw-step__circle">3</div>
            <div class="rw-step__label">Verification</div>
        </div>
        <div class="rw-step__line"></div>
        <div class="rw-step" data-rw-step="4">
            <div class="rw-step__circle">4</div>
            <div class="rw-step__label">Initial Payment</div>
        </div>
    </div>

    <form method="post" action="<?= base_url('plan-registration') ?>" enctype="multipart/form-data" id="rw-form">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= $planId ?>">
        <input type="hidden" name="package_id" value="<?= $planId ?>">

        <!-- ================================================================ -->
        <!-- STEP 1: Applicant Information                                    -->
        <!-- ================================================================ -->
        <div class="rw-panel" data-rw-panel="1">
            <div class="rw-section-title">Step 1 — Applicant Information</div>
            <div class="rw-section-sub">Complete the applicant's personal and contact details.</div>

            <div class="rw-two-col">
                <!-- Left Column -->
                <div>
                    <!-- Application Details -->
                    <div class="rw-card">
                        <h3 class="rw-card__title">Application Details</h3>
                        <div class="rw-form-row rw-form-row--3">
                            <div class="rw-group">
                                <label class="rw-label" for="id_control_no">ID Control No.</label>
                                <input class="rw-input" id="id_control_no" name="id_control_no" value="<?= esc(old('id_control_no', (string) ($plan_holder['id_control_no'] ?? ''))) ?>">
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="coordinator_user_id">Coordinator <span>*</span></label>
                                <select class="rw-select" id="coordinator_user_id" name="coordinator_user_id" data-rw-req>
                                    <option value="">Select your coordinator</option>
                                    <?php foreach ($coordinators as $coord): ?>
                                        <?php $coordName = trim(implode(' ', array_filter([$coord['first_name'] ?? '', $coord['middle_name'] ?? '', $coord['last_name'] ?? ''], static fn ($v) => $v !== ''))); ?>
                                        <option value="<?= (int) $coord['user_id'] ?>" <?= $selectedCoordinator === (int) $coord['user_id'] ? 'selected' : '' ?>>
                                            <?= esc($coordName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($coordinators)): ?>
                                    <small style="color:var(--rw-red);">No active staff coordinators available yet.</small>
                                <?php endif; ?>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="application_date">Date of Application <span>*</span></label>
                                <input class="rw-input" id="application_date" name="application_date" type="date" value="<?= esc($applicationDate) ?>" data-rw-req>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details -->
                    <div class="rw-card">
                        <h3 class="rw-card__title">Personal Details</h3>
                        <div class="rw-form-row rw-form-row--3">
                            <div class="rw-group">
                                <label class="rw-label" for="last_name">Last Name <span>*</span></label>
                                <input class="rw-input" id="last_name" name="last_name" value="<?= esc(old('last_name', (string) ($user['last_name'] ?? ''))) ?>" data-rw-req>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="first_name">Given Name <span>*</span></label>
                                <input class="rw-input" id="first_name" name="first_name" value="<?= esc(old('first_name', (string) ($user['first_name'] ?? ''))) ?>" data-rw-req>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="middle_name">Middle Name <span>*</span></label>
                                <input class="rw-input" id="middle_name" name="middle_name" value="<?= esc(old('middle_name', (string) ($user['middle_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="rw-form-row rw-form-row--4">
                            <div class="rw-group">
                                <label class="rw-label" for="date_of_birth">Date of Birth <span>*</span></label>
                                <input class="rw-input" id="date_of_birth" name="date_of_birth" type="date" value="<?= esc(old('date_of_birth', (string) ($plan_holder['date_of_birth'] ?? ''))) ?>" data-rw-req>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="age">Age</label>
                                <input class="rw-input" id="age" name="age" type="number" value="<?= esc(old('age', (string) ($plan_holder['age'] ?? ''))) ?>" readonly>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="gender">Gender <span>*</span></label>
                                <select class="rw-select" id="gender" name="gender" data-rw-req>
                                    <option value="">Gender</option>
                                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="civil_status">Civil Status <span>*</span></label>
                                <select class="rw-select" id="civil_status" name="civil_status" data-rw-req>
                                    <option value="">Civil Status</option>
                                    <option value="Single" <?= $civilStatus === 'Single' ? 'selected' : '' ?>>Single</option>
                                    <option value="Married" <?= $civilStatus === 'Married' ? 'selected' : '' ?>>Married</option>
                                    <option value="Divorced" <?= $civilStatus === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                    <option value="Widowed" <?= $civilStatus === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Address & Location -->
                    <div class="rw-card">
                        <h3 class="rw-card__title">Address & Location</h3>
                        <div class="rw-form-row rw-form-row--2">
                            <div class="rw-group">
                                <label class="rw-label" for="address_no">Address No.</label>
                                <input class="rw-input" id="address_no" name="address_no" value="<?= esc(old('address_no', (string) ($plan_holder['address_no'] ?? ''))) ?>">
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="address_street">Street</label>
                                <input class="rw-input" id="address_street" name="address_street" value="<?= esc(old('address_street', (string) ($plan_holder['address_street'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="rw-form-row rw-form-row--3">
                            <div class="rw-group">
                                <label class="rw-label" for="address_province">Province <span>*</span></label>
                                <select class="rw-select" id="address_province" name="address_province" data-rw-req data-initial-province="<?= esc(old('address_province', (string) ($plan_holder['address_province'] ?? ''))) ?>">
                                    <option value="">Select Province</option>
                                </select>
                                <input type="hidden" id="address_province_code" name="address_province_code" value="<?= esc(old('address_province_code', (string) ($plan_holder['address_province_code'] ?? ''))) ?>">
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="address_city">Town/City <span>*</span></label>
                                <select class="rw-select" id="address_city" name="address_city" data-rw-req data-initial-city="<?= esc(old('address_city', (string) ($plan_holder['address_city'] ?? ''))) ?>">
                                    <option value="">Select Province first</option>
                                </select>
                                <input type="hidden" id="address_city_code" name="address_city_code" value="<?= esc(old('address_city_code', (string) ($plan_holder['address_city_code'] ?? ''))) ?>">
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="address_barangay">Barangay <span>*</span></label>
                                <select class="rw-select" id="address_barangay" name="address_barangay" data-rw-req data-initial-barangay="<?= esc(old('address_barangay', (string) ($plan_holder['address_barangay'] ?? ''))) ?>">
                                    <option value="">Select Town/City first</option>
                                </select>
                            </div>
                        </div>
                        <div class="rw-form-row rw-form-row--3">
                            <div class="rw-group">
                                <label class="rw-label" for="citizenship">Citizenship</label>
                                <input class="rw-input" id="citizenship" name="citizenship" value="<?= esc(old('citizenship', (string) ($plan_holder['citizenship'] ?? ''))) ?>">
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="branch_id">Branch Office <span>*</span></label>
                                <select class="rw-select" id="branch_id" name="branch_id" data-rw-req>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= (int) $branch['branch_id'] ?>" <?= $selectedBranch === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                            <?= esc((string) $branch['branch_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="place_of_birth">Place of Birth</label>
                                <input class="rw-input" id="place_of_birth" name="place_of_birth" value="<?= esc(old('place_of_birth', (string) ($plan_holder['place_of_birth'] ?? ''))) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Spouse Information (required when Civil Status = Married) -->
                    <div class="rw-card" id="spouse_card">
                        <h3 class="rw-card__title">Spouse Information
                            <span id="spouse_req_badge" class="rw-required-badge" style="display:none;">Required when Married</span>
                        </h3>
                        <div class="rw-form-row rw-form-row--2">
                            <div class="rw-group">
                                <label class="rw-label" for="spouse_last_name">Last Name</label>
                                <input class="rw-input" id="spouse_last_name" name="spouse_last_name" data-spouse-req value="<?= esc(old('spouse_last_name', (string) ($plan_holder['spouse_last_name'] ?? ''))) ?>">
                            </div>
                            <div class="rw-group">
                                <label class="rw-label" for="spouse_first_name">Given Name</label>
                                <input class="rw-input" id="spouse_first_name" name="spouse_first_name" data-spouse-req value="<?= esc(old('spouse_first_name', (string) ($plan_holder['spouse_first_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="rw-form-row">
                            <div class="rw-group">
                                <label class="rw-label" for="spouse_middle_name">Middle Name</label>
                                <input class="rw-input" id="spouse_middle_name" name="spouse_middle_name" value="<?= esc(old('spouse_middle_name', (string) ($plan_holder['spouse_middle_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="rw-spouse-note" id="spouse_note" style="display:none;">
                            <i class="mdi mdi-information-outline"></i>
                            Spouse first and last name are required because your Civil Status is Married.
                        </div>
                    </div>

                    <!-- Contact & Affiliation -->
                    <div class="rw-card">
                        <h3 class="rw-card__title">Contact & Affiliation</h3>
                        <div class="rw-form-row">
                            <div class="rw-group">
                                <label class="rw-label" for="contact_number">Cellphone No. <span>*</span></label>
                                <input class="rw-input" id="contact_number" name="contact_number" data-rw-req value="<?= esc(old('contact_number', (string) ($user['contact_number'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="rw-form-row">
                            <div class="rw-group">
                                <label class="rw-label" for="email">Email</label>
                                <input class="rw-input" id="email" name="email" type="email" value="<?= esc(old('email', (string) ($user['email'] ?? ''))) ?>" data-rw-req>
                            </div>
                        </div>
                        <div class="rw-form-row">
                            <div class="rw-group">
                                <label class="rw-label" for="senior_citizen_id">Senior Citizen ID No.</label>
                                <input class="rw-input" id="senior_citizen_id" name="senior_citizen_id" value="<?= esc(old('senior_citizen_id', (string) ($plan_holder['senior_citizen_id'] ?? ''))) ?>">
                            </div>
                        </div>
                        <div class="rw-form-row">
                            <div class="rw-group">
                                <label class="rw-label" for="organization_affiliation">Organization Affiliation</label>
                                <input class="rw-input" id="organization_affiliation" name="organization_affiliation" value="<?= esc(old('organization_affiliation', (string) ($plan_holder['organization_affiliation'] ?? ''))) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- STEP 2: Beneficiaries                                            -->
        <!-- ================================================================ -->
        <div class="rw-panel" data-rw-panel="2" style="display:none;">
            <div class="rw-section-title">Step 2 — Beneficiaries</div>
            <div class="rw-section-sub">Add at least one beneficiary. You can leave empty rows blank.</div>

            <div class="rw-card">
                <div class="rw-table-wrap">
                    <table class="rw-table">
                        <thead>
                            <tr>
                                <th style="width:20%;">Last Name</th>
                                <th style="width:20%;">Given Name</th>
                                <th style="width:18%;">Middle Name</th>
                                <th style="width:18%;">Birthday</th>
                                <th style="width:24%;">Relationship</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < 10; $i++):
                                $beneficiary = $beneficiaries[$i] ?? [];
                            ?>
                            <tr>
                                <td><input class="rw-input" name="beneficiaries[<?= $i ?>][last_name]" value="<?= esc(old('beneficiaries.' . $i . '.last_name', (string) ($beneficiary['last_name'] ?? ''))) ?>"></td>
                                <td><input class="rw-input" name="beneficiaries[<?= $i ?>][first_name]" value="<?= esc(old('beneficiaries.' . $i . '.first_name', (string) ($beneficiary['first_name'] ?? ''))) ?>"></td>
                                <td><input class="rw-input" name="beneficiaries[<?= $i ?>][middle_name]" value="<?= esc(old('beneficiaries.' . $i . '.middle_name', (string) ($beneficiary['middle_name'] ?? ''))) ?>"></td>
                                <td><input class="rw-input" type="date" name="beneficiaries[<?= $i ?>][birthday]" value="<?= esc(old('beneficiaries.' . $i . '.birthday', (string) ($beneficiary['date_of_birth'] ?? ''))) ?>"></td>
                                <td><input class="rw-input" name="beneficiaries[<?= $i ?>][relationship]" value="<?= esc(old('beneficiaries.' . $i . '.relationship', (string) ($beneficiary['relationship'] ?? ''))) ?>"></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- STEP 3: Verification                                             -->
        <!-- ================================================================ -->
        <div class="rw-panel" data-rw-panel="3" style="display:none;">
            <div class="rw-section-title">Step 3 — Government ID Verification</div>
            <div class="rw-section-sub">Upload a supported government-issued ID. The system scans the document and checks that it <strong>appears consistent</strong> with the details you provided. This is a document check only — it does NOT prove the ID was issued by the government authority.</div>

            <div class="rw-two-col">
                <div class="rw-card">
                    <h3 class="rw-card__title">Government ID</h3>
                    <div class="rw-form-row rw-form-row--2">
                        <div class="rw-group">
                            <label class="rw-label" for="id_type">ID Type <span>*</span></label>
                            <select class="rw-select" id="id_type" name="id_type" data-rw-req>
                                <option value="">Select ID type</option>
                                <?php foreach ($id_types as $key => $info): ?>
                                    <option value="<?= esc($key) ?>" <?= old('id_type') === $key ? 'selected' : '' ?>><?= esc($info['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="rw-group">
                            <label class="rw-label" for="id_number">ID Number <span>*</span></label>
                            <input class="rw-input" id="id_number" name="id_number" data-rw-req value="<?= esc(old('id_number')) ?>" placeholder="Enter the ID number shown on your card">
                        </div>
                    </div>
                    <div class="rw-upload-area" onclick="document.getElementById('valid_id').click();">
                        <i class="mdi mdi-cloud-upload-outline"></i>
                        <p>Click to upload or drag and drop</p>
                        <small>Accepted formats: JPG, PNG (max 2MB)</small>
                    </div>
                    <input id="valid_id" name="valid_id" type="file" class="rw-input" accept="image/jpeg,image/png,image/*" style="display:none;" onchange="rwStartIdScan(this)">
                    <div id="rw-file-name" style="margin-top:8px;font-size:0.82rem;color:var(--rw-ink-soft);"></div>
                    <input type="hidden" name="ocr_text" id="ocr_text" value="">
                    <div id="rw-ocr-status" class="rw-ocr-status" style="display:none;"></div>
                    <div id="rw-id-result" class="rw-id-result" style="display:none;">
                        <i class="mdi mdi-shield-check"></i>
                        <div>
                            <strong id="rw-id-result-title"></strong>
                            <div id="rw-id-result-detail"></div>
                        </div>
                    </div>
                </div>
                <div class="rw-card">
                    <h3 class="rw-card__title">Verification Note</h3>
                    <div class="rw-info-box">
                        <h6>The system checks:</h6>
                        <ul>
                            <li>That the ID is a supported government-issued ID</li>
                            <li>That the document appears consistent with your name, birthday, and address</li>
                            <li>The ID number format for the selected ID type</li>
                        </ul>
                        <p style="margin-top:8px;font-style:italic;font-size:0.8rem;color:var(--rw-ink-soft);">
                            "Appears consistent" means the document matches your application details. It does NOT mean a government authority has officially verified the ID. A staff member will confirm the document before your account is activated.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rw-card" style="margin-top:16px;">
                <h3 class="rw-card__title">Certification</h3>
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    <input class="rw-input--check" type="checkbox" id="certify" name="certify" value="1" <?= old('certify') ? 'checked' : '' ?> required style="margin-top:4px;width:18px;height:18px;">
                    <label for="certify" style="font-size:0.86rem;color:var(--rw-ink-soft);line-height:1.5;">
                        This is to certify that the above information is <strong>TRUE AND CORRECT</strong> to the best of my knowledge.
                    </label>
                </div>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- STEP 4: Initial Payment                                          -->
        <!-- ================================================================ -->
        <div class="rw-panel" data-rw-panel="4" style="display:none;">
            <div class="rw-section-title">Step 4 — Initial Payment</div>
            <div class="rw-section-sub">Submit your initial payment to activate your membership.</div>

            <div class="rw-card">
                <h3 class="rw-card__title">Payment Details</h3>
                <div class="rw-form-row rw-form-row--3">
                    <div class="rw-group">
                        <label class="rw-label">Monthly Fee</label>
                        <input class="rw-input" type="text" value="₱<?= number_format((float) ($program['monthly_fee'] ?? 240), 2) ?>" readonly>
                    </div>
                    <div class="rw-group">
                        <label class="rw-label">Plan</label>
                        <input class="rw-input" type="text" value="<?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?>" readonly>
                    </div>
                    <div class="rw-group">
                        <label class="rw-label">Status</label>
                        <input class="rw-input" type="text" value="Pending Registration" readonly>
                    </div>
                </div>
                <div class="rw-info-box" style="margin-top:12px;">
                    <h6>What happens next?</h6>
                    <p style="margin:0;">After you submit your registration, you will go to the <strong>Initial Payment</strong> step. You may pay online via GCash to your assigned coordinator, or pay in cash at your branch. Once the payment is verified by staff, your membership will be activated.</p>
                </div>
            </div>
        </div>

        <!-- ====== Navigation ====== -->
        <div class="rw-nav">
            <div class="rw-nav__left">
                <button type="button" class="rw-btn rw-btn--outline rw-prev" style="display:none;" onclick="rwNav(-1)">
                    <i class="mdi mdi-chevron-left"></i> Previous
                </button>
            </div>
            <div class="rw-nav__right">
                <button type="button" class="rw-btn rw-btn--primary rw-next" onclick="rwNav(1)">
                    Next <i class="mdi mdi-chevron-right"></i>
                </button>
                <button type="submit" class="rw-btn rw-btn--primary rw-submit" style="display:none;">
                    <i class="mdi mdi-check"></i> Submit Registration
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    var currentStep = 1;
    var totalSteps = 4;

    function showStep(step) {
        currentStep = step;

        /* Hide all panels */
        document.querySelectorAll('[data-rw-panel]').forEach(function (p) {
            p.style.display = 'none';
        });

        /* Show current panel */
        var panel = document.querySelector('[data-rw-panel="' + step + '"]');
        if (panel) panel.style.display = '';

        /* Update step indicators */
        document.querySelectorAll('.rw-step').forEach(function (s) {
            var sStep = parseInt(s.getAttribute('data-rw-step'));
            s.classList.remove('rw-step--active', 'rw-step--completed');
            if (sStep === step) s.classList.add('rw-step--active');
            else if (sStep < step) s.classList.add('rw-step--completed');
        });

        /* Update circle numbers for completed steps */
        document.querySelectorAll('.rw-step--completed .rw-step__circle').forEach(function (c) {
            c.innerHTML = '<i class="mdi mdi-check"></i>';
        });
        document.querySelectorAll('.rw-step:not(.rw-step--completed):not(.rw-step--active) .rw-step__circle').forEach(function (c) {
            var sStep = parseInt(c.closest('.rw-step').getAttribute('data-rw-step'));
            c.textContent = sStep;
        });

        /* Show/hide nav buttons */
        var prevBtn = document.querySelector('.rw-prev');
        var nextBtn = document.querySelector('.rw-next');
        var submitBtn = document.querySelector('.rw-submit');

        if (prevBtn) prevBtn.style.display = step === 1 ? 'none' : '';
        if (nextBtn) nextBtn.style.display = step === totalSteps ? 'none' : '';
        if (submitBtn) submitBtn.style.display = step === totalSteps ? '' : 'none';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.rwNav = function (dir) {
        if (dir === 1) {
            /* Validate required fields in current step */
            var reqFields = document.querySelectorAll('[data-rw-panel="' + currentStep + '"] [data-rw-req]');
            for (var i = 0; i < reqFields.length; i++) {
                if (!String(reqFields[i].value || '').trim()) {
                    reqFields[i].focus();
                    reqFields[i].style.borderColor = 'var(--rw-red)';
                    setTimeout(function () { reqFields[i].style.borderColor = ''; }, 2000);
                    return;
                }
            }

            /* Validate step 2 has at least one beneficiary */
            if (currentStep === 2) {
                var benFields = document.querySelectorAll('input[name^="beneficiaries["]');
                var hasEntry = false;
                benFields.forEach(function (f) { if (String(f.value || '').trim()) hasEntry = true; });
                if (!hasEntry) {
                    alert('Please add at least one beneficiary before continuing.');
                    return;
                }
            }

            /* Validate step 3: ID scan completed + appears consistent + certification */
            if (currentStep === 3) {
                var idScanState = window.rwIdScanState || {};
                if (!idScanState.ocrDone) {
                    alert('Please upload your government ID and wait for the scan to finish before continuing.');
                    return;
                }
                if (!idScanState.matched) {
                    alert('The document does not appear consistent with the details you provided. Please re-check your ID and your details, then re-scan.');
                    return;
                }
                var certify = document.getElementById('certify');
                if (certify && !certify.checked) {
                    alert('Please certify that the information is true and correct.');
                    return;
                }
            }
        }

        var next = currentStep + dir;
        if (next >= 1 && next <= totalSteps) {
            showStep(next);
        }
    };

    /* ------------------------------------------------------------------ */
    /* Civil Status -> Spouse dynamic requirement                         */
    /* ------------------------------------------------------------------ */
    var OCR_WORKER = <?= json_encode(base_url('assets/vendor/tesseract/worker.min.js')) ?>;
    var OCR_CORE = <?= json_encode(base_url('assets/vendor/tesseract/tesseract-core.wasm.js')) ?>;
    var OCR_LANG_PATH = <?= json_encode(base_url('assets/vendor/tesseract')) ?>;

    function updateSpouseRequirement() {
        var cs = document.getElementById('civil_status');
        var required = cs && cs.value === 'Married';
        document.querySelectorAll('[data-spouse-req]').forEach(function (el) {
            if (required) el.setAttribute('data-rw-req', '1');
            else el.removeAttribute('data-rw-req');
        });
        var card = document.getElementById('spouse_card');
        if (card) card.classList.toggle('rw-card--spouse-required', required);
        var badge = document.getElementById('spouse_req_badge');
        var note = document.getElementById('spouse_note');
        if (badge) badge.style.display = required ? '' : 'none';
        if (note) note.style.display = required ? '' : 'none';
    }

    var civilStatusEl = document.getElementById('civil_status');
    if (civilStatusEl) {
        civilStatusEl.addEventListener('change', updateSpouseRequirement);
    }
    updateSpouseRequirement();

    /* ------------------------------------------------------------------ */
    /* Government ID: client-side scan + rough match estimate              */
    /* The server re-scores the OCR text authoritatively on submit.        */
    /* ------------------------------------------------------------------ */
    window.rwIdScanState = { ocrDone: false, matched: false };

    function rwNormalize(s) {
        return String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function rwEstimateMatch(text) {
        var t = rwNormalize(text);
        var last = rwNormalize((document.getElementById('last_name') || {}).value || '');
        var first = rwNormalize((document.getElementById('first_name') || {}).value || '');
        var dobEl = document.getElementById('date_of_birth');

        if (t === '' || last === '') return { pass: false, detail: 'The document could not be matched against your last name.' };

        var score = 0;
        if (t.indexOf(last) !== -1) score += 30; else return { pass: false, detail: 'Your last name was not found on the document.' };

        if (t.indexOf(first) !== -1) score += 25;
        else {
            var firstToken = (first.split(' ')[0] || '');
            if (firstToken.length >= 2 && t.indexOf(firstToken) !== -1) score += 25;
            else return { pass: false, detail: 'Your given name was not found on the document.' };
        }

        if (dobEl && dobEl.value) {
            var d = new Date(dobEl.value);
            if (!isNaN(d.getTime())) {
                var y = d.getFullYear();
                var mm = String(d.getMonth() + 1).padStart(2, '0');
                var dd = String(d.getDate()).padStart(2, '0');
                if (t.indexOf(y + ' ' + mm + ' ' + dd) !== -1 || t.indexOf(mm + ' ' + dd + ' ' + y) !== -1 || t.indexOf('' + y + mm + dd) !== -1) score += 20;
            }
        }

        return score >= 55
            ? { pass: true, detail: 'The document appears consistent with the details you provided.' }
            : { pass: false, detail: 'The document does not appear to match your details (estimate score ' + score + ').' };
    }

    window.rwStartIdScan = async function (input) {
        var nameEl = document.getElementById('rw-file-name');
        var statusEl = document.getElementById('rw-ocr-status');
        var resultEl = document.getElementById('rw-id-result');
        var ocrInput = document.getElementById('ocr_text');

        window.rwIdScanState = { ocrDone: false, matched: false };

        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (nameEl) nameEl.textContent = 'Selected: ' + file.name;

        if (file.size > 2 * 1024 * 1024) {
            statusEl.style.display = '';
            statusEl.className = 'rw-ocr-status rw-ocr-status--error';
            statusEl.textContent = 'File is larger than 2MB. Please upload a smaller photo.';
            ocrInput.value = '';
            return;
        }

        if (typeof Tesseract === 'undefined') {
            statusEl.style.display = '';
            statusEl.className = 'rw-ocr-status rw-ocr-status--error';
            statusEl.textContent = 'The scanner engine did not load. Please refresh the page and try again.';
            return;
        }

        statusEl.style.display = '';
        statusEl.className = 'rw-ocr-status rw-ocr-status--working';
        statusEl.textContent = 'Scanning ID... this can take a few seconds.';
        resultEl.style.display = 'none';

        try {
            var worker = await Tesseract.createWorker('eng', 1, {
                workerPath: OCR_WORKER,
                corePath: OCR_CORE,
                langPath: OCR_LANG_PATH,
                logger: function (m) {
                    if (m && m.status === 'recognizing text' && statusEl) {
                        statusEl.textContent = 'Scanning ID... ' + Math.round((m.progress || 0) * 100) + '%';
                    }
                }
            });
            var result = await worker.recognize(file);
            await worker.terminate();

            var text = String((result && result.data && result.data.text) || '').trim();
            ocrInput.value = text;
            statusEl.style.display = 'none';

            if (text === '') {
                window.rwIdScanState = { ocrDone: false, matched: false };
                resultEl.style.display = '';
                resultEl.className = 'rw-id-result rw-id-result--error';
                document.getElementById('rw-id-result-title').textContent = 'No text detected';
                document.getElementById('rw-id-result-detail').textContent = 'The ID could not be scanned. Please upload a clearer, well-lit photo of your ID.';
                return;
            }

            var estimate = rwEstimateMatch(text);
            window.rwIdScanState = { ocrDone: true, matched: estimate.pass };
            resultEl.style.display = '';
            resultEl.className = estimate.pass ? 'rw-id-result rw-id-result--ok' : 'rw-id-result rw-id-result--error';
            document.getElementById('rw-id-result-title').textContent = estimate.pass ? 'Document appears consistent' : 'Document does not match';
            document.getElementById('rw-id-result-detail').textContent = estimate.detail + ' The system will re-check the document when you submit.';
        } catch (err) {
            statusEl.style.display = '';
            statusEl.className = 'rw-ocr-status rw-ocr-status--error';
            statusEl.textContent = 'Scanning failed. Please try again.';
            window.rwIdScanState = { ocrDone: false, matched: false };
        }
    };

    /* Initialize step 1 */
    showStep(1);

    /* PSGC Address API */
    async function loadProvinces() {
        var el = document.getElementById('address_province');
        if (!el || el.dataset.loaded === 'true') return;
        el.innerHTML = '<option value="">Loading provinces...</option>';
        try {
            var r = await fetch('<?= base_url('api/address/provinces') ?>');
            var j = await r.json();
            var data = j.data || j;
            el.innerHTML = '<option value="">Select Province</option>';
            data.sort(function (a, b) { return a.name.localeCompare(b.name); });
            data.forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = item.name;
                opt.dataset.code = item.code;
                opt.textContent = item.name;
                el.appendChild(opt);
            });
            el.dataset.loaded = 'true';
            var init = el.getAttribute('data-initial-province') || '';
            if (init) {
                el.value = init;
                var sel = el.options[el.selectedIndex];
                if (sel && sel.dataset.code) loadCities(sel.dataset.code, true);
            }
        } catch (e) { el.innerHTML = '<option value="">Unable to load</option>'; }
    }

    async function loadCities(code, preserve) {
        var cityEl = document.getElementById('address_city');
        var brgyEl = document.getElementById('address_barangay');
        var hiddenCode = document.getElementById('address_city_code');
        if (!cityEl || !code) return;
        try {
            var r = await fetch('<?= base_url('api/address/cities') ?>' + '/' + encodeURIComponent(code));
            var j = await r.json();
            var data = j.data || j;
            cityEl.innerHTML = '<option value="">Select Town/City</option>';
            data.sort(function (a, b) { return a.name.localeCompare(b.name); });
            data.forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = item.name;
                opt.dataset.code = item.code;
                opt.textContent = item.name;
                cityEl.appendChild(opt);
            });
            if (preserve) {
                var initCity = cityEl.getAttribute('data-initial-city') || '';
                if (initCity) {
                    cityEl.value = initCity;
                    var sel = cityEl.options[cityEl.selectedIndex];
                    if (sel && sel.dataset.code) loadBarangays(sel.dataset.code, true);
                }
            } else if (brgyEl) {
                brgyEl.innerHTML = '<option value="">Select Town/City first</option>';
            }
            if (hiddenCode) hiddenCode.value = code;
        } catch (e) { cityEl.innerHTML = '<option value="">Unable to load</option>'; }
    }

    async function loadBarangays(code, preserve) {
        var brgyEl = document.getElementById('address_barangay');
        if (!brgyEl || !code) return;
        try {
            var r = await fetch('<?= base_url('api/address/barangays') ?>' + '/' + encodeURIComponent(code));
            var j = await r.json();
            var data = j.data || j;
            brgyEl.innerHTML = '<option value="">Select Barangay</option>';
            data.sort(function (a, b) { return a.name.localeCompare(b.name); });
            data.forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = item.name;
                opt.textContent = item.name;
                brgyEl.appendChild(opt);
            });
            if (preserve) {
                var initBrgy = brgyEl.getAttribute('data-initial-barangay') || '';
                if (initBrgy) brgyEl.value = initBrgy;
            }
        } catch (e) { brgyEl.innerHTML = '<option value="">Unable to load</option>'; }
    }

    var provEl = document.getElementById('address_province');
    if (provEl) {
        provEl.addEventListener('click', loadProvinces);
        provEl.addEventListener('focus', loadProvinces);
        provEl.addEventListener('change', function () {
            var sel = provEl.options[provEl.selectedIndex];
            var code = (sel && sel.dataset.code) || '';
            var hidden = document.getElementById('address_province_code');
            if (code) { loadCities(code); if (hidden) hidden.value = code; }
            else {
                var c = document.getElementById('address_city');
                var b = document.getElementById('address_barangay');
                if (c) c.innerHTML = '<option value="">Select Province first</option>';
                if (b) b.innerHTML = '<option value="">Select Town/City first</option>';
            }
        });
    }

    var cityEl = document.getElementById('address_city');
    if (cityEl) {
        cityEl.addEventListener('change', function () {
            var sel = cityEl.options[cityEl.selectedIndex];
            var code = (sel && sel.dataset.code) || '';
            var hidden = document.getElementById('address_city_code');
            if (code) { loadBarangays(code); if (hidden) hidden.value = code; }
            else {
                var b = document.getElementById('address_barangay');
                if (b) b.innerHTML = '<option value="">Select Town/City first</option>';
            }
        });
    }

    /* Auto-calculate age */
    var dobEl = document.getElementById('date_of_birth');
    var ageEl = document.getElementById('age');
    if (dobEl && ageEl) {
        function calcAge() {
            var d = new Date(dobEl.value);
            if (isNaN(d)) { ageEl.value = ''; return; }
            var now = new Date();
            var a = now.getFullYear() - d.getFullYear();
            if (now.getMonth() < d.getMonth() || (now.getMonth() === d.getMonth() && now.getDate() < d.getDate())) a--;
            ageEl.value = a >= 0 ? a : '';
        }
        if (dobEl.value) calcAge();
        dobEl.addEventListener('change', calcAge);
    }
})();
</script>
<?= $this->endSection() ?>
