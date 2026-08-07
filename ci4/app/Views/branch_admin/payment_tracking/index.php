<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/payments.css') ?>">

<div class="pm">

    <!-- ====== Header ====== -->
    <div class="pm-header">
        <div>
            <h1 class="pm-header__title">Payment Records</h1>
            <p class="pm-header__sub">View all payment records for this branch in a single table.</p>
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
        <form method="get" action="<?= site_url('branch-admin/payment-tracking') ?>" class="pm-filters">
            <div class="pm-filter-group pm-filter-group--status">
                <label class="pm-filter-label" for="pm-status">Status</label>
                <select id="pm-status" name="status" class="pm-select">
                    <option value="" <?= ($selected_status ?? '') === '' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= ($selected_status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= ($selected_status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="cancelled" <?= ($selected_status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="pm-filter-group pm-filter-group--method">
                <label class="pm-filter-label">&nbsp;</label>
                <select class="pm-select" disabled style="opacity:0.5;">
                    <option>All Methods</option>
                </select>
            </div>

            <div class="pm-filter-group pm-filter-group--branch">
                <label class="pm-filter-label">&nbsp;</label>
                <input type="text" class="pm-input" value="<?= esc(session('branch_name') ?? 'Current Branch') ?>" disabled style="opacity:0.5;">
            </div>

            <div class="pm-filter-actions">
                <button type="submit" class="pm-btn pm-btn--purple pm-btn--sm">
                    <i class="mdi mdi-check"></i> Apply
                </button>
                <a href="<?= site_url('branch-admin/cash-payment-record') ?>" class="pm-btn pm-btn--purple pm-btn--sm">
                    <i class="mdi mdi-plus"></i> Record Cash
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
                                    No payment records found.
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
                            $paymentId = (int) ($row['payment_id'] ?? 0);
                        ?>
                        <tr>
                            <td><strong>#<?= esc((string) $paymentId) ?></strong></td>
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
                                <?php if (! empty($supports_proof_upload ?? false) && $proofImage !== ''): ?>
                                    <a href="<?= site_url('uploads/payment-proofs/' . $proofImage) ?>" target="_blank" class="pm-proof-link">View Proof</a>
                                <?php else: ?>
                                    <span style="color:var(--pm-ink-faint);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= esc($remarks) ?>"><?= esc($remarks) ?></td>
                            <td>
                                <div class="pm-actions">
                                    <?php if ($status === 'pending' && ($can_approve ?? false)): ?>
                                        <form method="post" action="<?= site_url('branch-admin/payment-tracking/approve/' . $paymentId) ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="pm-btn pm-btn--sm" style="background:var(--pm-green);color:#fff;border-color:var(--pm-green);font-size:0.7rem;padding:5px 10px;">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="<?= site_url('branch-admin/payment-tracking/reject/' . $paymentId) ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="pm-btn pm-btn--sm" style="background:var(--pm-red);color:#fff;border-color:var(--pm-red);font-size:0.7rem;padding:5px 10px;">
                                                <i class="mdi mdi-close"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <div class="pm-dropdown">
                                        <button class="pm-action-icon" onclick="pmToggleDropdown(this)" title="Actions" style="cursor:pointer;">
                                            <i class="mdi mdi-dots-horizontal"></i>
                                        </button>
                                        <div class="pm-dropdown__menu">
                                            <button class="pm-dropdown__item" onclick="pmCloseAllDropdowns()">
                                                <i class="mdi mdi-receipt-text-outline"></i> Print Receipt
                                            </button>
                                            <button class="pm-dropdown__item" onclick="pmCloseAllDropdowns()">
                                                <i class="mdi mdi-eye-outline"></i> View Details
                                            </button>
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
