<div class="card">
    <div class="card-header">Payment Monitoring</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Plan Holder Name</th>
                        <th>Plan Details</th>
                        <th>Amount Paid</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-3">No payment records found for your branch.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <?php $status = strtolower((string) ($payment['status'] ?? 'pending')); ?>
                            <tr>
                                <td><?= esc(trim(((string) ($payment['first_name'] ?? '')) . ' ' . ((string) ($payment['last_name'] ?? '')))) ?></td>
                                <td>
                                    <div class="fw-semibold">Plan #<?= (int) ($payment['plan_id'] ?? 0) ?></div>
                                    <small class="text-muted">
                                        Monthly Fee: <?= number_format((float) ($payment['monthly_fee'] ?? 0), 2) ?> |
                                        Remaining: <?= number_format((float) ($payment['remaining_balance'] ?? 0), 2) ?>
                                    </small>
                                </td>
                                <td><?= number_format((float) ($payment['amount'] ?? 0), 2) ?></td>
                                <td><?= esc((string) ($payment['payment_date'] ?? '')) ?></td>
                                <td>
                                    <?php if ($status === 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($status === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(ucfirst($status)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $planHolderId = (int) ($payment['plan_holder_id'] ?? 0); ?>
                                    <?php if ($planHolderId > 0): ?>
                                        <a href="<?= site_url('/staff/client/view/' . $planHolderId) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('/staff/payments/edit/' . (int) ($payment['payment_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
