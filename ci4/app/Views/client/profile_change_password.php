<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="mb-4 text-end">
    <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Update Password</h2></div>
            <div class="cs-panel__body">
                <form method="post" action="<?= base_url('client/profile/update-password') ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="current_password">
                            Current Password *
                        </label>
                        <div class="input-group">
                            <input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="form-control <?= old('current_password') ? 'is-valid' : '' ?>"
                                value="<?= esc(old('current_password', '')) ?>"
                                required
                                placeholder="Enter your current password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('current_password')">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            For your security, we need to verify your current password
                        </small>
                    </div>

                    <hr class="cs-hairline my-4">

                    <div class="mb-3">
                        <label class="form-label" for="new_password">
                            New Password *
                        </label>
                        <div class="input-group">
                            <input
                                id="new_password"
                                name="new_password"
                                type="password"
                                class="form-control <?= old('new_password') ? 'is-valid' : '' ?>"
                                value="<?= esc(old('new_password', '')) ?>"
                                required
                                placeholder="Enter new password"
                                minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password')">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Must be at least 8 characters long
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">
                            Confirm Password *
                        </label>
                        <div class="input-group">
                            <input
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                class="form-control <?= old('confirm_password') ? 'is-valid' : '' ?>"
                                value="<?= esc(old('confirm_password', '')) ?>"
                                required
                                placeholder="Confirm new password"
                                minlength="8">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Passwords must match
                        </small>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <small class="mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            <strong>Password Requirements:</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <li>At least 8 characters long</li>
                                <li>Cannot contain your username</li>
                                <li>Mix of letters, numbers, and symbols recommended</li>
                            </ul>
                        </small>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="cs-panel mt-3">
            <div class="cs-panel__body">
                <h6 class="mb-3">
                    <i class="ti ti-shield-check" style="color: var(--cs-ok);"></i> Security Tips
                </h6>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <i class="ti ti-check" style="color: var(--cs-ok);"></i>
                        Use a unique password you don't use elsewhere
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check" style="color: var(--cs-ok);"></i>
                        Change your password regularly
                    </li>
                    <li class="mb-2">
                        <i class="ti ti-check" style="color: var(--cs-ok);"></i>
                        Never share your password with anyone
                    </li>
                    <li>
                        <i class="ti ti-check" style="color: var(--cs-ok);"></i>
                        Use a password manager to store your credentials safely
                    </li>
                </ul>
            </div>
        </section>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}
</script>

<style>
    /* No cs-panel equivalent for joining a form-control to a trailing
       icon button (the password show/hide toggle) - kept scoped. */
    .input-group .btn-outline-secondary {
        border-left: 0;
        border-right: 1px solid #dee2e6;
    }

    .input-group .form-control {
        border-right: 0;
    }

    .input-group .form-control:focus {
        border-right: 0;
    }

    .input-group .btn-outline-secondary:hover {
        background: transparent;
        border-color: #dee2e6;
    }
</style>

<?= $this->endSection() ?>
