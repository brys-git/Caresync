<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'unregistered'); ?>
<?php /* Restricted blur+overlay: no caresync.css equivalent, see client/membership.php. */ ?>
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

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<?php if ($state === 'unregistered'): ?>
    <div class="restricted-wrap">
        <div class="restricted-blur">
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Payment Records</h2></div>
                <div class="cs-panel__body cs-panel__body--flush">
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead><tr><th>Date</th><th class="cs-num">Amount</th><th>Status</th></tr></thead>
                            <tbody><tr><td colspan="3" class="text-center">No Data Available</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="restricted-modal">
            <div class="cs-panel shadow" style="max-width: 420px;">
                <div class="cs-panel__body text-center">
                    <h5 class="mb-2">Register to access this feature</h5>
                    <p class="cs-muted">Payment is restricted until you complete plan registration.</p>
                    <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register Now</a>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($state === 'awaiting_activation'): ?>
    <div class="restricted-wrap">
        <div class="restricted-blur">
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Payment Records</h2></div>
                <div class="cs-panel__body cs-panel__body--flush">
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead><tr><th>Date</th><th class="cs-num">Amount</th><th>Status</th></tr></thead>
                            <tbody><tr><td colspan="3" class="text-center">No Data Available</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="restricted-modal">
            <div class="cs-panel shadow" style="max-width: 420px;">
                <div class="cs-panel__body text-center">
                    <h5 class="mb-2">Complete Your Initial Payment</h5>
                    <p class="cs-muted">You already registered. Submit your initial payment to unlock your membership.</p>
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
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Submit GCash Payment</h2></div>
            <div class="cs-panel__body">
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
        </section>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Make Advance Payment</h2>
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#advancePaymentForm" aria-expanded="false">Open Form</button>
            </div>
            <div class="cs-panel__body">
                <div class="collapse" id="advancePaymentForm">
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
                            <input class="form-control cs-num" value="<?= esc('₱' . number_format((float) ($plan['monthly_fee'] ?? 0), 2)) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Remaining Balance</label>
                            <input class="form-control cs-num" value="<?= esc('₱' . number_format((float) ($plan['remaining_balance'] ?? 0), 2)) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Months Paid</label>
                            <input class="form-control" value="<?= esc((string) ((int) ($plan['months_paid'] ?? 0))) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Next Due Date</label>
                            <input class="form-control" value="<?= cs_date($plan['next_due_date'] ?? null) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Months Prepaid In Advance</label>
                            <input class="form-control" value="<?= esc((string) ($remaining_months_prepaid ?? 0)) ?>" readonly>
                        </div>
                    </div>

                    <form method="post" action="<?= base_url('client/payment/submit-gcash') ?>" enctype="multipart/form-data" id="advance-payment-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="payment_method" value="gcash">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="advance_reference">Reference Number</label>
                                <input id="advance_reference" name="reference_number" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="advance_months">Number of Months to Pay</label>
                                <select id="advance_months" name="months_covered" class="form-select" required>
                                    <?php for ($m = 1; $m <= 59; $m++): ?>
                                        <option value="<?= $m ?>"><?= $m ?> Month<?= $m === 1 ? '' : 's' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="advance_amount">Amount (auto-computed)</label>
                                <input id="advance_amount" name="amount" type="number" step="0.01" class="form-control" readonly required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="advance_date">Payment Date</label>
                                <input id="advance_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
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
        </section>
    <?php endif; ?>

    <section class="cs-panel">
        <div class="cs-panel__head">
            <div>
                <h2 class="cs-panel__title">Payment History</h2>
                <p class="cs-panel__note">Every payment on this plan, oldest first, with the calendar months it covers &mdash; including what you've paid in advance.</p>
            </div>
        </div>
        <div class="cs-panel__body cs-panel__body--flush">
            <?php if (empty($payments)): ?>
                <?= view('components/empty_state', [
                    'icon'   => 'ti-receipt',
                    'title'  => 'No payments recorded yet',
                    'text'   => 'Once your first payment is posted, it appears here with a receipt.',
                ]) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr><th>Date</th><th>Coverage Period</th><th class="cs-num">Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php $paymentService = new \App\Services\PaymentService(); ?>
                            <?php foreach ($payments as $payment): ?>
                                <?php
                                $status = strtolower((string) ($payment['status'] ?? 'pending'));
                                $coverageStart = (string) ($payment['coverage_start'] ?? $payment['payment_date'] ?? '');
                                $coverageLabel = $coverageStart !== ''
                                    ? $paymentService->describeCoverage($coverageStart, (int) ($payment['months_covered'] ?? 1))
                                    : (int) ($payment['months_covered'] ?? 1) . ' month(s)';
                                ?>
                                <tr>
                                    <td class="text-nowrap"><?= cs_date($payment['payment_date']) ?></td>
                                    <td><?= esc($coverageLabel) ?></td>
                                    <td class="cs-num"><?= cs_money($payment['amount']) ?></td>
                                    <td><?= esc(strtoupper((string) $payment['payment_method'])) ?></td>
                                    <td><?= esc((string) ($payment['reference_number'] ?? '-')) ?></td>
                                    <td><?= cs_status($status) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($plan && (string) ($plan['status'] ?? '') !== 'active'): ?>
            <div class="cs-panel__foot">
                <div class="alert alert-info mb-0">Your initial payment is pending verification.</div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

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
