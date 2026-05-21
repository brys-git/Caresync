<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Registration Approvals</h1>
        <p class="text-muted mb-0">Pending initial payments requiring verification before full access activation.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>#<?= esc((string) $row['payment_id']) ?></td>
                                <td>
                                    <?= esc((string) $row['first_name'] . ' ' . $row['last_name']) ?><br>
                                    <small class="text-muted"><?= esc((string) $row['email']) ?></small>
                                </td>
                                <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                <td><?= esc((string) $row['payment_date']) ?></td>
                                <td>P<?= esc(number_format((float) $row['amount'], 2)) ?></td>
                                <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                                <td><?= esc((string) ($row['reference_number'] ?: '-')) ?></td>
                                <td><span class="badge text-bg-warning">Pending</span></td>
                                <td class="text-end">
                                    <?php if (! empty($can_verify)): ?>
                                        <form method="post" action="<?= base_url('payments/verify-initial/' . (int) $row['payment_id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-primary">Verify</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">View only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No pending registrations for approval.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
