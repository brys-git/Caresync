<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$activeTab = (string) ($active_tab ?? '');
if ($activeTab === '') {
    $activeTab = ($selected_status ?? '') !== '' ? 'monitoring' : 'record';
}
?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Payment Tracking</h1>
        <p class="text-muted mb-0">Record cash payments and verify pending GCash submissions.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'record' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#record-tab" type="button">Record Payment</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'initial' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#initial-tab" type="button">Initial Payments</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'monitoring' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#monitoring-tab" type="button">Monitoring</button></li>
        <li class="nav-item" role="presentation"><a class="nav-link" href="<?= base_url('branch-admin/payment-tracking?status=pending') ?>">Pending Verification</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade <?= $activeTab === 'record' ? 'show active' : '' ?>" id="record-tab">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Cash Advance Payment Entry</h5>
                    <form method="post" action="<?= base_url('branch-admin/payment-tracking/record-cash') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_method" value="cash">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="plan_id">Plan Holder</label>
                                <select id="plan_id" name="plan_id" class="form-select" required>
                                    <option value="">Select plan holder</option>
                                    <?php foreach ($plan_options as $plan): ?>
                                        <option value="<?= (int) $plan['plan_id'] ?>" data-monthly-fee="<?= esc((string) $plan['monthly_fee']) ?>">
                                            <?= esc((string) ($plan['first_name'] . ' ' . $plan['last_name'])) ?> (<?= esc((string) ($plan['unique_identifier'] ?: 'No ID')) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="months_covered">Months Covered</label>
                                <select id="months_covered" name="months_covered" class="form-select" required>
                                    <option value="1">1 Month</option>
                                    <option value="3">3 Months</option>
                                    <option value="6">6 Months</option>
                                    <option value="12">12 Months</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="amount">Amount</label>
                                <input id="amount" name="amount" type="number" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="payment_date">Payment Date</label>
                                <input id="payment_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="official_receipt_number">Official Receipt Number</label>
                                <input id="official_receipt_number" name="official_receipt_number" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Record Advance Payment</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'initial' ? 'show active' : '' ?>" id="initial-tab">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Record Initial Payment</h5>
                        <a href="<?= base_url('branch-admin/payment-tracking?tab=initial') ?>" class="btn btn-sm btn-outline-secondary">Refresh</a>
                    </div>
                    <form method="post" action="<?= base_url('branch-admin/payment-tracking/record-cash') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_method" value="cash">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="initial_plan_id">Client (Not Yet Active)</label>
                                <select id="initial_plan_id" name="plan_id" class="form-select" required>
                                    <option value="">Select client</option>
                                    <?php foreach (($initial_plan_options ?? []) as $plan): ?>
                                        <option value="<?= (int) $plan['plan_id'] ?>" data-monthly-fee="<?= esc((string) $plan['monthly_fee']) ?>">
                                            <?= esc((string) ($plan['first_name'] . ' ' . $plan['last_name'])) ?> (<?= esc((string) ($plan['unique_identifier'] ?: 'No ID')) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($initial_plan_options ?? [])): ?>
                                    <small class="text-muted">No inactive plan holders found for initial payment.</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="initial_months_covered">Months Covered</label>
                                <select id="initial_months_covered" name="months_covered" class="form-select" required>
                                    <option value="1">1 Month</option>
                                    <option value="3">3 Months</option>
                                    <option value="6">6 Months</option>
                                    <option value="12">12 Months</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="initial_amount">Amount</label>
                                <input id="initial_amount" name="amount" type="number" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="initial_payment_date">Payment Date</label>
                                <input id="initial_payment_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="initial_official_receipt_number">Official Receipt Number</label>
                                <input id="initial_official_receipt_number" name="official_receipt_number" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Record Initial Payment</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Initial Payments</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Plan Holder</th><th>Months</th><th>Amount</th><th>Date</th><th>Method</th><th>Reference / OR</th><th>Status</th><th>Proof</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                <?php foreach (($initial_rows ?? []) as $row): ?>
                                    <?php $status = strtolower((string) ($row['status'] ?? 'pending')); ?>
                                    <tr>
                                        <td><?= esc((string) ($row['first_name'] . ' ' . $row['last_name'])) ?><br><small class="text-muted"><?= esc((string) ($row['unique_identifier'] ?: 'No ID')) ?></small></td>
                                        <td><?= esc((string) ((int) ($row['months_covered'] ?? 1))) ?></td>
                                        <td>P<?= esc(number_format((float) $row['amount'], 2)) ?></td>
                                        <td><?= esc((string) $row['payment_date']) ?></td>
                                        <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                                        <td><?= esc((string) ($row['reference_number'] ?: ($row['official_receipt_number'] ?: '-'))) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>">
                                                <?= esc(ucfirst($status)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (! empty($row['proof_image'] ?? null)): ?>
                                                <a href="<?= base_url('uploads/payment-proofs/' . $row['proof_image']) ?>" target="_blank">View</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($status === 'pending'): ?>
                                                <form method="post" action="<?= base_url('branch-admin/payment-tracking/approve/' . (int) $row['payment_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                </form>
                                                <form method="post" action="<?= base_url('branch-admin/payment-tracking/reject/' . (int) $row['payment_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="text" name="rejection_reason" class="form-control form-control-sm d-inline-block" style="max-width: 180px;" placeholder="Rejection reason">
                                                    <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">No action</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($initial_rows ?? [])): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">No initial payment records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'monitoring' ? 'show active' : '' ?>" id="monitoring-tab">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Payment Monitoring</h5>
                        <form method="get" action="<?= base_url('branch-admin/payment-tracking') ?>">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" <?= ($selected_status ?? '') === '' ? 'selected' : '' ?>>All</option>
                                <option value="pending" <?= ($selected_status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= ($selected_status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="cancelled" <?= ($selected_status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Plan Holder</th><th>Months</th><th>Amount</th><th>Date</th><th>Method</th><th>Reference / OR</th><th>Status</th><th>Proof</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <?php $status = strtolower((string) ($row['status'] ?? 'pending')); ?>
                                    <tr>
                                        <td><?= esc((string) ($row['first_name'] . ' ' . $row['last_name'])) ?><br><small class="text-muted"><?= esc((string) ($row['unique_identifier'] ?: 'No ID')) ?></small></td>
                                        <td><?= esc((string) ((int) ($row['months_covered'] ?? 1))) ?></td>
                                        <td>P<?= esc(number_format((float) $row['amount'], 2)) ?></td>
                                        <td><?= esc((string) $row['payment_date']) ?></td>
                                        <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                                        <td><?= esc((string) ($row['reference_number'] ?: ($row['official_receipt_number'] ?: '-'))) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>">
                                                <?= esc(ucfirst($status)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (! empty($row['proof_image'] ?? null)): ?>
                                                <a href="<?= base_url('uploads/payment-proofs/' . $row['proof_image']) ?>" target="_blank">View</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($status === 'pending'): ?>
                                                <form method="post" action="<?= base_url('branch-admin/payment-tracking/approve/' . (int) $row['payment_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                </form>
                                                <form method="post" action="<?= base_url('branch-admin/payment-tracking/reject/' . (int) $row['payment_id']) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="text" name="rejection_reason" class="form-control form-control-sm d-inline-block" style="max-width: 180px;" placeholder="Rejection reason">
                                                    <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">No action</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">No payment records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const plan = document.getElementById('plan_id');
        const amount = document.getElementById('amount');
        const months = document.getElementById('months_covered');
        if (!plan || !amount) {
            return;
        }
        function updateAmount() {
            const selected = plan.options[plan.selectedIndex];
            const fee = selected ? Number(selected.getAttribute('data-monthly-fee') || 0) : 0;
            const monthsValue = months ? Number(months.value || 1) : 1;
            if (fee > 0) {
                amount.value = (fee * monthsValue).toFixed(2);
            }
        }
        plan.addEventListener('change', updateAmount);
        if (months) {
            months.addEventListener('change', updateAmount);
        }
        updateAmount();
    })();

    (function () {
        const plan = document.getElementById('initial_plan_id');
        const amount = document.getElementById('initial_amount');
        const months = document.getElementById('initial_months_covered');
        if (!plan || !amount) {
            return;
        }
        function updateAmount() {
            const selected = plan.options[plan.selectedIndex];
            const fee = selected ? Number(selected.getAttribute('data-monthly-fee') || 0) : 0;
            const monthsValue = months ? Number(months.value || 1) : 1;
            if (fee > 0) {
                amount.value = (fee * monthsValue).toFixed(2);
            }
        }
        plan.addEventListener('change', updateAmount);
        if (months) {
            months.addEventListener('change', updateAmount);
        }
        updateAmount();
    })();
</script>
<?= $this->endSection() ?>
