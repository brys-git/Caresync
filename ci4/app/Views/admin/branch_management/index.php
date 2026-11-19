<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<div class="mb-3 text-end">
    <a href="<?= site_url('/admin/branch-management/create') ?>" class="btn btn-primary"><i class="ti ti-plus" aria-hidden="true"></i> Add Branch</a>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= ($tab ?? '') === 'branches' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=branches') ?>">Branches</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab ?? '') === 'availability' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=availability') ?>">Branch Services / Availability</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab ?? '') === 'transactions' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=transactions') ?>">Branch Transactions / Operations</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab ?? '') === 'contribution' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=contribution') ?>">Branch Contribution</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab ?? '') === 'approval' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=approval') ?>">Approval (Services &amp; Packages)</a>
    </li>
</ul>

<?php if (($tab ?? '') === 'branches'): ?>
    <section class="cs-panel">
        <div class="cs-panel__body cs-panel__body--flush">
            <?php if (empty($branch_rows ?? [])): ?>
                <?= view('components/empty_state', ['icon' => 'ti-building-store', 'title' => 'No branches yet']) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Branch</th>
                                <th>Address</th>
                                <th>Manager</th>
                                <th>Contact</th>
                                <th>Plan holders</th>
                                <th>Status</th>
                                <th class="cs-table__actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branch_rows as $b): ?>
                                <?php $isActive = ($b['status'] ?? '') === 'active'; ?>
                                <tr>
                                    <td class="text-nowrap"><?= esc($b['branch_code'] ?? '-') ?></td>
                                    <td>
                                        <?= esc($b['branch_name']) ?>
                                        <div class="cs-muted small"><?= ($b['branch_type'] ?? '') === 'main' ? 'Main Office' : 'Branch Office' ?></div>
                                    </td>
                                    <td><?= esc(implode(', ', array_filter([$b['address_street'] ?? '', $b['address_barangay'] ?? '', $b['address_city'] ?? '', $b['address_province'] ?? '']))) ?></td>
                                    <?php $managerName = trim((string) ($b['mgr_first'] ?? $b['manager_first_name'] ?? '') . ' ' . (string) ($b['mgr_last'] ?? $b['manager_last_name'] ?? '')); ?>
                                    <td><?= $managerName !== '' ? esc($managerName) : '<span class="text-muted">No manager yet</span>' ?></td>
                                    <td><?= esc($b['contact_number'] ?? '-') ?></td>
                                    <td><?= (int) ($b['client_count'] ?? 0) ?></td>
                                    <td><?= cs_status($isActive ? 'active' : 'inactive') ?></td>
                                    <td class="cs-table__actions">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('/admin/branch-management/edit/' . (int) $b['branch_id']) ?>">Edit</a>
                                        <form method="post" action="<?= site_url('/admin/branch-management/toggle-status/' . (int) $b['branch_id']) ?>" class="d-inline"
                                              data-cs-confirm="<?= $isActive ? 'Deactivate' : 'Activate' ?> this branch? Its records are not deleted." data-cs-confirm-title="<?= $isActive ? 'Deactivate branch' : 'Activate branch' ?>" data-cs-confirm-ok="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-danger' : 'btn-outline-primary' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (($tab ?? '') === 'availability'): ?>
    <section class="cs-panel">
        <div class="cs-panel__body">
            <form class="cs-filters mb-3" method="get">
                <input type="hidden" name="tab" value="availability">
                <div class="cs-filters__field">
                    <label class="form-label">Branch Filter</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($selected_branch_id ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cs-filters__actions">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
        </div>

        <div class="cs-panel__body cs-panel__body--flush">
            <?php if (empty($availability_rows ?? [])): ?>
                <?= view('components/empty_state', ['icon' => 'ti-building-store', 'title' => 'No records found']) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Service / Package Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th class="cs-table__actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availability_rows as $row): ?>
                                <tr>
                                    <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                    <td><?= esc($row['item_name'] ?? '-') ?></td>
                                    <td><?= esc($row['item_type'] ?? '-') ?></td>
                                    <td><?= cs_status((int) ($row['is_available'] ?? 0) === 1 ? 'active' : 'inactive', $row['status_label'] ?? null) ?></td>
                                    <td class="cs-table__actions">
                                        <form method="post" action="<?= site_url('/admin/branch-management/toggle-availability') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="item_type" value="<?= esc(strtolower((string) ($row['item_type'] ?? 'service'))) ?>">
                                            <input type="hidden" name="item_id" value="<?= (int) ($row['item_id'] ?? 0) ?>">
                                            <input type="hidden" name="branch_id" value="<?= (int) ($selected_branch_id ?? 0) ?>">
                                            <input type="hidden" name="return_tab" value="availability">
                                            <input type="hidden" name="is_available" value="<?= (int) ($row['is_available'] ?? 0) === 1 ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-sm <?= (int) ($row['is_available'] ?? 0) === 1 ? 'btn-danger' : 'btn-outline-primary' ?>">
                                                <?= (int) ($row['is_available'] ?? 0) === 1 ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (($tab ?? '') === 'transactions'): ?>
    <section class="cs-panel">
        <div class="cs-panel__body">
            <form class="cs-filters mb-3" method="get">
                <input type="hidden" name="tab" value="transactions">
                <div class="cs-filters__field">
                    <label class="form-label">Branch Filter</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($selected_branch_id ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cs-filters__field">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= esc($date_from ?? '') ?>">
                </div>
                <div class="cs-filters__field">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= esc($date_to ?? '') ?>">
                </div>
                <div class="cs-filters__actions">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-ghost" href="<?= site_url('/admin/branch-management/export-transactions?branch_id=' . (int) ($selected_branch_id ?? 0) . '&date_from=' . rawurlencode((string) ($date_from ?? '')) . '&date_to=' . rawurlencode((string) ($date_to ?? ''))) ?>">Export</a>
                </div>
            </form>
        </div>

        <div class="cs-panel__body cs-panel__body--flush">
            <?php if (empty($transactions ?? [])): ?>
                <?= view('components/empty_state', ['icon' => 'ti-receipt', 'title' => 'No transactions found']) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Plan Holder</th>
                                <th>Transaction Type</th>
                                <th class="cs-num">Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                    <td><?= esc($row['plan_holder'] ?? '-') ?></td>
                                    <td><?= esc($row['transaction_type'] ?? '-') ?></td>
                                    <td class="cs-num"><?= cs_money($row['amount'] ?? 0) ?></td>
                                    <td class="text-nowrap"><?= cs_date($row['transaction_date'] ?? null) ?></td>
                                    <td><?= cs_status((string) ($row['status'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (($tab ?? '') === 'contribution'): ?>
    <section class="cs-panel">
        <div class="cs-panel__body">
            <form class="cs-filters mb-3" method="get">
                <input type="hidden" name="tab" value="contribution">
                <div class="cs-filters__field">
                    <label class="form-label">Branch Filter</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($selected_branch_id ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cs-filters__actions">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
        </div>

        <div class="cs-panel__body cs-panel__body--flush">
            <?php if (empty($contributions ?? [])): ?>
                <?= view('components/empty_state', ['icon' => 'ti-cash', 'title' => 'No contribution data found']) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th class="cs-num">Total Payments Collected</th>
                                <th class="cs-num">Total Remitted (confirmed)</th>
                                <th class="cs-num">Commission earned (confirmed remittances)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contributions as $row): ?>
                                <tr>
                                    <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                    <td class="cs-num"><?= cs_money($row['total_collected'] ?? 0) ?></td>
                                    <td class="cs-num"><?= cs_money($row['total_remitted'] ?? 0) ?></td>
                                    <td class="cs-num"><?= cs_money($row['staff_commission'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (($tab ?? '') === 'approval'): ?>
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link <?= ($approval_tab ?? '') === 'services' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=approval&approval_tab=services') ?>">Pending Services</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($approval_tab ?? '') === 'packages' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=approval&approval_tab=packages') ?>">Pending Packages</a>
        </li>
    </ul>

    <?php if (($approval_tab ?? '') === 'services'): ?>
        <section class="cs-panel">
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if (empty($pending_services ?? [])): ?>
                    <?= view('components/empty_state', ['icon' => 'ti-truck', 'title' => 'No pending services found']) ?>
                <?php else: ?>
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Date Created</th>
                                    <th>Status</th>
                                    <th class="cs-table__actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_services as $row): ?>
                                    <tr>
                                        <td><?= esc($row['service_name'] ?? '-') ?></td>
                                        <td><?= esc($row['description'] ?? '-') ?></td>
                                        <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?: '-' ?></td>
                                        <td class="text-nowrap"><?= cs_date($row['created_at'] ?? null) ?></td>
                                        <td><?= cs_status((string) ($row['status'] ?? '')) ?></td>
                                        <td class="cs-table__actions">
                                            <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                <form method="post" action="<?= site_url('/admin/branch-management/approval/service/approve/' . (int) $row['pending_service_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-primary">Approve</button>
                                                </form>
                                                <form method="post" action="<?= site_url('/admin/branch-management/approval/service/reject/' . (int) $row['pending_service_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (($approval_tab ?? '') === 'packages'): ?>
        <section class="cs-panel">
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if (empty($pending_packages ?? [])): ?>
                    <?= view('components/empty_state', ['icon' => 'ti-package', 'title' => 'No pending packages found']) ?>
                <?php else: ?>
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Date Created</th>
                                    <th>Status</th>
                                    <th class="cs-table__actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_packages as $row): ?>
                                    <tr>
                                        <td><?= esc($row['package_name'] ?? '-') ?></td>
                                        <td><?= esc($row['description'] ?? '-') ?></td>
                                        <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?: '-' ?></td>
                                        <td class="text-nowrap"><?= cs_date($row['created_at'] ?? null) ?></td>
                                        <td><?= cs_status((string) ($row['status'] ?? '')) ?></td>
                                        <td class="cs-table__actions">
                                            <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                <form method="post" action="<?= site_url('/admin/branch-management/approval/package/approve/' . (int) $row['pending_package_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-primary">Approve</button>
                                                </form>
                                                <form method="post" action="<?= site_url('/admin/branch-management/approval/package/reject/' . (int) $row['pending_package_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
