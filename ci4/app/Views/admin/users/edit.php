<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$uid         = (int) $u['user_id'];
$val         = static fn (string $k, $default = '') => old($k, $default);
$roleNow     = (string) old('role_id', (string) $u['role_id']);
$branchNow   = (string) old('branch_id', (string) ($u['branch_id'] ?? ''));
$statusNow   = (string) old('status', (string) $u['status']);
$collectorNow = (string) old('is_collector', (string) $u['is_collector']) === '1';
$branchRoles = array_map('intval', $branch_roles ?? []);
?>
<form method="post" action="<?= base_url('admin/users/' . $uid . '/edit') ?>" id="editUserForm" autocomplete="off">
    <?= csrf_field() ?>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head">
            <h2 class="cs-panel__title">Personal information</h2>
            <p class="cs-panel__note">Username: <strong><?= esc((string) $u['username']) ?></strong> (cannot be changed). Passwords are never shown here — use "Reset password" on the user list.</p>
        </div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" maxlength="50" value="<?= esc((string) $val('first_name', (string) $u['first_name']), 'attr') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="middle_name">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name" maxlength="50" value="<?= esc((string) $val('middle_name', (string) ($u['middle_name'] ?? '')), 'attr') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" maxlength="50" value="<?= esc((string) $val('last_name', (string) $u['last_name']), 'attr') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= esc((string) $val('email', (string) ($u['email'] ?? '')), 'attr') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="contact_number">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" maxlength="20" value="<?= esc((string) $val('contact_number', (string) ($u['contact_number'] ?? '')), 'attr') ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Role, branch and status</h2></div>
        <div class="cs-panel__body">
            <?php if ($is_self): ?>
                <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'message' => 'This is your own account: you can update your details, but not your role or status.']) ?>
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="role_id">Role</label>
                    <select class="form-select" id="role_id" name="role_id" <?= $is_self ? 'disabled' : '' ?> required>
                        <?php foreach ($roles as $rid => $label): ?>
                            <option value="<?= (int) $rid ?>" <?= $roleNow === (string) $rid ? 'selected' : '' ?>><?= esc((string) $label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($is_self): ?><input type="hidden" name="role_id" value="<?= (int) $u['role_id'] ?>"><?php endif; ?>
                    <div class="form-text">Collector: field collector account only. To let a Staff member also collect, choose Staff and tick "Can collect payments".</div>
                    <?php if ($has_plan_holder && (int) $u['role_id'] === 4): ?>
                        <div class="form-text text-warning">This user has a plan holder record, so the role cannot be changed from Client.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6" id="branchWrap">
                    <label class="form-label" for="branch_id">Branch <span class="text-muted small" id="branchHint"></span></label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value="">No branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= (int) $b['branch_id'] ?>" <?= $branchNow === (string) $b['branch_id'] ? 'selected' : '' ?>>
                                <?= esc((string) $b['branch_name']) ?><?= $b['status'] !== 'active' ? ' (inactive)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-none" id="collectorWrap">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_collector" name="is_collector" value="1" <?= $collectorNow ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_collector">Can collect payments</label>
                    </div>
                </div>

                <?php if ($manages): ?>
                    <div class="col-12 d-none" id="managerWrap">
                        <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'message' => 'This user is the assigned manager of ' . (string) $manages['branch_name'] . '. You are moving them to another branch or role. What should happen to the manager post?']) ?>
                        <p class="text-danger fw-medium d-none mb-2" id="managerForced">An inactive user, a Collector, a Client or a System Admin cannot be a branch manager, so they will be cleared as manager of <?= esc((string) $manages['branch_name']) ?>. The branch will have no manager until you assign one.</p>
                        <div class="form-check" id="maKeepRow">
                            <input class="form-check-input" type="radio" name="manager_action" id="ma_keep" value="keep" <?= (string) old('manager_action', 'keep') === 'keep' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ma_keep">Keep them as manager of <?= esc((string) $manages['branch_name']) ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="manager_action" id="ma_clear" value="clear" <?= (string) old('manager_action') === 'clear' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ma_clear">Clear the manager (<?= esc((string) $manages['branch_name']) ?> will have no manager until you assign one)</label>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-12 d-none" id="adminPwWrap">
                    <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'message' => 'A System Admin can see and change everything in the system. Enter your own password to confirm.']) ?>
                    <label class="form-label" for="admin_password">Your password</label>
                    <input type="password" class="form-control" id="admin_password" name="admin_password" autocomplete="current-password">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" <?= $is_self ? 'disabled' : '' ?>>
                        <option value="active" <?= $statusNow === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusNow === 'inactive' ? 'selected' : '' ?>>Inactive (cannot sign in)</option>
                    </select>
                    <?php if ($is_self): ?><input type="hidden" name="status" value="active"><?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a class="btn btn-ghost" href="<?= base_url('admin/users/' . $uid) ?>">Cancel</a>
    </div>
</form>

<script>
(function () {
    const BRANCH_ROLES = <?= json_encode($branchRoles) ?>;
    const ORIG_ROLE = <?= (int) $u['role_id'] ?>;
    const ORIG_BRANCH = <?= json_encode((string) ($u['branch_id'] ?? '')) ?>;
    const MANAGES = <?= json_encode($manages ? (string) $manages['branch_id'] : '') ?>;
    const $ = function (id) { return document.getElementById(id); };
    function show(el, on) { if (el) { el.classList.toggle('d-none', !on); } }
    function apply() {
        const r = parseInt($('role_id').value, 10) || 0;
        const needed = BRANCH_ROLES.indexOf(r) !== -1;
        show($('branchWrap'), r !== 1);
        $('branch_id').required = needed;
        $('branchHint').textContent = needed ? '(required)' : (r === 4 ? '(optional)' : '');
        show($('collectorWrap'), r === 3);
        show($('adminPwWrap'), r === 1 && ORIG_ROLE !== 1);
        $('admin_password').required = r === 1 && ORIG_ROLE !== 1;
        // Asking about the manager post only matters when the user actually leaves it.
        // Only an active Branch Admin / Staff member can stay a manager; otherwise "clear" is forced.
        const statusEl = $('status');
        const active = !statusEl || statusEl.value === 'active';
        const forced = MANAGES !== '' && ([2, 3].indexOf(r) === -1 || !active);
        const leaves = MANAGES !== '' && (forced || $('branch_id').value !== MANAGES);
        show($('managerWrap'), leaves);
        show($('managerForced'), forced);
        show($('maKeepRow'), !forced);
        if (forced && $('ma_clear')) { $('ma_clear').checked = true; }
    }
    $('role_id').addEventListener('change', apply);
    $('branch_id').addEventListener('change', apply);
    if ($('status')) { $('status').addEventListener('change', apply); }
    apply();
})();
</script>
<?= $this->endSection() ?>
