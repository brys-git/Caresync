<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/branch-management.css') ?>">

<div class="bm">

    <!-- ====== Header ====== -->
    <div class="bm-header">
        <div class="bm-header__text">
            <h1 class="bm-header__title">Branch Management</h1>
            <p class="bm-header__sub">Monitor branch availability, operations, contribution, and approval queues.</p>
        </div>
        <div class="bm-header__actions">
            <a href="<?= site_url('/admin/branches') ?>" class="bm-btn bm-btn--outline">
                <i class="mdi mdi-plus"></i> Add Branch
            </a>
            <a href="<?= site_url('/admin/users/create') ?>" class="bm-btn bm-btn--filled">
                <i class="mdi mdi-plus"></i> Create Account
            </a>
        </div>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="bm-alert bm-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="bm-alert bm-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== Tabs ====== -->
    <?php
        $pendingCount = (int) ($pending_service_count ?? 0) + (int) ($pending_package_count ?? 0);
    ?>
    <div class="bm-tabs" role="tablist">
        <a href="<?= site_url('/admin/branch-management?tab=availability') ?>"
           class="bm-tab <?= ($tab ?? '') === 'availability' ? 'bm-tab--active' : '' ?>"
           role="tab" aria-selected="<?= ($tab ?? '') === 'availability' ? 'true' : 'false' ?>">
            <i class="mdi mdi-office-building-outline"></i>
            Branch Services / Availability
        </a>
        <a href="<?= site_url('/admin/branch-management?tab=transactions') ?>"
           class="bm-tab <?= ($tab ?? '') === 'transactions' ? 'bm-tab--active' : '' ?>"
           role="tab" aria-selected="<?= ($tab ?? '') === 'transactions' ? 'true' : 'false' ?>">
            <i class="mdi mdi-swap-horizontal"></i>
            Branch Transactions
        </a>
        <a href="<?= site_url('/admin/branch-management?tab=contribution') ?>"
           class="bm-tab <?= ($tab ?? '') === 'contribution' ? 'bm-tab--active' : '' ?>"
           role="tab" aria-selected="<?= ($tab ?? '') === 'contribution' ? 'true' : 'false' ?>">
            <i class="mdi mdi-cash-multiple"></i>
            Branch Contribution
        </a>
        <a href="<?= site_url('/admin/branch-management?tab=approval') ?>"
           class="bm-tab <?= ($tab ?? '') === 'approval' ? 'bm-tab--active' : '' ?>"
           role="tab" aria-selected="<?= ($tab ?? '') === 'approval' ? 'true' : 'false' ?>">
            <i class="mdi mdi-clipboard-check-outline"></i>
            Approvals
            <?php if ($pendingCount > 0): ?>
                <span class="bm-tab__badge"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Branch Services / Availability                              -->
    <!-- ================================================================ -->
    <?php if (($tab ?? '') === 'availability'): ?>
        <div class="bm-card">
            <!-- Filter bar -->
            <form method="get" class="bm-filters">
                <input type="hidden" name="tab" value="availability">

                <div class="bm-filter-group bm-filter-group--branch">
                    <label class="bm-filter-label" for="bm-branch">Branch</label>
                    <select name="branch_id" id="bm-branch" class="bm-select">
                        <option value="">All branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($selected_branch_id ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bm-filter-group bm-filter-group--type">
                    <label class="bm-filter-label" for="bm-type-filter">Type</label>
                    <select id="bm-type-filter" class="bm-select" onchange="bmFilterTable()">
                        <option value="">All types</option>
                        <option value="Service">Service</option>
                        <option value="Package">Package</option>
                    </select>
                </div>

                <div class="bm-filter-group bm-filter-group--status">
                    <label class="bm-filter-label" for="bm-status-filter">Status</label>
                    <select id="bm-status-filter" class="bm-select" onchange="bmFilterTable()">
                        <option value="">All statuses</option>
                        <option value="Available">Available</option>
                        <option value="Not Available">Not Available</option>
                    </select>
                </div>

                <div class="bm-filter-group bm-filter-group--search">
                    <label class="bm-filter-label" for="bm-search">Quick search</label>
                    <div class="bm-search-wrap">
                        <i class="mdi mdi-magnify"></i>
                        <input type="text" id="bm-search" class="bm-input" placeholder="Search services or packages..." oninput="bmFilterTable()">
                    </div>
                </div>

                <div class="bm-filter-group" style="justify-content:flex-end;">
                    <label class="bm-filter-label">&nbsp;</label>
                    <button type="submit" class="bm-btn bm-btn--outline bm-btn--sm">
                        <i class="mdi mdi-filter-variant"></i> Apply
                    </button>
                </div>
            </form>

            <!-- Table -->
            <div class="bm-table-wrap">
                <table class="bm-table" id="bm-availability-table">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Service / Package Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($availability_rows ?? [])): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="bm-empty">
                                        <i class="mdi mdi-package-variant-closed"></i>
                                        No services or packages found.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($availability_rows as $row): ?>
                                <tr
                                    data-type="<?= esc($row['item_type'] ?? '') ?>"
                                    data-status="<?= esc($row['status_label'] ?? '') ?>"
                                    data-search="<?= strtolower(esc(($row['item_name'] ?? '') . ' ' . ($row['branch_name'] ?? ''))) ?>"
                                >
                                    <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                    <td><strong><?= esc($row['item_name'] ?? '-') ?></strong></td>
                                    <td>
                                        <?php if (($row['item_type'] ?? '') === 'Service'): ?>
                                            <span class="bm-badge bm-badge--teal">Service</span>
                                        <?php else: ?>
                                            <span class="bm-badge bm-badge--slate">Package</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) ($row['is_available'] ?? 0) === 1): ?>
                                            <span class="bm-badge bm-badge--green">Available</span>
                                        <?php else: ?>
                                            <span class="bm-badge bm-badge--red">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <form method="post" action="<?= site_url('/admin/branch-management/toggle-availability') ?>" style="margin:0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="item_type" value="<?= esc(strtolower((string) ($row['item_type'] ?? 'service'))) ?>">
                                                <input type="hidden" name="item_id" value="<?= (int) ($row['item_id'] ?? 0) ?>">
                                                <input type="hidden" name="branch_id" value="<?= (int) ($selected_branch_id ?? 0) ?>">
                                                <input type="hidden" name="return_tab" value="availability">
                                                <input type="hidden" name="is_available" value="<?= (int) ($row['is_available'] ?? 0) === 1 ? 0 : 1 ?>">
                                                <button type="submit" class="bm-btn <?= (int) ($row['is_available'] ?? 0) === 1 ? 'bm-btn--danger-outline' : 'bm-btn--success-outline' ?> bm-btn--sm">
                                                    <?= (int) ($row['is_available'] ?? 0) === 1 ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                            <div class="dropdown">
                                                <button class="bm-btn bm-btn--ghost bm-btn--sm" data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
                                                    <i class="mdi mdi-dots-horizontal"></i> More
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" style="min-width:160px;">
                                                    <li><span class="dropdown-item-text text-muted small"><?= esc($row['item_name'] ?? '') ?></span></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="post" action="<?= site_url('/admin/branch-management/toggle-availability') ?>" style="margin:0;">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="item_type" value="<?= esc(strtolower((string) ($row['item_type'] ?? 'service'))) ?>">
                                                            <input type="hidden" name="item_id" value="<?= (int) ($row['item_id'] ?? 0) ?>">
                                                            <input type="hidden" name="branch_id" value="<?= (int) ($selected_branch_id ?? 0) ?>">
                                                            <input type="hidden" name="return_tab" value="availability">
                                                            <input type="hidden" name="is_available" value="<?= (int) ($row['is_available'] ?? 0) === 1 ? 0 : 1 ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="mdi mdi-<?= (int) ($row['is_available'] ?? 0) === 1 ? 'close-circle-outline text-danger' : 'check-circle-outline text-success' ?> me-1"></i>
                                                                <?= (int) ($row['is_available'] ?? 0) === 1 ? 'Disable' : 'Enable' ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="bm-footer">
                <span>Showing <strong id="bm-row-count"><?= count($availability_rows ?? []) ?></strong> records</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Branch Transactions                                         -->
    <!-- ================================================================ -->
    <?php if (($tab ?? '') === 'transactions'): ?>
        <div class="bm-card">
            <!-- Filter bar -->
            <form method="get" class="bm-filters">
                <input type="hidden" name="tab" value="transactions">

                <div class="bm-filter-group bm-filter-group--branch">
                    <label class="bm-filter-label" for="bm-tx-branch">Branch</label>
                    <select name="branch_id" id="bm-tx-branch" class="bm-select">
                        <option value="">All branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($selected_branch_id ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bm-filter-group">
                    <label class="bm-filter-label" for="bm-date-from">Date From</label>
                    <input type="date" name="date_from" id="bm-date-from" class="bm-input" value="<?= esc($date_from ?? '') ?>">
                </div>

                <div class="bm-filter-group">
                    <label class="bm-filter-label" for="bm-date-to">Date To</label>
                    <input type="date" name="date_to" id="bm-date-to" class="bm-input" value="<?= esc($date_to ?? '') ?>">
                </div>

                <div class="bm-filter-group" style="flex-direction:row;gap:8px;align-items:flex-end;">
                    <button type="submit" class="bm-btn bm-btn--outline bm-btn--sm">
                        <i class="mdi mdi-filter-variant"></i> Filter
                    </button>
                    <a class="bm-btn bm-btn--ghost bm-btn--sm"
                       href="<?= site_url('/admin/branch-management/export-transactions?branch_id=' . (int) ($selected_branch_id ?? 0) . '&date_from=' . rawurlencode((string) ($date_from ?? '')) . '&date_to=' . rawurlencode((string) ($date_to ?? ''))) ?>">
                        <i class="mdi mdi-download-outline"></i> Export CSV
                    </a>
                </div>
            </form>

            <!-- Table -->
            <div class="bm-table-wrap">
                <table class="bm-table">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Plan Holder</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions ?? [])): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="bm-empty">
                                        <i class="mdi mdi-swap-horizontal"></i>
                                        No transactions found for the selected criteria.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?= esc($row['branch_name'] ?? '-') ?></td>
                                    <td><strong><?= esc($row['plan_holder'] ?? '-') ?></strong></td>
                                    <td>
                                        <?php if (($row['transaction_type'] ?? '') === 'Payment'): ?>
                                            <span class="bm-badge bm-badge--green">Payment</span>
                                        <?php else: ?>
                                            <span class="bm-badge bm-badge--teal">Service</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                                    <td><?= esc($row['transaction_date'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                            $txStatus = strtolower((string) ($row['status'] ?? ''));
                                            $badgeClass = match ($txStatus) {
                                                'approved', 'paid' => 'bm-badge--green',
                                                'pending' => 'bm-badge--amber',
                                                'rejected', 'failed' => 'bm-badge--red',
                                                default => 'bm-badge--slate',
                                            };
                                        ?>
                                        <span class="bm-badge <?= $badgeClass ?>"><?= esc(ucfirst($row['status'] ?? '-')) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="bm-footer">
                <span>Showing <strong><?= count($transactions ?? []) ?></strong> records</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Branch Contribution                                         -->
    <!-- ================================================================ -->
    <?php if (($tab ?? '') === 'contribution'): ?>
        <div class="bm-card">
            <!-- Filter bar -->
            <form method="get" class="bm-filters">
                <input type="hidden" name="tab" value="contribution">

                <div class="bm-filter-group bm-filter-group--branch">
                    <label class="bm-filter-label" for="bm-cb-branch">Branch</label>
                    <select name="branch_id" id="bm-cb-branch" class="bm-select">
                        <option value="">All branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($selected_branch_id ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc($branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bm-filter-group" style="align-self:flex-end;">
                    <button type="submit" class="bm-btn bm-btn--outline bm-btn--sm">
                        <i class="mdi mdi-filter-variant"></i> Filter
                    </button>
                </div>
            </form>

            <!-- Table -->
            <div class="bm-table-wrap">
                <table class="bm-table">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Total Payments Collected</th>
                            <th>Staff Commission (10%)</th>
                            <th>Total Remitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contributions ?? [])): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="bm-empty">
                                        <i class="mdi mdi-cash-multiple"></i>
                                        No contribution data found.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contributions as $row): ?>
                                <tr>
                                    <td><strong><?= esc($row['branch_name'] ?? '-') ?></strong></td>
                                    <td>₱<?= esc((string) ($row['total_collected'] ?? '0.00')) ?></td>
                                    <td>₱<?= esc((string) ($row['staff_commission'] ?? '0.00')) ?></td>
                                    <td>₱<?= esc((string) ($row['total_remitted'] ?? '0.00')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="bm-footer">
                <span>Showing <strong><?= count($contributions ?? []) ?></strong> branch<?= count($contributions ?? []) !== 1 ? 'es' : '' ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Approvals                                                  -->
    <!-- ================================================================ -->
    <?php if (($tab ?? '') === 'approval'): ?>
        <div class="bm-card">
            <!-- Approval sub-tabs -->
            <div class="bm-subtabs">
                <a href="<?= site_url('/admin/branch-management?tab=approval&approval_tab=services') ?>"
                   class="bm-subtab <?= ($approval_tab ?? '') === 'services' ? 'bm-subtab--active' : '' ?>">
                    <i class="mdi mdi-wrench-outline"></i> Pending
                    <?php if (($pending_service_count ?? 0) > 0): ?>
                        <span class="bm-badge bm-badge--amber" style="margin-left:4px;"><?= $pending_service_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= site_url('/admin/branch-management?tab=approval&approval_tab=packages') ?>"
                   class="bm-subtab <?= ($approval_tab ?? '') === 'packages' ? 'bm-subtab--active' : '' ?>">
                    <i class="mdi mdi-package-variant"></i> Approved
                    <?php if (($pending_package_count ?? 0) > 0): ?>
                        <span class="bm-badge bm-badge--green" style="margin-left:4px;"><?= $pending_package_count ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php
                $items = ($approval_tab ?? '') === 'services' ? ($pending_services ?? []) : ($pending_packages ?? []);
            ?>

            <div>
                <?php if (empty($items)): ?>
                    <div class="bm-empty">
                        <i class="mdi mdi-clipboard-check-outline"></i>
                        No pending items found.
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <?php
                            $isService = ($approval_tab ?? '') === 'services';
                            $itemId = $isService ? (int) ($row['pending_service_id'] ?? 0) : (int) ($row['pending_package_id'] ?? 0);
                            $itemName = $isService ? ($row['service_name'] ?? '-') : ($row['package_name'] ?? '-');
                            $creatorName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: 'Unknown';
                            $itemStatus = $row['status'] ?? 'pending';
                        ?>
                        <div class="bm-approval-row">
                            <div class="bm-approval-row__icon <?= $isService ? 'bm-approval-row__icon--service' : 'bm-approval-row__icon--package' ?>">
                                <i class="mdi <?= $isService ? 'mdi-wrench-outline' : 'mdi-package-variant' ?>"></i>
                            </div>
                            <div class="bm-approval-row__body">
                                <div class="bm-approval-row__name"><?= esc($itemName) ?></div>
                                <div class="bm-approval-row__meta">
                                    <?= $isService ? 'Service' : 'Package' ?>
                                    &middot; <?= esc($row['created_at'] ?? '-') ?>
                                    &middot; by <?= esc($creatorName) ?>
                                </div>
                            </div>
                            <span class="bm-badge <?= $itemStatus === 'pending' ? 'bm-badge--amber' : ($itemStatus === 'approved' ? 'bm-badge--green' : 'bm-badge--red') ?>">
                                <?= esc(strtoupper($itemStatus)) ?>
                            </span>
                            <div class="bm-approval-row__actions">
                                <?php if ($itemStatus === 'pending'): ?>
                                    <form method="post" action="<?= site_url('/admin/branch-management/approval/' . ($isService ? 'service' : 'package') . '/approve/' . $itemId) ?>" style="margin:0;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="bm-btn bm-btn--success-outline bm-btn--sm">
                                            <i class="mdi mdi-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="post" action="<?= site_url('/admin/branch-management/approval/' . ($isService ? 'service' : 'package') . '/reject/' . $itemId) ?>" style="margin:0;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="bm-btn bm-btn--danger-outline bm-btn--sm">
                                            <i class="mdi mdi-close"></i> Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">Processed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ====== Client-side filter for Availability tab ====== -->
<script>
(function () {
    window.bmFilterTable = function () {
        var typeFilter = (document.getElementById('bm-type-filter') || {}).value || '';
        var statusFilter = (document.getElementById('bm-status-filter') || {}).value || '';
        var searchVal = ((document.getElementById('bm-search') || {}).value || '').toLowerCase().trim();
        var rows = document.querySelectorAll('#bm-availability-table tbody tr[data-type]');
        var visible = 0;

        rows.forEach(function (row) {
            var matchType = !typeFilter || row.getAttribute('data-type') === typeFilter;
            var matchStatus = !statusFilter || row.getAttribute('data-status') === statusFilter;
            var matchSearch = !searchVal || (row.getAttribute('data-search') || '').indexOf(searchVal) !== -1;

            if (matchType && matchStatus && matchSearch) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        var countEl = document.getElementById('bm-row-count');
        if (countEl) {
            countEl.textContent = visible;
        }
    };
})();
</script>

<?= $this->endSection() ?>
