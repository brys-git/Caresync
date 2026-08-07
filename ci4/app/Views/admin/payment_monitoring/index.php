<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/payments.css') ?>">

<div class="pm">

    <!-- ====== Header ====== -->
    <div class="pm-header">
        <div>
            <h1 class="pm-header__title">Payment Monitoring</h1>
            <p class="pm-header__sub">System-wide transaction visibility across all branches.</p>
        </div>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="pm-alert pm-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="pm-alert pm-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== KPI Cards ====== -->
    <div class="pm-kpis">
        <div class="pm-kpi">
            <div class="pm-kpi__label">Total Collections</div>
            <div class="pm-kpi__value">₱<?= esc(number_format((float) ($total_collections ?? 0), 2)) ?></div>
        </div>
        <div class="pm-kpi">
            <div class="pm-kpi__label">Completed Transactions</div>
            <div class="pm-kpi__value"><?= (int) ($completed_count ?? 0) ?></div>
        </div>
        <div class="pm-kpi pm-kpi--warning">
            <div class="pm-kpi__label">Pending Verification</div>
            <i class="mdi mdi-alert-circle pm-kpi__icon"></i>
            <div class="pm-kpi__value"><?= (int) ($pending_count ?? 0) ?></div>
        </div>
        <div class="pm-kpi">
            <div class="pm-kpi__label">Primary Method</div>
            <div class="pm-kpi__chart">
                <div class="pm-kpi__donut" style="background:conic-gradient(var(--pm-blue) 0% <?= (int) ($primary_method_pct ?? 0) ?>%, #edf2f7 <?= (int) ($primary_method_pct ?? 0) ?>% 100%);"></div>
                <div class="pm-kpi__legend"><strong><?= esc($primary_method ?? 'N/A') ?>:</strong> <?= (int) ($primary_method_pct ?? 0) ?>%</div>
            </div>
        </div>
    </div>

    <!-- ====== Filter Bar ====== -->
    <div class="pm-filter-card">
        <form method="get" action="<?= site_url('admin/payment-monitoring') ?>" class="pm-filters">
            <div class="pm-filter-group pm-filter-group--status">
                <label class="pm-filter-label" for="pm-status">Status</label>
                <select id="pm-status" name="status" class="pm-select">
                    <option value="" <?= ($filters['status'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="pm-filter-group pm-filter-group--method">
                <label class="pm-filter-label" for="pm-method">Method</label>
                <select id="pm-method" name="payment_method" class="pm-select">
                    <option value="" <?= ($filters['payment_method'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                    <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="gcash" <?= ($filters['payment_method'] ?? '') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                </select>
            </div>

            <div class="pm-filter-group pm-filter-group--branch">
                <label class="pm-filter-label" for="pm-branch">Branch</label>
                <select id="pm-branch" name="branch_id" class="pm-select">
                    <option value="0">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                            <?= esc((string) $branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pm-filter-group pm-filter-group--date">
                <label class="pm-filter-label" for="pm-date-from">Date Range</label>
                <input id="pm-date-from" name="date_from" type="date" class="pm-input" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
            </div>
            <div class="pm-filter-group pm-filter-group--date">
                <label class="pm-filter-label">&nbsp;</label>
                <input name="date_to" type="date" class="pm-input" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
            </div>

            <div class="pm-filter-actions">
                <button type="submit" class="pm-btn pm-btn--purple pm-btn--sm">
                    <i class="mdi mdi-check"></i> Apply
                </button>
                <a href="<?= site_url('admin/payment-monitoring/export?' . http_build_query($filters ?? [])) ?>" class="pm-btn pm-btn--outline pm-btn--sm">
                    <i class="mdi mdi-download-outline"></i> Export CSV
                </a>
                <a href="#" class="pm-btn pm-btn--purple pm-btn--sm">
                    <i class="mdi mdi-plus"></i> Record Payment
                </a>
            </div>
        </form>
    </div>

    <!-- ====== Table ====== -->
    <div class="pm-card">
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plan Holder</th>
                        <th>Branch</th>
                        <th>Months</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference / OR</th>
                        <th>Status</th>
                        <th>Proof</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="12">
                                <div class="pm-empty">
                                    <i class="mdi mdi-credit-card-outline"></i>
                                    No payment records found for selected filters.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row):
                            $status = strtolower((string) ($row['status'] ?? 'pending'));
                            $method = strtolower((string) ($row['payment_method'] ?? 'cash'));
                            $refNumber = (string) ($row['reference_number'] ?? ($row['official_receipt_number'] ?? '-'));
                            $proofImage = (string) ($row['proof_image'] ?? '');
                            $remarks = (string) ($row['remarks'] ?? '-');
                        ?>
                        <tr>
                            <td><strong>#<?= esc((string) $row['payment_id']) ?></strong></td>
                            <td>
                                <div class="pm-holder">
                                    <span class="pm-holder__name"><?= esc(trim((string) ($row['first_name'] ?? '')) . ' ' . esc((string) ($row['last_name'] ?? ''))) ?></span>
                                    <span class="pm-holder__id"><?= esc((string) ($row['unique_identifier'] ?: 'No ID')) ?></span>
                                </div>
                            </td>
                            <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                            <td><?= esc((string) ((int) ($row['months_covered'] ?? 1))) ?></td>
                            <td><strong>₱<?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></strong></td>
                            <td><?= esc((string) ($row['payment_date'] ?? '-')) ?></td>
                            <td>
                                <span class="pm-method pm-method--<?= $method ?>">
                                    <i class="mdi <?= $method === 'gcash' ? 'mdi-cash' : 'mdi-cash-multiple' ?>"></i>
                                    <?= strtoupper($method) ?>
                                </span>
                            </td>
                            <td><?= esc($refNumber) ?></td>
                            <td>
                                <span class="pm-status pm-status--<?= $status ?>"><?= esc(ucfirst($status)) ?></span>
                            </td>
                            <td>
                                <?php if (! empty($supports_proof_upload) && $proofImage !== ''): ?>
                                    <a href="<?= site_url('uploads/payment-proofs/' . $proofImage) ?>" target="_blank" class="pm-proof-link">View Proof</a>
                                <?php else: ?>
                                    <span style="color:var(--pm-ink-faint);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= esc($remarks) ?>"><?= esc($remarks) ?></td>
                            <td>
                                <div class="pm-actions">
                                    <div class="pm-dropdown">
                                        <button class="pm-action-icon" onclick="pmToggleDropdown(this)" title="Actions" style="cursor:pointer;">
                                            <i class="mdi mdi-dots-horizontal"></i>
                                        </button>
                                        <div class="pm-dropdown__menu">
                                            <button class="pm-dropdown__item" onclick="pmCloseAllDropdowns()">
                                                <i class="mdi mdi-receipt-text-outline"></i> Print Receipt
                                            </button>
                                            <a href="<?= site_url('admin/client-management/view/' . (int) ($row['plan_holder_id'] ?? 0)) ?>" class="pm-dropdown__item">
                                                <i class="mdi mdi-eye-outline"></i> View Details
                                            </a>
                                            <button class="pm-dropdown__item" onclick="pmCloseAllDropdowns()">
                                                <i class="mdi mdi-pencil-outline"></i> Edit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    window.pmToggleDropdown = function (btn) {
        var menu = btn.nextElementSibling;
        var isOpen = menu.classList.contains('show');
        pmCloseAllDropdowns();
        if (!isOpen) menu.classList.add('show');
    };

    window.pmCloseAllDropdowns = function () {
        document.querySelectorAll('.pm-dropdown__menu.show').forEach(function (m) {
            m.classList.remove('show');
        });
    };

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.pm-dropdown')) pmCloseAllDropdowns();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') pmCloseAllDropdowns();
    });
})();
</script>
<?= $this->endSection() ?>
