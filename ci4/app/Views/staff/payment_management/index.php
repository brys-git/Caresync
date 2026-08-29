<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Payment Management</h1>
        <p class="text-muted mb-0">Record cash payments and monitor transaction statuses.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Cash Advance Payment Entry</h5>
            <form method="post" action="<?= base_url('staff/payment-management/record-cash') ?>">
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
                        <label class="form-label" for="official_receipt_number">Temporary Receipt Number</label>
                        <input id="official_receipt_number" name="official_receipt_number" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Record Advance Payment</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Payment Records</h5>
                <form method="get" action="<?= base_url('staff/payment-management') ?>">
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
                    <thead><tr><th>Plan Holder</th><th>Months</th><th>Amount</th><th>Date</th><th>Method</th><th>Reference / OR</th><th>Status</th></tr></thead>
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
                                <td><span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>"><?= esc(ucfirst($status)) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No payment records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
</script>
<?= $this->endSection() ?>
