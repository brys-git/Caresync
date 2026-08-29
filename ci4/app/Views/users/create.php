<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Create User Account</h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= base_url('users/create') ?>">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= old('first_name') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= old('last_name') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= old('contact_number') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="role_id">Role</label>
                        <select class="form-select" id="role_id" name="role_id" <?= $current_role_id === 3 ? 'disabled' : '' ?> required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= esc((string) $role['role_id']) ?>" <?= old('role_id') == $role['role_id'] ? 'selected' : '' ?>>
                                    <?= esc($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($current_role_id === 3): ?>
                            <input type="hidden" name="role_id" value="4">
                            <small class="text-muted">Staff can only create Plan Holder accounts.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select class="form-select" id="branch_id" name="branch_id">
                            <option value="">No Branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= esc((string) $branch['branch_id']) ?>" <?= old('branch_id') == $branch['branch_id'] ? 'selected' : '' ?>>
                                    <?= esc($branch['branch_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($current_role_id === 1): ?>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirm">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                A temporary password will be auto-generated, sent via email, and the user will be forced to change password on first login.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="account_status">Account Status</label>
                        <select class="form-select" id="account_status" name="account_status" <?= $current_role_id === 3 ? 'disabled' : '' ?> required>
                            <option value="pending" <?= old('account_status', 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="verified" <?= old('account_status') === 'verified' ? 'selected' : '' ?>>Verified</option>
                        </select>
                        <?php if ($current_role_id === 3): ?>
                            <input type="hidden" name="account_status" value="verified">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_plan_holder" name="is_plan_holder" value="1" <?= old('is_plan_holder') == '1' ? 'checked' : '' ?> <?= $current_role_id === 3 ? 'checked disabled' : '' ?>>
                            <label class="form-check-label" for="is_plan_holder">Is Plan Holder</label>
                            <?php if ($current_role_id === 3): ?>
                                <input type="hidden" name="is_plan_holder" value="1">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" <?= old('must_change_password') == '1' ? 'checked' : '' ?> <?= $current_role_id === 3 ? 'checked disabled' : '' ?>>
                            <label class="form-check-label" for="must_change_password">Force Password Change</label>
                            <?php if ($current_role_id === 3): ?>
                                <input type="hidden" name="must_change_password" value="1">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
