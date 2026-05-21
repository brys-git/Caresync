<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Payment Tracking</h4>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'monitoring') ? 'active' : '' ?>" href="<?= site_url('/branch-admin/payment-tracking?tab=monitoring' . ($selected_plan_id ? '&plan_id=' . (int) $selected_plan_id : '')) ?>">Payment Monitoring</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'record') ? 'active' : '' ?>" href="<?= site_url('/branch-admin/payment-tracking?tab=record') ?>">Record Payment</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'update') ? 'active' : '' ?>" href="<?= site_url('/branch-admin/payment-tracking?tab=update') ?>">Update Payment</a>
        </li>
    </ul>

    <?php if ($active_tab === 'monitoring'): ?>
        <div class="card mb-3">
            <div class="card-header">Plan Payment Monitoring</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Plan ID</th>
                                <th>Client Name</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Remaining</th>
                                <th>Months Paid</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3">No plans found for your branch.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td>#<?= (int) $plan['plan_id'] ?></td>
                                        <td><?= esc($plan['client_name'] ?? 'N/A') ?></td>
                                        <td><?= number_format((float) ($plan['total_amount'] ?? 0), 2) ?></td>
                                        <td><?= number_format((float) ($plan['paid_amount'] ?? 0), 2) ?></td>
                                        <td><?= number_format((float) ($plan['remaining_balance'] ?? 0), 2) ?></td>
                                        <td><?= (int) ($plan['months_paid'] ?? 0) ?></td>
                                        <td>
                                            <?php $status = $plan_statuses[(int) $plan['plan_id']] ?? 'Unpaid'; ?>
                                            <?php if ($status === 'Fully Paid'): ?>
                                                <span class="badge bg-success">Fully Paid</span>
                                            <?php elseif ($status === 'Active'): ?>
                                                <span class="badge bg-primary">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= site_url('/branch-admin/payment-tracking?tab=monitoring&plan_id=' . (int) $plan['plan_id']) ?>" class="btn btn-sm btn-outline-secondary">View History</a>
                                            <a href="<?= site_url('/branch-admin/payment-tracking?tab=record&plan_id=' . (int) $plan['plan_id']) ?>" class="btn btn-sm btn-outline-primary">Record</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (! empty($payment_history)): ?>
            <div class="card">
                <div class="card-header">Payment History (Plan #<?= (int) $selected_plan_id ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_history as $history): ?>
                                    <tr>
                                        <td><?= esc($history['payment_date'] ?? '') ?></td>
                                        <td><?= number_format((float) ($history['amount'] ?? 0), 2) ?></td>
                                        <td><?= esc($history['payment_method'] ?? '') ?></td>
                                        <td><?= esc(ucfirst($history['status'] ?? '')) ?></td>
                                        <td><?= esc($history['reference_number'] ?? '-') ?></td>
                                        <td><?= esc($history['remarks'] ?? '-') ?></td>
                                        <td>
                                            <a href="<?= site_url('/branch-admin/payment-tracking/edit/' . (int) $history['payment_id']) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif ($selected_plan_id): ?>
            <div class="alert alert-info">No payment records found for this plan.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($active_tab === 'record'): ?>
        <div class="card">
            <div class="card-header">Record New Payment</div>
            <div class="card-body">
                <form action="<?= site_url('/branch-admin/payment-tracking/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <?php
                        $selectedPlanId = (int) old('plan_id', $selected_plan_id);
                    ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="branch_plan_id" class="form-label">Plan Holder</label>
                            <select id="branch_plan_id" name="plan_id" class="form-select" required>
                                <option value="">Select plan holder</option>
                                <?php foreach (($plan_holder_choices ?? []) as $choice): ?>
                                    <?php $planId = (int) ($choice['plan_id'] ?? 0); ?>
                                    <option value="<?= $planId > 0 ? $planId : '' ?>" <?= $selectedPlanId === $planId ? 'selected' : '' ?> <?= $planId <= 0 ? 'disabled' : '' ?>>
                                        <?= esc((string) ($choice['label'] ?? 'N/A')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">All branch plan holders are listed. Entries marked No Plan Yet cannot be selected for payment recording.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= esc(old('amount')) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= esc(old('payment_date', date('Y-m-d'))) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash" <?= old('payment_method') === 'cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="gcash" <?= old('payment_method') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="paid" <?= old('status', 'paid') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="pending" <?= old('status') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="cancelled" <?= old('status') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" value="<?= esc(old('reference_number')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"><?= esc(old('remarks')) ?></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save Payment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'update'): ?>
        <div class="card">
            <div class="card-header">Update Existing Payment</div>
            <div class="card-body">
                <?php if (! empty($selected_payment)): ?>
                    <form action="<?= site_url('/branch-admin/payment-tracking/update/' . (int) $selected_payment['payment_id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Plan ID</label>
                                <input type="text" class="form-control" value="#<?= (int) $selected_payment['plan_id'] ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= esc(old('amount', $selected_payment['amount'])) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= esc(old('payment_date', $selected_payment['payment_date'])) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash" <?= old('payment_method', $selected_payment['payment_method']) === 'cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="gcash" <?= old('payment_method', $selected_payment['payment_method']) === 'gcash' ? 'selected' : '' ?>>GCash</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="paid" <?= old('status', $selected_payment['status']) === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="pending" <?= old('status', $selected_payment['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="cancelled" <?= old('status', $selected_payment['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" value="<?= esc(old('reference_number', $selected_payment['reference_number'] ?? '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3"><?= esc(old('remarks', $selected_payment['remarks'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning">Update Payment</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info mb-0">Select a payment from Monitoring tab history and click Edit to update it.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
