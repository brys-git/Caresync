<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h4 mb-3">Payment Management</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Record Payment</h2>
            <form method="post" action="<?= base_url('payments/record') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="plan_id">Plan</label>
                        <select id="plan_id" name="plan_id" class="form-select" required>
                            <option value="">Select plan</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= esc((string) $plan['plan_id']) ?>">
                                    Plan #<?= esc((string) $plan['plan_id']) ?> - <?= esc($plan['first_name'] . ' ' . $plan['last_name']) ?>
                                    (ID: <?= esc($plan['unique_identifier']) ?> | Balance: <?= esc((string) $plan['remaining_balance']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="amount">Amount</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="payment_date">Payment Date</label>
                        <input id="payment_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="reference_number">Reference Number</label>
                        <input id="reference_number" name="reference_number" type="text" class="form-control" placeholder="Required for GCash">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Save Payment</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-3">Payment History</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Branch</th>
                            <th>Received By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= esc($payment['payment_date']) ?></td>
                                <td>#<?= esc((string) $payment['plan_id']) ?></td>
                                <td>
                                    <?= esc($payment['first_name'] . ' ' . $payment['last_name']) ?><br>
                                    <small class="text-muted"><?= esc($payment['unique_identifier']) ?></small>
                                </td>
                                <td><?= esc((string) $payment['amount']) ?></td>
                                <td><?= esc(strtoupper((string) $payment['payment_method'])) ?></td>
                                <td><?= esc((string) ($payment['reference_number'] ?? '-')) ?></td>
                                <td><?= esc((string) $payment['branch_id']) ?></td>
                                <td><?= esc($payment['receiver_first_name'] . ' ' . $payment['receiver_last_name']) ?></td>
                                <td><?= esc((string) $payment['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const method = document.getElementById('payment_method');
        const reference = document.getElementById('reference_number');

        function syncReferenceRequirement() {
            const requiresReference = method.value === 'gcash';
            reference.required = requiresReference;
            if (!requiresReference) {
                reference.value = '';
            }
        }

        method.addEventListener('change', syncReferenceRequirement);
        syncReferenceRequirement();
    })();
</script>
<?= $this->endSection() ?>
