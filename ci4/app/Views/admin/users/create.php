<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
// Roles: id => label. Order matters only for the dropdown.
$roles       = $roles ?? [];
$idRequired  = array_map('intval', $id_required ?? []);
$branchRoles = array_map('intval', $branch_roles ?? []);
$idTypes     = $id_types ?? [];
$oldRole     = (string) old('role_id');
$oldMode     = (string) old('password_mode', 'generate');
?>
<div class="cs-wizard">
    <ol class="wizard-progress mb-4">
        <li class="wizard-progress__item is-active" data-progress-step="1"><span class="wizard-progress__badge">1</span><span class="wizard-progress__label">Personal Info</span></li>
        <li class="wizard-progress__item" data-progress-step="2"><span class="wizard-progress__badge">2</span><span class="wizard-progress__label">Government ID</span></li>
        <li class="wizard-progress__item" data-progress-step="3"><span class="wizard-progress__badge">3</span><span class="wizard-progress__label">Account &amp; Role</span></li>
        <li class="wizard-progress__item" data-progress-step="4"><span class="wizard-progress__badge">4</span><span class="wizard-progress__label">Review</span></li>
    </ol>

    <form method="post" action="<?= base_url('admin/users/create') ?>" id="createUserForm" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="government_id_pending_token" id="government_id_pending_token" value="<?= esc((string) ($keep_token ?? ''), 'attr') ?>">

        <!-- ============ STEP 1: Personal information ============ -->
        <div class="wizard-panel" data-step-panel="1">
            <div class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Personal Information</h2></div>
                <div class="cs-panel__body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" maxlength="50" value="<?= esc((string) old('first_name'), 'attr') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="middle_name">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" maxlength="50" value="<?= esc((string) old('middle_name'), 'attr') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" maxlength="50" value="<?= esc((string) old('last_name'), 'attr') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" minlength="4" maxlength="50" value="<?= esc((string) old('username'), 'attr') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= esc((string) old('email'), 'attr') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" maxlength="20" value="<?= esc((string) old('contact_number'), 'attr') ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-nav">
                <span></span>
                <button type="button" class="btn btn-primary wizard-next">Next: Government ID <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 2: Government ID ============ -->
        <div class="wizard-panel d-none" data-step-panel="2">
            <div class="cs-panel">
                <div class="cs-panel__head">
                    <h2 class="cs-panel__title">Government ID Verification</h2>
                    <p class="cs-panel__note">Upload or take a photo of the new account holder's valid government-issued ID so it can be confirmed against the name entered.
                        <strong id="idRequirement">Required for Branch Admin, Staff, Collector and System Admin accounts; optional for a Client.</strong></p>
                </div>
                <div class="cs-panel__body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label" for="id_type">Government ID Type</label>
                            <select id="id_type" class="form-select">
                                <option value="">Select ID type</option>
                                <?php foreach ($idTypes as $value => $label): ?>
                                    <option value="<?= esc((string) $value, 'attr') ?>" <?= ($keep_id_type ?? '') === (string) $value ? 'selected' : '' ?>><?= esc((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-ghost btn-sm" id="btnChooseUpload"><i class="ti ti-upload me-1"></i>Upload Government ID</button>
                        <button type="button" class="btn btn-ghost btn-sm" id="btnChooseCamera"><i class="ti ti-camera me-1"></i>Take Photo</button>
                        <input type="file" id="idFileInput" accept="image/jpeg,image/png,image/webp" class="d-none">
                    </div>

                    <div id="cameraPanel" class="d-none mb-3">
                        <div class="id-verify-camera-frame mb-2"><video id="cameraVideo" autoplay playsinline muted></video></div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm" id="btnCapture"><i class="ti ti-camera me-1"></i>Capture</button>
                            <button type="button" class="btn btn-ghost btn-sm" id="btnCancelCamera">Cancel</button>
                        </div>
                        <p class="text-danger small mt-2 d-none" id="cameraError"></p>
                    </div>

                    <div id="idPreviewWrap" class="d-none mb-3">
                        <img id="idPreviewImg" alt="ID preview" class="id-verify-preview">
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-ghost btn-sm" id="btnRetake"><i class="ti ti-refresh me-1"></i>Retake / Choose Another</button>
                        </div>
                    </div>

                    <canvas id="captureCanvas" class="d-none"></canvas>

                    <p class="text-muted small mb-2">Verification runs automatically once an ID image and type are selected. Use this only to retry.</p>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button type="button" class="btn btn-ghost btn-sm" id="btnVerifyId" disabled>Re-check ID</button>
                        <span class="text-muted small" id="verifyHint">Choose or capture an image first.</span>
                    </div>

                    <div id="verificationResult" class="d-none"></div>
                </div>
            </div>
            <div class="wizard-nav">
                <button type="button" class="btn btn-ghost wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="button" class="btn btn-primary wizard-next">Next: Account &amp; Role <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 3: Account & role ============ -->
        <div class="wizard-panel d-none" data-step-panel="3">
            <div class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Account &amp; Role</h2></div>
                <div class="cs-panel__body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="role_id">Role</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select a role</option>
                                <?php foreach ($roles as $rid => $label): ?>
                                    <option value="<?= (int) $rid ?>" <?= $oldRole === (string) $rid ? 'selected' : '' ?>><?= esc((string) $label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Collector: field collector account only. To let a Staff member also collect, choose Staff and tick "Can collect payments".</div>
                        </div>

                        <div class="col-md-6" id="branchWrap">
                            <label class="form-label" for="branch_id">Branch <span class="text-muted small" id="branchHint"></span></label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Select a branch</option>
                                <?php foreach (($branches ?? []) as $b): ?>
                                    <option value="<?= (int) $b['branch_id'] ?>" <?= (string) old('branch_id') === (string) $b['branch_id'] ? 'selected' : '' ?>><?= esc((string) $b['branch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 d-none" id="uniqueWrap">
                            <label class="form-label" for="unique_identifier">Unique Identifier <span class="text-muted small">(optional)</span></label>
                            <input type="text" class="form-control" id="unique_identifier" name="unique_identifier" maxlength="100" value="<?= esc((string) old('unique_identifier'), 'attr') ?>">
                            <div class="form-text">Type the plan holder's existing unique identifier to link this account to their membership.</div>
                        </div>

                        <div class="col-12 d-none" id="collectorWrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_collector" name="is_collector" value="1" <?= (string) old('is_collector') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_collector">Can collect payments</label>
                            </div>
                            <div class="form-text">Lets this Staff member use the Collection List and record field payments.</div>
                        </div>

                        <div class="col-12 d-none" id="adminPwWrap">
                            <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'message' => 'A System Admin can see and change everything in the system. Enter your own password to confirm.']) ?>
                            <label class="form-label" for="admin_password">Your password</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" autocomplete="current-password">
                        </div>

                        <div class="col-12">
                            <span class="form-label d-block">Password</span>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_mode" id="pm_generate" value="generate" <?= $oldMode !== 'manual' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pm_generate">Generate a temporary password <span class="text-muted small">(recommended)</span></label>
                            </div>
                            <div class="form-text ms-4 mb-2">Valid for 7 days, shown once on the next screen and emailed to the user. They must choose a new password at first sign-in.</div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_mode" id="pm_manual" value="manual" <?= $oldMode === 'manual' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pm_manual">Set a password myself</label>
                            </div>
                        </div>

                        <div class="col-12 d-none" id="manualWrap">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="password">Password <span class="text-muted small">(at least 8 characters)</span></label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="password_confirm">Confirm Password</label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" checked>
                                        <label class="form-check-label" for="must_change_password">Require a new password at first sign-in</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="wizard-nav">
                <button type="button" class="btn btn-ghost wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="button" class="btn btn-primary wizard-next">Next: Review <i class="ti ti-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- ============ STEP 4: Review ============ -->
        <div class="wizard-panel d-none" data-step-panel="4">
            <div class="cs-panel mb-3">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Personal Information</h2><button type="button" class="btn btn-ghost btn-sm wizard-edit" data-goto-step="1">Edit</button></div>
                <div class="cs-panel__body small" id="reviewPersonal"></div>
            </div>
            <div class="cs-panel mb-3">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Government ID Verification</h2><button type="button" class="btn btn-ghost btn-sm wizard-edit" data-goto-step="2">Edit</button></div>
                <div class="cs-panel__body small" id="reviewIdVerification"></div>
            </div>
            <div class="cs-panel mb-4">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Account &amp; Role</h2><button type="button" class="btn btn-ghost btn-sm wizard-edit" data-goto-step="3">Edit</button></div>
                <div class="cs-panel__body small" id="reviewAccount"></div>
            </div>
            <div class="wizard-nav">
                <button type="button" class="btn btn-ghost wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </div>
    </form>
</div>

<script src="<?= base_url('assets/js/id-verification-widget.js') ?>"></script>
<script>
(function () {
    const ID_REQUIRED = <?= json_encode($idRequired) ?>;
    const BRANCH_ROLES = <?= json_encode($branchRoles) ?>;
    const ROLE_ADMIN = 1, ROLE_STAFF = 3, ROLE_CLIENT = 4;
    const TOTAL_STEPS = 4;
    let currentStep = 1;

    const panels = Array.from(document.querySelectorAll('.wizard-panel'));
    const progressItems = Array.from(document.querySelectorAll('.wizard-progress__item'));
    const $ = function (id) { return document.getElementById(id); };

    function roleId() { return parseInt($('role_id').value, 10) || 0; }
    function manualMode() { return $('pm_manual').checked; }
    function show(el, on) { el.classList.toggle('d-none', !on); }

    // The role decides which fields are shown and which are needed.
    function applyRole() {
        const r = roleId();
        const branchNeeded = BRANCH_ROLES.indexOf(r) !== -1;
        show($('branchWrap'), r !== ROLE_ADMIN);
        $('branch_id').required = branchNeeded;
        $('branchHint').textContent = branchNeeded ? '(required)' : (r === ROLE_CLIENT ? '(optional)' : '');
        $('branch_id').options[0].text = branchNeeded ? 'Select a branch' : 'No branch';
        show($('uniqueWrap'), r === ROLE_CLIENT);
        show($('collectorWrap'), r === ROLE_STAFF);
        show($('adminPwWrap'), r === ROLE_ADMIN);
        $('admin_password').required = r === ROLE_ADMIN;
        const idNeeded = ID_REQUIRED.indexOf(r) !== -1;
        $('idRequirement').textContent = r === 0
            ? 'Required for Branch Admin, Staff, Collector and System Admin accounts; optional for a Client.'
            : (idNeeded ? 'Required for this role.' : 'Optional for a Client.');
    }

    function applyPasswordMode() {
        const manual = manualMode();
        show($('manualWrap'), manual);
        $('password').required = manual;
        $('password_confirm').required = manual;
    }

    function showStep(step) {
        currentStep = Math.min(Math.max(step, 1), TOTAL_STEPS);
        panels.forEach(function (p) { p.classList.toggle('d-none', parseInt(p.dataset.stepPanel, 10) !== currentStep); });
        progressItems.forEach(function (item) {
            const n = parseInt(item.dataset.progressStep, 10);
            item.classList.toggle('is-active', n === currentStep);
            item.classList.toggle('is-done', n < currentStep);
        });
        if (currentStep === 4) { populateReview(); }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function fieldValue(id) { const el = $(id); return el ? el.value.trim() : ''; }
    function esc(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    function row(label, value) {
        return '<div class="d-flex justify-content-between gap-3 border-bottom py-1"><span class="text-muted">' + label + '</span><span class="fw-medium text-end">' + (value || '&mdash;') + '</span></div>';
    }

    function fail(el, hint) {
        if (el) { el.focus(); if (el.reportValidity) { el.reportValidity(); } }
        if (hint) { const h = $('verifyHint'); h.textContent = hint; h.classList.add('text-danger'); }
        return false;
    }

    // Browser-side convenience only: the server checks every one of these again.
    function validateStep(step) {
        if (step === 1) {
            for (const id of ['first_name', 'last_name', 'username', 'email']) {
                const el = $(id);
                if (!el.checkValidity()) { return fail(el); }
            }
            return true;
        }
        if (step === 2) {
            if (ID_REQUIRED.indexOf(roleId()) !== -1 && !(widget && widget.wasAttempted())) {
                return fail(null, 'Please verify the ID before continuing (required for the chosen role). Pick the role in step 3 first if you are unsure.');
            }
            return true;
        }
        if (step === 3) {
            if (!$('role_id').value) { return fail($('role_id')); }
            if ($('branchWrap').classList.contains('d-none') === false && $('branch_id').required && !$('branch_id').value) { return fail($('branch_id')); }
            if (roleId() === ROLE_ADMIN && !$('admin_password').value) { return fail($('admin_password')); }
            if (manualMode()) {
                if ($('password').value.length < 8) { return fail($('password')); }
                if ($('password').value !== $('password_confirm').value) {
                    $('password_confirm').setCustomValidity('Passwords do not match');
                    $('password_confirm').reportValidity();
                    $('password_confirm').setCustomValidity('');
                    return false;
                }
            }
            return true;
        }
        return true;
    }

    document.querySelectorAll('.wizard-next').forEach(function (b) {
        b.addEventListener('click', function () { if (validateStep(currentStep)) { showStep(currentStep + 1); } });
    });
    document.querySelectorAll('.wizard-back').forEach(function (b) { b.addEventListener('click', function () { showStep(currentStep - 1); }); });
    document.querySelectorAll('.wizard-edit').forEach(function (b) { b.addEventListener('click', function () { showStep(parseInt(b.dataset.gotoStep, 10)); }); });
    $('role_id').addEventListener('change', applyRole);
    $('pm_generate').addEventListener('change', applyPasswordMode);
    $('pm_manual').addEventListener('change', applyPasswordMode);

    // Last check before submit: ID may be needed for the final role.
    $('createUserForm').addEventListener('submit', function (e) {
        if (!validateStep(3) || (ID_REQUIRED.indexOf(roleId()) !== -1 && !(widget && widget.wasAttempted()))) {
            e.preventDefault();
            showStep(ID_REQUIRED.indexOf(roleId()) !== -1 && !(widget && widget.wasAttempted()) ? 2 : 3);
        }
    });

    function populateReview() {
        $('reviewPersonal').innerHTML = [
            row('Name', esc([fieldValue('first_name'), fieldValue('middle_name'), fieldValue('last_name')].filter(Boolean).join(' '))),
            row('Username', esc(fieldValue('username'))),
            row('Email', esc(fieldValue('email'))),
            row('Contact Number', esc(fieldValue('contact_number'))),
        ].join('');

        const t = $('id_type');
        const st = widget ? widget.getStatus() : null;
        $('reviewIdVerification').innerHTML = st
            ? row('ID Type', esc(t.selectedOptions[0] ? t.selectedOptions[0].text : '')) + row('Status', esc(st.charAt(0).toUpperCase() + st.slice(1).replace('_', ' ')))
            : row('Status', ID_REQUIRED.indexOf(roleId()) !== -1 ? 'Not yet verified (required)' : 'Skipped (optional for a Client)');

        const rs = $('role_id'), bs = $('branch_id');
        const rows = [row('Role', esc(rs.selectedOptions[0] ? rs.selectedOptions[0].text : ''))];
        if (roleId() !== ROLE_ADMIN) { rows.push(row('Branch', esc(bs.value ? bs.selectedOptions[0].text : 'No branch'))); }
        if (roleId() === ROLE_CLIENT && fieldValue('unique_identifier')) { rows.push(row('Unique Identifier', esc(fieldValue('unique_identifier')))); }
        if (roleId() === ROLE_STAFF) { rows.push(row('Can collect payments', $('is_collector').checked ? 'Yes' : 'No')); }
        rows.push(row('Password', manualMode() ? 'Set by you' + ($('must_change_password').checked ? ' (change required at first sign-in)' : '') : 'Temporary password, valid 7 days'));
        $('reviewAccount').innerHTML = rows.join('');
    }

    const widget = initIdVerificationWidget({
        endpoint: '<?= base_url('api/id-verification/verify-pending') ?>',
        csrfName: document.querySelector('input[name="<?= csrf_token() ?>"]').name,
        csrfValue: document.querySelector('input[name="<?= csrf_token() ?>"]').value,
        getIdentity: function () {
            return { first_name: fieldValue('first_name'), middle_name: fieldValue('middle_name'), last_name: fieldValue('last_name'), gender: '' };
        },
        resultFieldId: 'government_id_pending_token',
        resultFieldKey: 'pending_token',
        initialStatus: <?= json_encode($keep_status ?? null) ?>,
    });

    applyRole();
    applyPasswordMode();
    showStep(1);
})();
</script>
<?= $this->endSection() ?>
