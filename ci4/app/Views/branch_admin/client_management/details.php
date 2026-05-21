<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $holderStatus = strtolower((string) ($holder['plan_holder_status'] ?? 'inactive'));
    $paymentStatus = strtolower((string) ($initial_payment['status'] ?? 'none'));
    $paymentClass = $paymentStatus === 'paid' ? 'success' : ($paymentStatus === 'pending' ? 'warning' : ($paymentStatus === 'cancelled' ? 'danger' : 'secondary'));
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Plan Holder Details</h1>
            <p class="text-muted mb-0">Approve registration only after initial payment has been verified.</p>
        </div>
        <a href="<?= base_url('branch-admin/client-management') ?>" class="btn btn-outline-secondary">Back to Client Management</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Client Profile</h5>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th style="width:40%;">Name</th><td><?= esc((string) ($holder['first_name'] . ' ' . $holder['last_name'])) ?></td></tr>
                            <tr><th>Email</th><td><?= esc((string) ($holder['email'] ?? '-')) ?></td></tr>
                            <tr><th>Unique ID</th><td><?= esc((string) ($holder['unique_identifier'] ?: 'Not assigned')) ?></td></tr>
                            <tr><th>Address</th><td><?= esc(trim((string) (($holder['address_barangay'] ?? '') . ', ' . ($holder['address_city'] ?? '')), ' ,')) ?></td></tr>
                            <tr><th>Plan Holder Status</th><td><span class="badge text-bg-<?= $holderStatus === 'active' ? 'success' : 'secondary' ?>"><?= esc(ucfirst($holderStatus)) ?></span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Initial Payment Verification</h5>
                    <?php if (! empty($initial_payment)): ?>
                        <table class="table table-sm mb-3">
                            <tbody>
                                <tr><th style="width:40%;">Payment ID</th><td>#<?= esc((string) ($initial_payment['payment_id'] ?? '-')) ?></td></tr>
                                <tr><th>Amount</th><td>P<?= esc(number_format((float) ($initial_payment['amount'] ?? 0), 2)) ?></td></tr>
                                <tr><th>Date</th><td><?= esc((string) ($initial_payment['payment_date'] ?? '-')) ?></td></tr>
                                <tr><th>Method</th><td><?= esc(strtoupper((string) ($initial_payment['payment_method'] ?? '-'))) ?></td></tr>
                                <tr><th>Reference</th><td><?= esc((string) ($initial_payment['reference_number'] ?? '-')) ?></td></tr>
                                <tr><th>Status</th><td><span class="badge text-bg-<?= esc($paymentClass) ?>"><?= esc(ucfirst($paymentStatus)) ?></span></td></tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-secondary">No initial payment record found.</div>
                    <?php endif; ?>

                    <?php if ($can_approve): ?>
                        <form method="post" action="<?= base_url('branch-admin/client-management/approve/' . (int) $holder['plan_holder_id']) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success">Approve Registration</button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>Approve Registration</button>
                        <div class="form-text mt-2"><?= esc((string) $approval_message) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
