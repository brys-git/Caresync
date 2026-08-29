<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Edit Client</h1>
        <a href="<?= base_url('admin/client-management/view/' . $client['plan_holder_id']) ?>" class="btn btn-outline-secondary btn-sm">Back to Details</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= base_url('admin/client-management/update/' . $client['plan_holder_id']) ?>">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Client ID</label>
                        <input type="text" class="form-control" value="<?= esc((string) $client['plan_holder_id']) ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="<?= esc((string) ($client['branch_name'] ?? 'N/A')) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unique Identifier</label>
                        <input type="text" class="form-control" value="<?= esc((string) ($client['unique_identifier'] ?? '')) ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="first_name">First Name</label>
                        <input id="first_name" name="first_name" type="text" class="form-control" value="<?= esc(old('first_name', (string) ($client['first_name'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input id="last_name" name="last_name" type="text" class="form-control" value="<?= esc(old('last_name', (string) ($client['last_name'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-control" value="<?= esc(old('email', (string) ($client['email'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input id="contact_number" name="contact_number" type="text" class="form-control" value="<?= esc(old('contact_number', (string) ($client['contact_number'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" for="address_no">Address No</label>
                        <input id="address_no" name="address_no" type="text" class="form-control" value="<?= esc(old('address_no', (string) ($client['address_no'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_street">Street</label>
                        <input id="address_street" name="address_street" type="text" class="form-control" value="<?= esc(old('address_street', (string) ($client['address_street'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="address_barangay">Barangay</label>
                        <input id="address_barangay" name="address_barangay" type="text" class="form-control" value="<?= esc(old('address_barangay', (string) ($client['address_barangay'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="address_city">City</label>
                        <input id="address_city" name="address_city" type="text" class="form-control" value="<?= esc(old('address_city', (string) ($client['address_city'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="date_of_birth">Birthdate</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="<?= esc(old('date_of_birth', (string) ($client['date_of_birth'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="gender">Gender</label>
                        <input id="gender" name="gender" type="text" class="form-control" value="<?= esc(old('gender', (string) ($client['gender'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="civil_status">Civil Status</label>
                        <input id="civil_status" name="civil_status" type="text" class="form-control" value="<?= esc(old('civil_status', (string) ($client['civil_status'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="citizenship">Citizenship</label>
                        <input id="citizenship" name="citizenship" type="text" class="form-control" value="<?= esc(old('citizenship', (string) ($client['citizenship'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="place_of_birth">Place of Birth</label>
                        <input id="place_of_birth" name="place_of_birth" type="text" class="form-control" value="<?= esc(old('place_of_birth', (string) ($client['place_of_birth'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="age">Age</label>
                        <input id="age" name="age" type="number" class="form-control" value="<?= esc(old('age', (string) ($client['age'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="height">Height</label>
                        <input id="height" name="height" type="number" step="0.01" class="form-control" value="<?= esc(old('height', (string) ($client['height'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="weight">Weight</label>
                        <input id="weight" name="weight" type="number" step="0.01" class="form-control" value="<?= esc(old('weight', (string) ($client['weight'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="spouse_name">Spouse Name</label>
                        <input id="spouse_name" name="spouse_name" type="text" class="form-control" value="<?= esc(old('spouse_name', (string) ($client['spouse_name'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="spouse_birthdate">Spouse Birthdate</label>
                        <input id="spouse_birthdate" name="spouse_birthdate" type="date" class="form-control" value="<?= esc(old('spouse_birthdate', (string) ($client['spouse_birthdate'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="spouse_occupation">Spouse Occupation</label>
                        <input id="spouse_occupation" name="spouse_occupation" type="text" class="form-control" value="<?= esc(old('spouse_occupation', (string) ($client['spouse_occupation'] ?? ''))) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="senior_citizen_id">Senior Citizen ID</label>
                        <input id="senior_citizen_id" name="senior_citizen_id" type="text" class="form-control" value="<?= esc(old('senior_citizen_id', (string) ($client['senior_citizen_id'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="organization_affiliation">Organization</label>
                        <input id="organization_affiliation" name="organization_affiliation" type="text" class="form-control" value="<?= esc(old('organization_affiliation', (string) ($client['organization_affiliation'] ?? ''))) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active" <?= old('status', (string) ($client['plan_holder_status'] ?? 'active')) === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status', (string) ($client['plan_holder_status'] ?? 'active')) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4">Update Client</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
