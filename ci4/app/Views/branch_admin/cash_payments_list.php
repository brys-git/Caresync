<?= $this->extend('layouts/admin_base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3">Cash Payments Recorded</h1>
        <p class="text-muted">List of cash payments waiting for client verification.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Client Name</th>
                            <th>Months</th>
                            <th>Amount</th>
                            <th>Reference / OR</th>
                            <th>Date Recorded</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No cash payments recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= esc((string) $payment['client_name']) ?></td>
                                    <td><?= (int) $payment['months_covered'] ?></td>
                                    <td>₱<?= number_format((float) $payment['amount'], 2) ?></td>
                                    <td><code><?= esc((string) $payment['receipt_number']) ?></code></td>
                                    <td><?= $payment['recorded_date'] ?></td>
                                    <td>
                                        <?php if ($payment['verified']): ?>
                                            <span class="badge bg-success">Verified</span>
                                            <br><small class="text-muted"><?= $payment['verified_date'] ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending Verification</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <a href="<?= base_url('branch-admin/cash-payment-record') ?>" class="btn btn-primary btn-sm">Record New Payment</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
