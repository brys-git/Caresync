<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h4 mb-3">Plan Holder Registration</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php
        $activeTab = $active_tab ?? 'registration';
        $canReviewApprovals = (bool) ($can_review_approvals ?? false);
        $hasPendingTable = (bool) ($has_pending_table ?? false);
        $approvalRegistrations = $approval_registrations ?? [];
    ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="<?= base_url('plan-holders/register?tab=registration') ?>" class="btn <?= $activeTab === 'registration' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">Registration</a>
        <?php if ($canReviewApprovals): ?>
            <a href="<?= base_url('plan-holders/register?tab=approvals') ?>" class="btn <?= $activeTab === 'approvals' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">Approvals</a>
        <?php endif; ?>
    </div>

    <div class="card <?= $activeTab === 'registration' ? '' : 'd-none' ?>" id="registration-tab-panel">
        <div class="card-body">
            <form method="post" action="<?= base_url('plan-holders/store') ?>">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label d-block">Client Account Status</label>
                        <?php $registrationMode = old('registration_mode', 'existing'); ?>
                        <div class="border rounded p-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="registration_mode" id="registration_mode_existing" value="existing" <?= $registrationMode === 'existing' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="registration_mode_existing">
                                    Client already has an account
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode" id="registration_mode_new" value="new" <?= $registrationMode === 'new' ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="registration_mode_new">
                                    Client does not have an account yet
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select class="form-select" id="branch_id" name="branch_id" required>
                            <option value="">Select branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= esc((string) $branch['branch_id']) ?>" <?= old('branch_id') == $branch['branch_id'] ? 'selected' : '' ?>>
                                    <?= esc($branch['branch_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 existing-user-fields">
                        <label class="form-label" for="existing_user_email">Client Email (Existing Account)</label>
                        <input
                            id="existing_user_email"
                            name="existing_user_email"
                            type="email"
                            class="form-control"
                            value="<?= old('existing_user_email') ?>"
                            placeholder="Enter client email"
                            autocomplete="off"
                        >
                        <input type="hidden" id="user_id" name="user_id" value="<?= esc((string) old('user_id')) ?>">
                        <small class="text-muted" id="existing_user_help">Enter the email of an existing client account.</small>
                    </div>

                    <div class="col-12 existing-user-fields">
                        <div class="border rounded p-3 bg-light-subtle">
                            <h2 class="h6 mb-3">Auto-filled Account Details</h2>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="existing_username">Username</label>
                                    <input id="existing_username" type="text" class="form-control" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="existing_first_name">First Name</label>
                                    <input id="existing_first_name" type="text" class="form-control" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="existing_last_name">Last Name</label>
                                    <input id="existing_last_name" type="text" class="form-control" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="existing_contact_number">Contact Number</label>
                                    <input id="existing_contact_number" type="text" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 new-user-fields d-none">
                        <div class="border rounded p-3">
                            <h2 class="h6">New User Account</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="username">Username</label>
                                    <input id="username" name="username" type="text" class="form-control" value="<?= old('username') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input id="email" name="email" type="email" class="form-control" value="<?= old('email') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="first_name">First Name</label>
                                    <input id="first_name" name="first_name" type="text" class="form-control" value="<?= old('first_name') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="last_name">Last Name</label>
                                    <input id="last_name" name="last_name" type="text" class="form-control" value="<?= old('last_name') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="contact_number">Contact Number</label>
                                    <input id="contact_number" name="contact_number" type="text" class="form-control" value="<?= old('contact_number') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="password">Password</label>
                                    <input id="password" name="password" type="password" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="password_confirm">Confirm Password</label>
                                    <input id="password_confirm" name="password_confirm" type="password" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="account_status">Account Status</label>
                                    <select class="form-select" id="account_status" name="account_status">
                                        <option value="pending" <?= old('account_status', 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="verified" <?= old('account_status') === 'verified' ? 'selected' : '' ?>>Verified</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="unique_identifier">Unique Identifier</label>
                        <input id="unique_identifier" name="unique_identifier" type="text" class="form-control" value="<?= old('unique_identifier') ?>" required>
                        <small class="text-muted">Required for safe client matching across modules.</small>
                    </div>
                    <input type="hidden" id="status" name="status" value="inactive">

                    <div class="col-md-3">
                        <label class="form-label" for="age">Age</label>
                        <input id="age" name="age" type="number" min="0" class="form-control" value="<?= old('age') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="gender">Gender</label>
                        <input id="gender" name="gender" type="text" class="form-control" value="<?= old('gender') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="civil_status">Civil Status</label>
                        <input id="civil_status" name="civil_status" type="text" class="form-control" value="<?= old('civil_status') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="citizenship">Citizenship</label>
                        <input id="citizenship" name="citizenship" type="text" class="form-control" value="<?= old('citizenship') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="height">Height</label>
                        <input id="height" name="height" type="number" step="0.01" class="form-control" value="<?= old('height') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="weight">Weight</label>
                        <input id="weight" name="weight" type="number" step="0.01" class="form-control" value="<?= old('weight') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="place_of_birth">Place of Birth</label>
                        <input id="place_of_birth" name="place_of_birth" type="text" class="form-control" value="<?= old('place_of_birth') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" class="form-control" value="<?= old('date_of_birth') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="senior_citizen_id">Senior Citizen ID</label>
                        <input id="senior_citizen_id" name="senior_citizen_id" type="text" class="form-control" value="<?= old('senior_citizen_id') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" for="address_no">Address No</label>
                        <input id="address_no" name="address_no" type="text" class="form-control" value="<?= old('address_no') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="address_street">Street</label>
                        <input id="address_street" name="address_street" type="text" class="form-control" value="<?= old('address_street') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="address_barangay">Barangay</label>
                        <input id="address_barangay" name="address_barangay" type="text" class="form-control" value="<?= old('address_barangay') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="address_city">City</label>
                        <input id="address_city" name="address_city" type="text" class="form-control" value="<?= old('address_city') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="organization_affiliation">Organization Affiliation</label>
                        <input id="organization_affiliation" name="organization_affiliation" type="text" class="form-control" value="<?= old('organization_affiliation') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4">Save Plan Holder</button>
            </form>
        </div>
    </div>

    <?php if ($canReviewApprovals): ?>
        <div class="card <?= $activeTab === 'approvals' ? '' : 'd-none' ?>" id="approvals-tab-panel">
            <div class="card-body">
                <h2 class="h5 mb-3">Plan Holder Verification Queue</h2>

                <?php if (! $hasPendingTable): ?>
                    <div class="alert alert-warning mb-0">Pending registration queue is unavailable. Run database migrations first.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Reviewed By</th>
                                    <th style="min-width: 280px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approvalRegistrations as $registration): ?>
                                    <?php
                                        $status = (string) ($registration['status'] ?? 'pending');
                                        $badgeClass = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                        $reviewerName = trim((string) ($registration['reviewer_first_name'] ?? '') . ' ' . (string) ($registration['reviewer_last_name'] ?? ''));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($registration['first_name'] ?? '')) ?> <?= esc((string) ($registration['last_name'] ?? '')) ?></div>
                                            <small class="text-muted"><?= esc((string) ($registration['email'] ?? '-')) ?></small>
                                        </td>
                                        <td><?= esc((string) ($registration['branch_name'] ?? '-')) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= esc($badgeClass) ?> text-uppercase"><?= esc($status) ?></span>
                                            <?php if ($status === 'rejected' && ! empty($registration['rejection_notes'])): ?>
                                                <div class="small text-danger mt-1">Reason: <?= esc((string) $registration['rejection_notes']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc((string) ($registration['created_at'] ?? '-')) ?></td>
                                        <td>
                                            <?= esc($reviewerName !== '' ? $reviewerName : '-') ?>
                                            <?php if (! empty($registration['reviewed_at'])): ?>
                                                <div class="small text-muted"><?= esc((string) $registration['reviewed_at']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status === 'pending'): ?>
                                                <div class="d-flex flex-column gap-2">
                                                    <form method="post" action="<?= base_url('plan-holders/approvals/approve/' . (int) $registration['pending_registration_id']) ?>" class="m-0">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-success btn-sm w-100">Approve</button>
                                                    </form>
                                                    <form method="post" action="<?= base_url('plan-holders/approvals/reject/' . (int) $registration['pending_registration_id']) ?>" class="m-0">
                                                        <?= csrf_field() ?>
                                                        <textarea name="rejection_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional rejection reason"></textarea>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Already reviewed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($approvalRegistrations)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No verification requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        const modeExisting = document.getElementById('registration_mode_existing');
        const modeNew = document.getElementById('registration_mode_new');
        const existingBlocks = document.querySelectorAll('.existing-user-fields');
        const newBlock = document.querySelector('.new-user-fields');
        const existingEmail = document.getElementById('existing_user_email');
        const existingUserId = document.getElementById('user_id');
        const existingUserHelp = document.getElementById('existing_user_help');
        const existingUsername = document.getElementById('existing_username');
        const existingFirstName = document.getElementById('existing_first_name');
        const existingLastName = document.getElementById('existing_last_name');
        const existingContactNumber = document.getElementById('existing_contact_number');

        const existingUsers = <?= json_encode(array_values(array_map(static function ($user) {
            return [
                'user_id' => (int) ($user['user_id'] ?? 0),
                'username' => (string) ($user['username'] ?? ''),
                'email' => strtolower(trim((string) ($user['email'] ?? ''))),
                'first_name' => (string) ($user['first_name'] ?? ''),
                'last_name' => (string) ($user['last_name'] ?? ''),
                'contact_number' => (string) ($user['contact_number'] ?? ''),
            ];
        }, $existing_users ?? []))) ?>;

        function getMode() {
            return modeNew.checked ? 'new' : 'existing';
        }

        function clearExistingPreview() {
            existingUsername.value = '';
            existingFirstName.value = '';
            existingLastName.value = '';
            existingContactNumber.value = '';
        }

        function syncExistingUserFromEmail() {
            const emailValue = (existingEmail.value || '').trim().toLowerCase();
            if (emailValue === '') {
                existingUserId.value = '';
                clearExistingPreview();
                existingUserHelp.textContent = 'Enter the email of an existing client account.';
                return;
            }

            const matched = existingUsers.find((user) => user.email === emailValue);
            if (!matched) {
                existingUserId.value = '';
                clearExistingPreview();
                existingUserHelp.textContent = 'No matching eligible account found for this email.';
                return;
            }

            existingUserId.value = String(matched.user_id);
            existingUsername.value = matched.username;
            existingFirstName.value = matched.first_name;
            existingLastName.value = matched.last_name;
            existingContactNumber.value = matched.contact_number;
            existingUserHelp.textContent = 'Account matched and ready for linking.';
        }

        function syncMode() {
            const isNew = getMode() === 'new';
            existingBlocks.forEach((block) => block.classList.toggle('d-none', isNew));
            newBlock.classList.toggle('d-none', !isNew);

            existingEmail.required = !isNew;
            existingUserId.required = !isNew;

            document.querySelectorAll('.new-user-fields input, .new-user-fields select').forEach((field) => {
                if (field.name === 'account_status') {
                    field.required = isNew;
                    return;
                }

                if (['username', 'email', 'first_name', 'last_name', 'password', 'password_confirm'].includes(field.name)) {
                    field.required = isNew;
                }
            });

            if (isNew) {
                existingUserHelp.textContent = 'Switch back to existing account mode to link by email.';
            } else {
                syncExistingUserFromEmail();
            }
        }

        modeExisting.addEventListener('change', syncMode);
        modeNew.addEventListener('change', syncMode);
        existingEmail.addEventListener('input', syncExistingUserFromEmail);
        existingEmail.addEventListener('change', syncExistingUserFromEmail);

        syncMode();
        syncExistingUserFromEmail();
    })();
</script>
<?= $this->endSection() ?>
