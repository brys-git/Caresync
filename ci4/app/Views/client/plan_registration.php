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
            <form method="post" action="<?= base_url('plan-registration') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="plan_id" value="<?= $planId ?>">
                <input type="hidden" name="package_id" value="<?= $planId ?>">

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
                        <label class="form-label" for="address_barangay">Barangay</label>
                        <input id="address_barangay" name="address_barangay" class="form-control" value="<?= esc(old('address_barangay', (string) ($plan_holder['address_barangay'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="address_city">Town/City</label>
                        <input id="address_city" name="address_city" class="form-control" value="<?= esc(old('address_city', (string) ($plan_holder['address_city'] ?? ''))) ?>" required>
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

                <h3 class="h6 text-primary mb-3">Beneficiary Section <span class="text-danger">*</span></h3>
                <p class="text-muted small mb-3">Add at least one beneficiary. You can leave empty rows blank.</p>
                <div class="table-responsive mb-4">
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
                                            class="form-control"
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

                <h3 class="h6 text-primary mb-3">Emergency Contact</h3>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" for="emergency_contact_name">Name</label>
                        <input id="emergency_contact_name" name="emergency_contact_name" class="form-control" value="<?= esc(old('emergency_contact_name', (string) ($plan_holder['emergency_contact_name'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="emergency_contact_number">Contact #</label>
                        <input id="emergency_contact_number" name="emergency_contact_number" class="form-control" value="<?= esc(old('emergency_contact_number', (string) ($plan_holder['emergency_contact_number'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="emergency_contact_address">Address</label>
                        <input id="emergency_contact_address" name="emergency_contact_address" class="form-control" value="<?= esc(old('emergency_contact_address', (string) ($plan_holder['emergency_contact_address'] ?? ''))) ?>">
                    </div>
                </div>

                <h3 class="h6 text-primary mb-3">Certification</h3>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="certify" name="certify" value="1" <?= old('certify') ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="certify">
                        This is to certify that the above information is TRUE AND CORRECT to the best of my knowledge.
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Submit Registration</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
