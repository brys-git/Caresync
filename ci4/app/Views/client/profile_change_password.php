<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-0">Change Password</h4>
            <p class="text-muted mb-0 small">Update your login password</p>
        </div>
        <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="mdi mdi-arrow-left me-1"></i> Back to Profile
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i> <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i> <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Change Password Form -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0">Update Password</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= base_url('client/profile/update-password') ?>" novalidate>
                        <?= csrf_field() ?>

                        <!-- Current Password -->
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
                                    <i class="mdi mdi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                For your security, we need to verify your current password
                            </small>
                        </div>

                        <hr class="my-4">

                        <!-- New Password -->
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
                                    <i class="mdi mdi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Must be at least 8 characters long
                            </small>
                        </div>

                        <!-- Confirm Password -->
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
                                    <i class="mdi mdi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Passwords must match
                            </small>
                        </div>

                        <!-- Password Requirements -->
                        <div class="alert alert-info" role="alert">
                            <small class="mb-0">
                                <i class="mdi mdi-information me-2"></i>
                                <strong>Password Requirements:</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <li>At least 8 characters long</li>
                                    <li>Cannot contain your username</li>
                                    <li>Mix of letters, numbers, and symbols recommended</li>
                                </ul>
                            </small>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url('client/profile') ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check-circle me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="card mt-3 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="mdi mdi-shield-check text-success me-2"></i> Security Tips
                    </h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="mdi mdi-check text-success me-2"></i> 
                            Use a unique password you don't use elsewhere
                        </li>
                        <li class="mb-2">
                            <i class="mdi mdi-check text-success me-2"></i> 
                            Change your password regularly
                        </li>
                        <li class="mb-2">
                            <i class="mdi mdi-check text-success me-2"></i> 
                            Never share your password with anyone
                        </li>
                        <li>
                            <i class="mdi mdi-check text-success me-2"></i> 
                            Use a password manager to store your credentials safely
                        </li>
                    </ul>
                </div>
            </div>
        </div>
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
    .card {
        border: none;
        border-radius: 0.5rem;
    }

    .card-header {
        border-radius: 0.5rem 0.5rem 0 0;
    }

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
