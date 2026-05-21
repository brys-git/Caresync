<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Client Management</h1>
        <p class="text-muted mb-0">Review registration payment status and open plan holder details for activation approval.</p>
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
                            <th>Plan Holder</th>
                            <th>Unique ID</th>
                            <th>Plan Holder Status</th>
                            <th>Initial Payment</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holders as $holder): ?>
                            <?php
                                $holderStatus = strtolower((string) ($holder['plan_holder_status'] ?? 'inactive'));
                                $paymentStatus = strtolower((string) ($holder['initial_payment_status'] ?? 'none'));
                                $paymentLabel = 'No payment';
                                $paymentClass = 'secondary';
                                if ($paymentStatus === 'pending') {
                                    $paymentLabel = 'Pending Payment';
                                    $paymentClass = 'warning';
                                } elseif ($paymentStatus === 'paid') {
                                    $paymentLabel = 'Paid';
                                    $paymentClass = 'success';
                                } elseif ($paymentStatus === 'cancelled') {
                                    $paymentLabel = 'Cancelled';
                                    $paymentClass = 'danger';
                                }
                            ?>
                            <tr>
                                <td>
                                    <?= esc((string) ($holder['first_name'] . ' ' . $holder['last_name'])) ?><br>
                                    <small class="text-muted"><?= esc((string) ($holder['email'] ?? '-')) ?></small>
                                </td>
                                <td><?= esc((string) ($holder['unique_identifier'] ?: 'Not assigned')) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $holderStatus === 'active' ? 'success' : 'secondary' ?>">
                                        <?= esc(ucfirst($holderStatus)) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= esc($paymentClass) ?>">
                                        <?= esc($paymentLabel) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('branch-admin/client-management/view/' . (int) $holder['plan_holder_id']) ?>" class="btn btn-sm btn-outline-primary me-1">View Details</a>
                                    <?php if ($paymentStatus === 'pending'): ?>
                                        <form method="post" action="<?= base_url('branch-admin/client-management/approve/' . (int) $holder['plan_holder_id']) ?>" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to approve this initial payment? This will activate the membership.')">
                                            <button type="submit" class="btn btn-sm btn-success">Approve Payment</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($holders)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No plan holders found for your branch.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
