<div class="card">
    <div class="card-header">Update Payment</div>
    <div class="card-body">
        <?php if (! empty($selected_payment)): ?>
            <form action="<?= site_url('/staff/payments/update/' . (int) $selected_payment['payment_id']) ?>" method="post">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Plan ID</label>
                        <input type="text" class="form-control" value="#<?= (int) $selected_payment['plan_id'] ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Payment Date</label>
                        <input type="text" class="form-control" value="<?= esc((string) ($selected_payment['payment_date'] ?? '')) ?>" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control" value="<?= esc(old('amount', $selected_payment['amount'])) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="form-select" required>
                            <option value="cash" <?= old('payment_method', $selected_payment['payment_method']) === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="gcash" <?= old('payment_method', $selected_payment['payment_method']) === 'gcash' ? 'selected' : '' ?>>GCash</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" id="reference_number" name="reference_number" class="form-control" value="<?= esc(old('reference_number', $selected_payment['reference_number'] ?? '')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="paid" <?= old('status', $selected_payment['status']) === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="pending" <?= old('status', $selected_payment['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="cancelled" <?= old('status', $selected_payment['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3"><?= esc(old('remarks', $selected_payment['remarks'] ?? '')) ?></textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning">Update Payment</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info mb-0">Select a payment from monitoring and click Edit to update it.</div>
        <?php endif; ?>
    </div>
</div>
