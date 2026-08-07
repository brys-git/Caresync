<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$beneficiaries = $beneficiaries ?? [];
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$planId = (int) ($plan_id ?? 0);
$applicationDate = (string) old('application_date', (string) ($plan_holder['application_date'] ?? date('Y-m-d')));
$civilStatus = (string) old('civil_status', (string) ($plan_holder['civil_status'] ?? ''));
$gender = (string) old('gender', (string) ($plan_holder['gender'] ?? ''));
?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></h1>
        <p class="text-muted mb-0">Plan registration form.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

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


    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Step-by-step registration</h2>
                    <p class="text-muted mb-0">Complete each section to submit the registration for verification.</p>
                </div>
                <div class="w-100 w-md-50">
                    <div class="progress" style="height: 8px;">
                        <div id="registration-progress" class="progress-bar" role="progressbar" style="width: 33%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                        <span class="step-pill text-primary" data-step-pill="1">1. Applicant</span>
                        <span class="step-pill" data-step-pill="2">2. Beneficiaries</span>
                        <span class="step-pill" data-step-pill="3">3. Verification</span>
                    </div>
                </div>
            </div>

            <form method="post" action="<?= base_url('plan-registration') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="plan_id" value="<?= $planId ?>">
                <input type="hidden" name="package_id" value="<?= $planId ?>">

                <div class="step-panel" data-step-panel="1">
                    <h3 class="h6 text-primary mb-3">Step 1 — Applicant Information</h3>
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
                            <input id="application_date" name="application_date" type="date" class="form-control" value="<?= esc($applicationDate) ?>" data-required-step-1>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input id="last_name" name="last_name" class="form-control" value="<?= esc(old('last_name', (string) ($user['last_name'] ?? ''))) ?>" required data-required-step-1>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="first_name">Given Name</label>
                            <input id="first_name" name="first_name" class="form-control" value="<?= esc(old('first_name', (string) ($user['first_name'] ?? ''))) ?>" required data-required-step-1>
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
                            <label class="form-label" for="address_province">Province</label>
                            <select id="address_province" name="address_province" class="form-select" required data-required-step-1 data-initial-province="<?= esc(old('address_province', (string) ($plan_holder['address_province'] ?? ''))) ?>">
                                <option value="">Select Province</option>
                            </select>
                            <input type="hidden" id="address_province_code" name="address_province_code" value="<?= esc(old('address_province_code', (string) ($plan_holder['address_province_code'] ?? ''))) ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="address_city">Town/City</label>
                            <select id="address_city" name="address_city" class="form-select" required data-required-step-1 data-initial-city="<?= esc(old('address_city', (string) ($plan_holder['address_city'] ?? ''))) ?>">
                                <option value="">Select Province first</option>
                            </select>
                            <input type="hidden" id="address_city_code" name="address_city_code" value="<?= esc(old('address_city_code', (string) ($plan_holder['address_city_code'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="address_barangay">Barangay</label>
                            <select id="address_barangay" name="address_barangay" class="form-select" required data-required-step-1 data-initial-barangay="<?= esc(old('address_barangay', (string) ($plan_holder['address_barangay'] ?? ''))) ?>">
                                <option value="">Select Town/City first</option>
                            </select>
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
                        <div class="col-md-2">
                            <label class="form-label" for="date_of_birth">Date of Birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="<?= esc(old('date_of_birth', (string) ($plan_holder['date_of_birth'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="place_of_birth">Place of Birth</label>
                            <input id="place_of_birth" name="place_of_birth" class="form-control" value="<?= esc(old('place_of_birth', (string) ($plan_holder['place_of_birth'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="citizenship">Citizenship</label>
                            <input id="citizenship" name="citizenship" class="form-control" value="<?= esc(old('citizenship', (string) ($plan_holder['citizenship'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="branch_id">Branch Office</label>
                            <select id="branch_id" name="branch_id" class="form-select" required data-required-step-1>
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
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="spouse_last_name">Last Name (Spouse)</label>
                            <input id="spouse_last_name" name="spouse_last_name" class="form-control" value="<?= esc(old('spouse_last_name', (string) ($plan_holder['spouse_last_name'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="spouse_first_name">Given Name (Spouse)</label>
                            <input id="spouse_first_name" name="spouse_first_name" class="form-control" value="<?= esc(old('spouse_first_name', (string) ($plan_holder['spouse_first_name'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="spouse_middle_name">Middle Name (Spouse)</label>
                            <input id="spouse_middle_name" name="spouse_middle_name" class="form-control" value="<?= esc(old('spouse_middle_name', (string) ($plan_holder['spouse_middle_name'] ?? ''))) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="spouse_birthdate">Date of Birth (Spouse)</label>
                            <input id="spouse_birthdate" name="spouse_birthdate" type="date" class="form-control" value="<?= esc(old('spouse_birthdate', (string) ($plan_holder['spouse_birthdate'] ?? ''))) ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="spouse_occupation">Occupation (Spouse)</label>
                            <input id="spouse_occupation" name="spouse_occupation" class="form-control" value="<?= esc(old('spouse_occupation', (string) ($plan_holder['spouse_occupation'] ?? ''))) ?>">
                        </div>
                    </div>

                    <h3 class="h6 text-primary mb-3">Contact Info</h3>
                    <div class="row g-3 mb-4">
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

                <div class="step-panel d-none" data-step-panel="2">
                    <h3 class="h6 text-primary mb-3">Step 2 — Beneficiary Section <span class="text-danger">*</span></h3>
                    <p class="text-muted small mb-3">Add at least one beneficiary. You can leave empty rows blank.</p>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Last Name</th>
                                    <th style="width: 15%;">Given Name</th>
                                    <th style="width: 15%;">Middle Name</th>
                                    <th style="width: 15%;">Birthday</th>
                                    <th style="width: 25%;">Relationship</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < 10; $i++): ?>
                                    <?php
                                    $beneficiary = $beneficiaries[$i] ?? [];
                                    ?>
                                    <tr>
                                        <td>
                                            <input
                                                class="form-control"
                                                name="beneficiaries[<?= $i ?>][last_name]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.last_name', (string) ($beneficiary['last_name'] ?? ''))) ?>"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                class="form-control"
                                                name="beneficiaries[<?= $i ?>][first_name]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.first_name', (string) ($beneficiary['first_name'] ?? ''))) ?>"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                class="form-control"
                                                name="beneficiaries[<?= $i ?>][middle_name]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.middle_name', (string) ($beneficiary['middle_name'] ?? ''))) ?>"
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
                                                class="form-control"
                                                name="beneficiaries[<?= $i ?>][relationship]"
                                                value="<?= esc(old('beneficiaries.' . $i . '.relationship', (string) ($beneficiary['relationship'] ?? ''))) ?>"
                                            >
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="step-panel d-none" data-step-panel="3">
                    <h3 class="h6 text-primary mb-3">Step 3 — Verification</h3>
                    <div class="alert alert-info">
                        Please upload a valid government-issued ID. The registration will be reviewed and the submitted name, birthday, and other personal details will be checked against the uploaded document.
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="valid_id">Upload Valid ID</label>
                            <input id="valid_id" name="valid_id" type="file" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted">Accepted formats: JPG, PNG, PDF.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Verification Note</label>
                            <div class="border rounded p-3 bg-light-subtle">
                                <p class="mb-2 small">The system will verify:</p>
                                <ul class="mb-0 small">
                                    <li>Full name</li>
                                    <li>Birthday</li>
                                    <li>Address and branch details</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <h3 class="h6 text-primary mb-3">Certification</h3>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="certify" name="certify" value="1" <?= old('certify') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="certify">
                            This is to certify that the above information is TRUE AND CORRECT to the best of my knowledge.
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button type="button" class="btn btn-outline-secondary prev-step d-none">Back</button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-outline-primary next-step">Next</button>
                        <button type="submit" class="btn btn-primary submit-step d-none">Submit Registration</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const stepPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
            const stepPills = Array.from(document.querySelectorAll('[data-step-pill]'));
            const progressBar = document.getElementById('registration-progress');
            const nextButton = document.querySelector('.next-step');
            const backButton = document.querySelector('.prev-step');
            const submitButton = document.querySelector('.submit-step');
            let currentStep = 1;

            function showStep(step) {
                currentStep = step;
                stepPanels.forEach(function (panel) {
                    const panelStep = Number(panel.getAttribute('data-step-panel'));
                    panel.classList.toggle('d-none', panelStep !== step);
                });

                stepPills.forEach(function (pill) {
                    const pillStep = Number(pill.getAttribute('data-step-pill'));
                    const isActive = pillStep === step;
                    pill.classList.toggle('text-primary', isActive);
                    pill.classList.toggle('fw-semibold', isActive);
                });

                const progressPercent = ((step - 1) / (stepPanels.length - 1)) * 100;
                if (progressBar) {
                    progressBar.style.width = progressPercent + '%';
                }

                if (backButton) {
                    backButton.classList.toggle('d-none', step === 1);
                }
                if (nextButton) {
                    nextButton.classList.toggle('d-none', step === stepPanels.length);
                }
                if (submitButton) {
                    submitButton.classList.toggle('d-none', step !== stepPanels.length);
                }
            }

            function validateStep(step) {
                if (step === 1) {
                    const requiredFields = document.querySelectorAll('[data-required-step-1]');
                    for (const field of requiredFields) {
                        if (!String(field.value || '').trim()) {
                            field.focus();
                            window.alert('Please complete all required fields in Step 1 before continuing.');
                            return false;
                        }
                    }
                    return true;
                }

                if (step === 2) {
                    const beneficiaryFields = Array.from(document.querySelectorAll('input[name^="beneficiaries["]'));
                    const hasEntry = beneficiaryFields.some(function (input) {
                        return String(input.value || '').trim() !== '';
                    });

                    if (!hasEntry) {
                        window.alert('Please add at least one beneficiary before continuing.');
                        return false;
                    }
                }

                return true;
            }

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    if (validateStep(currentStep) && currentStep < stepPanels.length) {
                        showStep(currentStep + 1);
                    }
                });
            }

            if (backButton) {
                backButton.addEventListener('click', function () {
                    if (currentStep > 1) {
                        showStep(currentStep - 1);
                    }
                });
            }

            showStep(1);

            function clearCityAndBarangay() {
                const citySelect = document.getElementById('address_city');
                const barangaySelect = document.getElementById('address_barangay');
                const cityCodeInput = document.getElementById('address_city_code');
                const provinceCodeInput = document.getElementById('address_province_code');

                if (citySelect) {
                    citySelect.innerHTML = '<option value="">Select Province first</option>';
                }
                if (barangaySelect) {
                    barangaySelect.innerHTML = '<option value="">Select Town/City first</option>';
                }
                if (cityCodeInput) {
                    cityCodeInput.value = '';
                }
                if (provinceCodeInput) {
                    provinceCodeInput.value = '';
                }
            }

            async function loadProvinces() {
                const provinceSelect = document.getElementById('address_province');
                const citySelect = document.getElementById('address_city');
                const barangaySelect = document.getElementById('address_barangay');

                if (!provinceSelect) {
                    return;
                }

                if (provinceSelect.dataset.loaded === 'true') {
                    return;
                }

                provinceSelect.innerHTML = '<option value="">Loading provinces...</option>';

                try {
                    const response = await fetch('https://psgc.cloud/api/v2/provinces');
                    const json = await response.json();
                    const data = json.data || json;

                    provinceSelect.innerHTML = '<option value="">Select Province</option>';
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    data.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.name;
                        option.dataset.code = item.code;
                        option.textContent = item.name;
                        provinceSelect.appendChild(option);
                    });

                    provinceSelect.dataset.loaded = 'true';

                    const initialProvince = provinceSelect.getAttribute('data-initial-province') || '';
                    if (initialProvince) {
                        const provinceOption = Array.from(provinceSelect.options).find(function (option) {
                            return option.value === initialProvince;
                        });
                        if (provinceOption) {
                            provinceSelect.value = initialProvince;
                            const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
                            if (selectedOption?.dataset.code) {
                                await loadCities(selectedOption.dataset.code, true);
                            }
                        }
                    } else {
                        if (citySelect) {
                            citySelect.innerHTML = '<option value="">Select Province first</option>';
                        }
                        if (barangaySelect) {
                            barangaySelect.innerHTML = '<option value="">Select Town/City first</option>';
                        }
                    }
                } catch (error) {
                    console.error('Unable to load provinces:', error);
                    provinceSelect.innerHTML = '<option value="">Unable to load provinces</option>';
                }
            }

            async function loadCities(provinceCode, preserveSelection = false) {
                const citySelect = document.getElementById('address_city');
                const barangaySelect = document.getElementById('address_barangay');
                const hiddenCityCode = document.getElementById('address_city_code');

                if (!citySelect || !provinceCode) {
                    return;
                }

                try {
                    const response = await fetch('https://psgc.cloud/api/v2/provinces/' + encodeURIComponent(provinceCode) + '/cities-municipalities');
                    const json = await response.json();
                    const data = json.data || json;

                    citySelect.innerHTML = '<option value="">Select Town/City</option>';
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    data.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.name;
                        option.dataset.code = item.code;
                        option.textContent = item.name;
                        citySelect.appendChild(option);
                    });

                    const initialCity = citySelect.getAttribute('data-initial-city') || '';
                    if (preserveSelection && initialCity) {
                        const cityOption = Array.from(citySelect.options).find(function (option) {
                            return option.value === initialCity;
                        });
                        if (cityOption) {
                            citySelect.value = initialCity;
                            const selectedOption = citySelect.options[citySelect.selectedIndex];
                            if (selectedOption?.dataset.code) {
                                await loadBarangays(selectedOption.dataset.code, true);
                            }
                        }
                    } else {
                        if (barangaySelect) {
                            barangaySelect.innerHTML = '<option value="">Select Town/City first</option>';
                        }
                    }

                    if (hiddenCityCode) {
                        hiddenCityCode.value = provinceCode;
                    }
                } catch (error) {
                    console.error('Unable to load cities:', error);
                    citySelect.innerHTML = '<option value="">Unable to load cities</option>';
                    if (barangaySelect) {
                        barangaySelect.innerHTML = '<option value="">Unable to load barangays</option>';
                    }
                }
            }

            async function loadBarangays(cityCode, preserveSelection = false) {
                const barangaySelect = document.getElementById('address_barangay');

                if (!barangaySelect || !cityCode) {
                    return;
                }

                try {
                    const response = await fetch('https://psgc.cloud/api/v2/cities-municipalities/' + encodeURIComponent(cityCode) + '/barangays');
                    const json = await response.json();
                    const data = json.data || json;

                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    data.forEach(function (item) {
                        const option = document.createElement('option');
                        option.value = item.name;
                        option.textContent = item.name;
                        barangaySelect.appendChild(option);
                    });

                    const initialBarangay = barangaySelect.getAttribute('data-initial-barangay') || '';
                    if (preserveSelection && initialBarangay) {
                        const barangayOption = Array.from(barangaySelect.options).find(function (option) {
                            return option.value === initialBarangay;
                        });
                        if (barangayOption) {
                            barangaySelect.value = initialBarangay;
                        }
                    }
                } catch (error) {
                    console.error('Unable to load barangays:', error);
                    barangaySelect.innerHTML = '<option value="">Unable to load barangays</option>';
                }
            }

            const provinceField = document.getElementById('address_province');
            if (provinceField) {
                provinceField.addEventListener('click', function () {
                    loadProvinces();
                });
                provinceField.addEventListener('focus', function () {
                        });
                provinceField.addEventListener('change', function () {
                    const selectedOption = provinceField.options[provinceField.selectedIndex];
                    const provinceCode = selectedOption?.dataset.code || '';
                    const hiddenCode = document.getElementById('address_province_code');
                    if (provinceCode) {
                        loadCities(provinceCode);
                        if (hiddenCode) hiddenCode.value = provinceCode;
                    } else {
                        clearCityAndBarangay();
                    }
                });
            }

            const cityField = document.getElementById('address_city');
            if (cityField) {
                cityField.addEventListener('change', function () {
                    const selectedOption = cityField.options[cityField.selectedIndex];
                    const cityCode = selectedOption?.dataset.code || '';
                    const hiddenCode = document.getElementById('address_city_code');
                    if (cityCode) {
                        loadBarangays(cityCode);
                        if (hiddenCode) hiddenCode.value = cityCode;
                    } else {
                        const barangaySelect = document.getElementById('address_barangay');
                        if (barangaySelect) {
                            barangaySelect.innerHTML = '<option value="">Select Town/City first</option>';
                        }
                    }
                });
            }

            // Auto-calculate age from date of birth
            function calculateAge(dateOfBirthStr) {
                if (!dateOfBirthStr) {
                    return null;
                }

                const birthDate = new Date(dateOfBirthStr);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }

                return age >= 0 ? age : null;
            }

            const dateOfBirthField = document.getElementById('date_of_birth');
            const ageField = document.getElementById('age');

            if (dateOfBirthField) {
                // Calculate age on page load if date of birth exists
                if (dateOfBirthField.value) {
                    const calculatedAge = calculateAge(dateOfBirthField.value);
                    if (calculatedAge !== null && ageField) {
                        ageField.value = calculatedAge;
                    }
                }

                // Calculate age when date of birth changes
                dateOfBirthField.addEventListener('change', function () {
                    const calculatedAge = calculateAge(this.value);
                    if (calculatedAge !== null && ageField) {
                        ageField.value = calculatedAge;
                    } else if (ageField) {
                        ageField.value = '';
                    }
                });
            }

        });
    </script>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
