<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'unregistered'); ?>
<style>
    .restricted-wrap { position: relative; }
    .restricted-blur { filter: blur(4px); pointer-events: none; user-select: none; }
    .restricted-modal {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.55);
    }
</style>

<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Payment</h1>
        <p class="text-muted mb-0">Track contribution records.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if ($state === 'unregistered'): ?>
        <div class="restricted-wrap">
            <div class="restricted-blur">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Payment Records</h5>
                        <table class="table">
                            <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr><td colspan="3" class="text-center">No Data Available</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="restricted-modal">
                <div class="card shadow" style="max-width: 420px;">
                    <div class="card-body text-center">
                        <h5 class="mb-2">Register to access this feature</h5>
                        <p class="text-muted">Payment is restricted until you complete plan registration.</p>
                        <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register Now</a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($state === 'awaiting_activation'): ?>
        <div class="restricted-wrap">
            <div class="restricted-blur">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Payment Records</h5>
                        <table class="table">
                            <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <tr><td colspan="3" class="text-center">No Data Available</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="restricted-modal">
                <div class="card shadow" style="max-width: 420px;">
                    <div class="card-body text-center">
                        <h5 class="mb-2">Complete Your Initial Payment</h5>
                        <p class="text-muted">You already registered. Submit your initial payment to unlock your membership.</p>
                        <a href="<?= base_url('initial-payment') ?>" class="btn btn-primary">Go to Initial Payment</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if (($access['initial_payment_status'] ?? 'none') === 'cancelled'): ?>
            <div class="alert alert-danger">Payment rejected. Please resubmit.</div>
        <?php endif; ?>

        <?php if ($state === 'awaiting_activation'): ?>
            <div class="alert alert-warning">Your registration is complete. Submit your initial payment to activate your membership.</div>
        <?php endif; ?>

        <?php if ($state === 'active' && $plan): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Submit GCash Payment</h5>
                    <form method="post" action="<?= base_url('client/payment/submit-gcash') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_method" value="gcash">
                        <input type="hidden" name="months_covered" value="1">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="amount">Amount</label>
                                <input id="amount" name="amount" type="number" step="0.01" class="form-control" value="<?= esc((string) ($plan['monthly_fee'] ?? '0')) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="payment_date">Payment Date</label>
                                <input id="payment_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="reference_number">Reference Number</label>
                                <input id="reference_number" name="reference_number" class="form-control" required>
                            </div>
                            <?php if (! empty($supports_proof_upload)): ?>
                                <div class="col-md-12">
                                    <label class="form-label" for="proof_image">Proof Image (Optional)</label>
                                    <input id="proof_image" name="proof_image" type="file" class="form-control" accept="image/*">
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Submit GCash Payment</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Make Advance Payment</h5>
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#advancePaymentForm" aria-expanded="false">Open Form</button>
                    </div>
                    <div class="collapse mt-3" id="advancePaymentForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Plan Name</label>
                                <select class="form-select" name="program_id" aria-label="Plan Name">
                                    <?php if (! empty($membership_plans)): ?>
                                        <?php foreach ($membership_plans as $membershipPlan): ?>
                                            <option
                                                value="<?= esc((string) ($membershipPlan['program_id'] ?? 0)) ?>"
                                                <?= (int) ($program['id'] ?? 0) === (int) ($membershipPlan['program_id'] ?? 0) ? 'selected' : '' ?>
                                            >
                                                <?= esc((string) ($membershipPlan['program_name'] ?? 'Damayan Burial Program')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">No plans available</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Membership Status</label>
                                <input class="form-control" value="<?= esc(ucfirst((string) ($plan['status'] ?? 'active'))) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Monthly Contribution</label>
                                <input class="form-control" value="P<?= esc(number_format((float) ($plan['monthly_fee'] ?? 0), 2)) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Remaining Balance</label>
                                <input class="form-control" value="P<?= esc(number_format((float) ($plan['remaining_balance'] ?? 0), 2)) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Months Paid</label>
                                <input class="form-control" value="<?= esc((string) ((int) ($plan['months_paid'] ?? 0))) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Next Due Date</label>
                                <input class="form-control" value="<?= esc((string) ($plan['next_due_date'] ?? '-')) ?>" readonly>
                            </div>
                        </div>

                        <form method="post" action="<?= base_url('client/payment/submit-gcash') ?>" enctype="multipart/form-data" id="advance-payment-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="payment_method" value="gcash">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="advance_months">Number of Months to Pay</label>
                                    <select id="advance_months" name="months_covered" class="form-select" required>
                                        <option value="1">1 Month</option>
                                        <option value="3">3 Months</option>
                                        <option value="6">6 Months</option>
                                        <option value="12">12 Months</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="advance_amount">Amount</label>
                                    <input id="advance_amount" name="amount" type="number" step="0.01" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="advance_date">Payment Date</label>
                                    <input id="advance_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="advance_reference">Reference Number</label>
                                    <input id="advance_reference" name="reference_number" class="form-control" required>
                                </div>
                                <?php if (! empty($supports_proof_upload)): ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="advance_proof">Receipt Screenshot</label>
                                        <input id="advance_proof" name="proof_image" type="file" class="form-control" accept="image/*" required>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-primary mt-3" type="submit">Submit Advance Payment</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Payment History</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Date</th><th>Months</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <?php $status = strtolower((string) ($payment['status'] ?? 'pending')); ?>
                                <tr>
                                    <td><?= esc((string) $payment['payment_date']) ?></td>
                                    <td><?= esc((string) ((int) ($payment['months_covered'] ?? 1))) ?></td>
                                    <td>P<?= esc(number_format((float) $payment['amount'], 2)) ?></td>
                                    <td><?= esc(strtoupper((string) $payment['payment_method'])) ?></td>
                                    <td><?= esc((string) ($payment['reference_number'] ?? '-')) ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>">
                                            <?= esc(ucfirst($status)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="6" class="text-center">No Data Available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($plan && (string) ($plan['status'] ?? '') !== 'active'): ?>
                    <div class="alert alert-info mb-0">Your initial payment is pending verification.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($state === 'active' && $plan): ?>
<script>
    (function () {
        const monthlyFee = Number('<?= esc((string) ($plan['monthly_fee'] ?? 0)) ?>');
        const monthsSelect = document.getElementById('advance_months');
        const amountInput = document.getElementById('advance_amount');

        if (!monthsSelect || !amountInput) {
            return;
        }

        function updateAmount() {
            const months = Number(monthsSelect.value || 1);
            amountInput.value = (monthlyFee * months).toFixed(2);
        }

        monthsSelect.addEventListener('change', updateAmount);
        updateAmount();
    })();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
