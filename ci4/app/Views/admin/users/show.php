<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$uid      = (int) $u['user_id'];
$name     = trim((string) $u['first_name'] . ' ' . (string) ($u['middle_name'] ?? '') . ' ' . (string) $u['last_name']);
$name     = preg_replace('/\s+/', ' ', $name);
$isActive = ($u['status'] ?? '') === 'active';
$field = static function (string $label, string $html): string {
    return '<div class="d-flex justify-content-between gap-3 border-bottom py-2"><span class="text-muted">' . esc($label) . '</span><span class="fw-medium text-end">' . $html . '</span></div>';
};
$none = '<span class="text-muted">—</span>';
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-ghost btn-sm" href="<?= base_url('admin/users') ?>"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to users</a>
    <a class="btn btn-primary btn-sm" href="<?= base_url('admin/users/' . $uid . '/edit') ?>"><i class="ti ti-edit" aria-hidden="true"></i> Edit</a>
    <?php if (! $is_self): ?>
        <form method="post" action="<?= base_url('admin/users/' . $uid . '/reset-password') ?>" class="d-inline"
              data-cs-confirm="Issue a new temporary password for <?= esc($name, 'attr') ?>? Their current password stops working and they must change the new one at next sign-in."
              data-cs-confirm-title="Reset password" data-cs-confirm-ok="Reset password" data-cs-once>
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-primary btn-sm">Reset password</button>
        </form>
        <form method="post" action="<?= base_url('admin/users/' . $uid . '/toggle-status') ?>" class="d-inline"
              data-cs-confirm="<?= $isActive ? 'Deactivate ' . esc($name, 'attr') . '? They will not be able to sign in. Their records are not deleted.' : 'Activate ' . esc($name, 'attr') . '? They will be able to sign in again.' ?>"
              data-cs-confirm-title="<?= $isActive ? 'Deactivate user' : 'Activate user' ?>" data-cs-confirm-ok="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-danger' : 'btn-outline-primary' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
        </form>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="cs-panel h-100">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Account</h2></div>
            <div class="cs-panel__body">
                <?= $field('Name', esc($name) . ($is_self ? ' (you)' : '')) ?>
                <?= $field('Username', esc((string) $u['username'])) ?>
                <?= $field('Email', $u['email'] ? esc((string) $u['email']) : $none) ?>
                <?= $field('Contact number', ($u['contact_number'] ?? '') !== '' ? esc((string) $u['contact_number']) : $none) ?>
                <?= $field('Role', esc((string) ($u['role_name'] ?? '—'))) ?>
                <?= $field('Branch', ($u['branch_name'] ?? '') !== '' ? esc((string) $u['branch_name']) : $none) ?>
                <?php if ($manages): ?>
                    <?= $field('Branch manager', 'Manager of ' . esc((string) $manages['branch_name'])) ?>
                <?php endif; ?>
                <?php if ((int) $u['role_id'] === 3): ?>
                    <?= $field('Can collect payments', (int) $u['is_collector'] === 1 ? 'Yes' : 'No') ?>
                <?php endif; ?>
                <?= $field('Status', cs_status($isActive ? 'active' : 'inactive')) ?>
                <?= $field('Account', cs_status((string) ($u['account_status'] ?? ''))) ?>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Sign-in</h2></div>
            <div class="cs-panel__body">
                <?= $field('Last login', ! empty($u['last_login']) ? esc(cs_date($u['last_login'], true)) : 'Never') ?>
                <?= $field('Created', esc(cs_date($u['created_at'], true))) ?>
                <?php if ((int) $u['must_change_password'] === 1): ?>
                    <?= $field('Password', 'Temporary — must be changed at next sign-in' . (! empty($u['temp_password_expires_at']) ? '<div class="cs-table__sub">Valid until ' . esc(cs_date($u['temp_password_expires_at'], true)) . '</div>' : '')) ?>
                <?php else: ?>
                    <?= $field('Password', 'Set by the user') ?>
                <?php endif; ?>
                <?php if ($has_plan_holder): ?>
                    <?= $field('Plan holder record', 'Yes') ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Government ID check</h2></div>
            <div class="cs-panel__body">
                <?php if ($id_check): ?>
                    <?= $field('Result', cs_status((string) $id_check['verification_status'])) ?>
                    <?= $field('ID type', esc(ucwords(str_replace('_', ' ', (string) $id_check['id_type'])))) ?>
                    <?= $field('Checked', esc(cs_date($id_check['verified_at'] ?: $id_check['created_at']))) ?>
                <?php else: ?>
                    <p class="cs-muted mb-0">No ID check is on file for this account.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-12">
        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Recent changes to this account</h2></div>
            <div class="cs-panel__body">
                <?php if ($history === []): ?>
                    <p class="cs-muted mb-0">Nothing recorded yet.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($history as $h): ?>
                            <li class="border-bottom py-2">
                                <div><?= esc((string) $h['description']) ?></div>
                                <div class="cs-table__sub"><?= esc(cs_date($h['created_at'], true)) ?> · by <?= esc(trim((string) $h['first_name'] . ' ' . (string) $h['last_name']) ?: 'system') ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
