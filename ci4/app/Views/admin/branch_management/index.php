<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Branch Management</h1>
            <p class="text-muted mb-0">Monitor branch availability, operations, contribution, and approval queues.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4">
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
            <a class="nav-link <?= ($tab ?? '') === 'approval' ? 'active' : '' ?>" href="<?= site_url('/admin/branch-management?tab=approval') ?>">Approval (Services & Packages)</a>
        </li>
    </ul>

    <?php if (($tab ?? '') === 'availability'): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end mb-3">
                    <input type="hidden" name="tab" value="availability">
                    <div class="col-md-4">
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
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle table-striped">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Service / Package Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($availability_rows ?? [])): ?>
                                <tr><td colspan="5" class="text-center py-4">No records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($availability_rows as $row): ?>
                                    <tr>
                                        <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                        <td><?= esc($row['item_name'] ?? '-') ?></td>
                                        <td><?= esc($row['item_type'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge <?= (int) ($row['is_available'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= esc($row['status_label'] ?? 'Not Available') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="<?= site_url('/admin/branch-management/toggle-availability') ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="item_type" value="<?= esc(strtolower((string) ($row['item_type'] ?? 'service'))) ?>">
                                                <input type="hidden" name="item_id" value="<?= (int) ($row['item_id'] ?? 0) ?>">
                                                <input type="hidden" name="branch_id" value="<?= (int) ($selected_branch_id ?? 0) ?>">
                                                <input type="hidden" name="return_tab" value="availability">
                                                <input type="hidden" name="is_available" value="<?= (int) ($row['is_available'] ?? 0) === 1 ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-sm <?= (int) ($row['is_available'] ?? 0) === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                    <?= (int) ($row['is_available'] ?? 0) === 1 ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($tab ?? '') === 'transactions'): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end mb-3">
                    <input type="hidden" name="tab" value="transactions">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?= esc($date_from ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?= esc($date_to ?? '') ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-primary flex-grow-1" type="submit">Filter</button>
                        <a class="btn btn-outline-secondary flex-grow-1" href="<?= site_url('/admin/branch-management/export-transactions?branch_id=' . (int) ($selected_branch_id ?? 0) . '&date_from=' . rawurlencode((string) ($date_from ?? '')) . '&date_to=' . rawurlencode((string) ($date_to ?? ''))) ?>">Export</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle table-striped">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Plan Holder</th>
                                <th>Transaction Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions ?? [])): ?>
                                <tr><td colspan="6" class="text-center py-4">No transactions found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $row): ?>
                                    <tr>
                                        <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                        <td><?= esc($row['plan_holder'] ?? '-') ?></td>
                                        <td><?= esc($row['transaction_type'] ?? '-') ?></td>
                                        <td><?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                                        <td><?= esc($row['transaction_date'] ?? '-') ?></td>
                                        <td><?= esc(ucfirst((string) ($row['status'] ?? '-'))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($tab ?? '') === 'contribution'): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end mb-3">
                    <input type="hidden" name="tab" value="contribution">
                    <div class="col-md-4">
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
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle table-striped">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Total Payments Collected</th>
                                <th>Total Remitted</th>
                                <th>Staff Commission (10%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contributions ?? [])): ?>
                                <tr><td colspan="4" class="text-center py-4">No contribution data found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($contributions as $row): ?>
                                    <tr>
                                        <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                        <td><?= esc((string) ($row['total_collected'] ?? '0.00')) ?></td>
                                        <td><?= esc((string) ($row['total_remitted'] ?? '0.00')) ?></td>
                                        <td><?= esc((string) ($row['staff_commission'] ?? '0.00')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Date Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_services ?? [])): ?>
                                    <tr><td colspan="6" class="text-center py-4">No pending services found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pending_services as $row): ?>
                                        <tr>
                                            <td><?= esc($row['service_name'] ?? '-') ?></td>
                                            <td><?= esc($row['description'] ?? '-') ?></td>
                                            <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?: '-' ?></td>
                                            <td><?= esc($row['created_at'] ?? '-') ?></td>
                                            <td><?= esc(ucfirst((string) ($row['status'] ?? '-'))) ?></td>
                                            <td>
                                                <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                    <form method="post" action="<?= site_url('/admin/branch-management/approval/service/approve/' . (int) $row['pending_service_id']) ?>" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (($approval_tab ?? '') === 'packages'): ?>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Date Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_packages ?? [])): ?>
                                    <tr><td colspan="6" class="text-center py-4">No pending packages found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pending_packages as $row): ?>
                                        <tr>
                                            <td><?= esc($row['package_name'] ?? '-') ?></td>
                                            <td><?= esc($row['description'] ?? '-') ?></td>
                                            <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?: '-' ?></td>
                                            <td><?= esc($row['created_at'] ?? '-') ?></td>
                                            <td><?= esc(ucfirst((string) ($row['status'] ?? '-'))) ?></td>
                                            <td>
                                                <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                    <form method="post" action="<?= site_url('/admin/branch-management/approval/package/approve/' . (int) $row['pending_package_id']) ?>" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>