<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$filters    = $filters ?? [];
$search     = (string) ($filters['search'] ?? '');
$roleF      = (int) ($filters['role'] ?? 0);
$branchF    = (int) ($filters['branch_id'] ?? 0);
$statusF    = (string) ($filters['status'] ?? '');
$hasFilters = $search !== '' || $roleF > 0 || $branchF > 0 || $statusF !== '';
$users      = $users ?? [];
$page       = (int) ($page ?? 1);
$pages      = (int) ($pages ?? 1);
$total      = (int) ($total ?? 0);
$perPage    = (int) ($per_page ?? 25);

// Query string of the current filters (page excluded), reused by the pager and the row actions.
$keep = array_filter(['search' => $search, 'role' => $roleF ?: '', 'branch_id' => $branchF ?: '', 'status' => $statusF], static fn ($v) => $v !== '');
$pageUrl = static fn (int $p): string => base_url('admin/users') . '?' . http_build_query($keep + ['page' => $p]);
$returnQs = '?' . http_build_query($keep + ($page > 1 ? ['page' => $page] : []));
?>

<form class="cs-filters" method="get" action="<?= base_url('admin/users') ?>">
    <div class="cs-filters__field cs-filters__field--wide">
        <label class="form-label" for="f-search">Search</label>
        <input class="form-control" id="f-search" type="search" name="search"
               value="<?= esc($search) ?>" placeholder="Name, username or email">
    </div>

    <div class="cs-filters__field">
        <label class="form-label" for="f-role">Role</label>
        <select class="form-select" id="f-role" name="role">
            <option value="">Every role</option>
            <?php foreach (($roles ?? []) as $r): ?>
                <option value="<?= (int) $r['role_id'] ?>" <?= $roleF === (int) $r['role_id'] ? 'selected' : '' ?>><?= esc((string) $r['role_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="cs-filters__field">
        <label class="form-label" for="f-branch">Branch</label>
        <select class="form-select" id="f-branch" name="branch_id">
            <option value="">Every branch</option>
            <?php foreach (($branches ?? []) as $b): ?>
                <option value="<?= (int) $b['branch_id'] ?>" <?= $branchF === (int) $b['branch_id'] ? 'selected' : '' ?>><?= esc((string) $b['branch_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="cs-filters__field">
        <label class="form-label" for="f-status">Status</label>
        <select class="form-select" id="f-status" name="status">
            <option value="">Any status</option>
            <option value="active" <?= $statusF === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusF === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <div class="cs-filters__actions">
        <button class="btn btn-primary" type="submit"><i class="ti ti-filter" aria-hidden="true"></i> Apply</button>
        <?php if ($hasFilters): ?>
            <a class="btn btn-ghost" href="<?= base_url('admin/users') ?>">Clear</a>
        <?php endif; ?>
    </div>
</form>

<section class="cs-panel">
    <div class="cs-panel__head">
        <div>
            <h2 class="cs-panel__title"><?= esc(number_format($total)) ?> <?= $total === 1 ? 'user' : 'users' ?></h2>
            <?php if ($hasFilters): ?>
                <p class="cs-panel__note">Filtered view. Clear the filters to see everyone.</p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary btn-sm" href="<?= base_url('admin/users/create') ?>">
                <i class="ti ti-user-plus" aria-hidden="true"></i> <span>Add user</span>
            </a>
        </div>
    </div>

    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($users === []): ?>
            <?= view('components/empty_state', [
                'icon'  => 'ti-users',
                'title' => $hasFilters ? 'No users match those filters' : 'No users yet',
                'text'  => $hasFilters ? 'Try a different role or branch, or clear the search box.' : '',
            ]) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table cs-table--cards">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Account</th>
                            <th>Last login</th>
                            <th>Created</th>
                            <th class="cs-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php
                            $uid      = (int) $u['user_id'];
                            $name     = trim((string) $u['first_name'] . ' ' . (string) $u['last_name']);
                            $isActive = ($u['status'] ?? '') === 'active';
                            $isSelf   = $uid === (int) ($self_id ?? 0);
                            ?>
                            <tr>
                                <td class="cs-cards__name">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="cs-avatar" aria-hidden="true"><?= esc(cs_initials($name)) ?></span>
                                        <div class="lh-sm">
                                            <span class="cs-table__primary"><?= esc($name) ?><?= $isSelf ? ' (you)' : '' ?></span>
                                            <div class="cs-table__sub"><?= esc((string) ($u['email'] ?? '—')) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Username"><?= esc((string) $u['username']) ?></td>
                                <td data-label="Role">
                                    <?= esc((string) ($u['role_name'] ?? '—')) ?>
                                    <?php if ((int) $u['role_id'] === 3 && (int) ($u['is_collector'] ?? 0) === 1): ?>
                                        <div class="cs-table__sub">Can collect payments</div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Branch"><?= esc((string) ($u['branch_name'] ?? '—')) ?></td>
                                <td data-label="Status"><?= cs_status($isActive ? 'active' : 'inactive') ?></td>
                                <td data-label="Account"><?= cs_status((string) ($u['account_status'] ?? '')) ?></td>
                                <td data-label="Last login"><?= ! empty($u['last_login']) ? esc(cs_date($u['last_login'], true)) : '<span class="text-muted">Never</span>' ?></td>
                                <td data-label="Created"><?= esc(cs_date($u['created_at'])) ?></td>
                                <td class="cs-table__actions cs-cards__actions">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/users/' . $uid) ?>">View</a>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/users/' . $uid . '/edit') ?>">Edit</a>
                                    <?php if (! $isSelf): ?>
                                        <form method="post" action="<?= base_url('admin/users/' . $uid . '/reset-password') ?>" class="d-inline"
                                              data-cs-confirm="Issue a new temporary password for <?= esc($name, 'attr') ?>? Their current password stops working and they must change the new one at next sign-in."
                                              data-cs-confirm-title="Reset password" data-cs-confirm-ok="Reset password" data-cs-once>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="return" value="<?= esc($returnQs, 'attr') ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Reset password</button>
                                        </form>
                                        <form method="post" action="<?= base_url('admin/users/' . $uid . '/toggle-status') ?>" class="d-inline"
                                              data-cs-confirm="<?= $isActive ? 'Deactivate ' . esc($name, 'attr') . '? They will not be able to sign in. Their records are not deleted.' : 'Activate ' . esc($name, 'attr') . '? They will be able to sign in again.' ?>"
                                              data-cs-confirm-title="<?= $isActive ? 'Deactivate user' : 'Activate user' ?>" data-cs-confirm-ok="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="return" value="<?= esc($returnQs, 'attr') ?>">
                                            <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-danger' : 'btn-outline-primary' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3" aria-label="Users pages">
                    <span class="cs-muted small">
                        Showing <?= esc(number_format(($page - 1) * $perPage + 1)) ?>–<?= esc(number_format(min($page * $perPage, $total))) ?> of <?= esc(number_format($total)) ?>
                    </span>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= esc($pageUrl(max(1, $page - 1)), 'attr') ?>">Previous</a></li>
                        <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= esc($pageUrl($p), 'attr') ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= esc($pageUrl(min($pages, $page + 1)), 'attr') ?>">Next</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
