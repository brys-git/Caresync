<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$initialStatus = strtolower((string) (($latest_initial_payment['status'] ?? 'none')));
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$monthlyFee = (float) ($monthly_fee ?? ($program['monthly_fee'] ?? 240));
?>
<div class="row g-3">
    <div class="col-md-8">
        <?php // Plain error/success flash already handled by layouts/_shell.php's toast. The "errors" list below is a validation-array flash, a different shape the shell's toast loop doesn't cover, so it stays. ?>
        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach (session()->getFlashdata('errors') as $field => $message): ?>
                        <li><?= esc($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (! $plan_holder || ! $plan): ?>
            <div class="alert alert-warning">
                Plan registration is incomplete. Please complete registration before making payment.
                <a href="<?= base_url('plan-registration') ?>" class="alert-link">Go to plan registration</a>.
            </div>
        <?php else: ?>
            <?php if ($initialStatus === 'pending'): ?>
                <div class="alert alert-warning">Your initial payment is pending verification.</div>
            <?php elseif ($initialStatus === 'paid' && strtolower((string) ($plan_holder['status'] ?? 'inactive')) === 'inactive'): ?>
                <div class="alert alert-info">Initial payment is verified. Your account will activate automatically after OR matching is completed.</div>
            <?php elseif ($initialStatus === 'cancelled'): ?>
                <div class="alert alert-danger">Payment rejected. Please resubmit.</div>
            <?php endif; ?>

            <section class="cs-panel">
                <div class="cs-panel__body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Monthly Contribution</small><strong><?= cs_money($monthlyFee) ?></strong></div></div>
                        <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Plan</small><strong><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></strong></div></div>
                        <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Status</small><strong><?= esc((string) ($plan['status'] ?? 'inactive')) ?></strong></div></div>
                        <div class="col-md-3"><div class="border rounded p-3 bg-light"><small class="text-muted d-block">Initial Payment Due</small><strong><span id="initial_amount_preview" class="cs-money"><?= esc(number_format($monthlyFee * 2, 2)) ?></span></strong></div></div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-3">
                        <small><i class="ti ti-info-circle me-1"></i>Your initial payment must cover at least <strong>2 months</strong> so your membership is immediately eligible to avail or claim services after it's verified.</small>
                    </div>

                    <form method="post" action="<?= base_url('initial-payment') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="months_covered">Months Covered (minimum 2)</label>
                                <input id="months_covered" name="months_covered" type="number" min="2" data-monthly-fee="<?= esc((string) $monthlyFee) ?>" class="form-control" value="<?= esc(old('months_covered', '2')) ?>" required <?= in_array($initialStatus, ['pending', 'paid'], true) ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-select" required <?= $initialStatus === 'paid' ? 'disabled' : '' ?> >
                                    <option value="">Select method</option>
                                    <option value="cash" <?= old('payment_method') === 'cash' ? 'selected' : '' ?>>Cash (at branch)</option>
                                    <option value="gcash" <?= old('payment_method') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="reference_number">Reference/Receipt Number</label>
                                <input id="reference_number" name="reference_number" class="form-control" placeholder="Enter receipt number from branch or GCash reference" value="<?= esc(old('reference_number')) ?>" required <?= $initialStatus === 'paid' ? 'readonly' : '' ?>>
                                <small class="text-muted d-block mt-1" id="hint_receipt">
                                    <span id="hint_cash" style="display:none;">Ask your branch for the official receipt number after paying.</span>
                                    <span id="hint_gcash" style="display:none;">Enter your GCash transaction reference.</span>
                                </small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-4" <?= $initialStatus === 'paid' ? 'disabled' : '' ?>>Submit Initial Payment</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <section class="cs-panel">
            <div class="cs-panel__body small">
                <h6 class="mb-2">💳 Payment Methods</h6>
                <p class="mb-2"><strong>GCash:</strong> Pay online and enter your transaction reference.</p>
                <p class="mb-3"><strong>Cash:</strong> Visit your branch to pay, then ask for the official receipt number.</p>

                <div class="alert alert-info p-2 mb-0">
                    <small><strong>Tip:</strong> Once you submit the correct receipt/reference, your membership is automatically activated!</small>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    (function () {
        const method = document.getElementById('payment_method');
        const reference = document.getElementById('reference_number');
        const hintCash = document.getElementById('hint_cash');
        const hintGcash = document.getElementById('hint_gcash');

        if (!method || !reference) {
            return;
        }

        // Don't disable the field if it's readonly (already submitted)
        const isReadonly = reference.hasAttribute('readonly');

        function toggleReferenceField() {
            const isCash = method.value === 'cash';
            const isGcash = method.value === 'gcash';

            // Don't disable if readonly
            if (!isReadonly) {
                reference.disabled = !(isCash || isGcash);
            }
            reference.required = isCash || isGcash;

            // Update placeholder and hint
            if (isCash) {
                reference.placeholder = 'Enter official receipt number from branch';
                hintCash.style.display = 'inline';
                hintGcash.style.display = 'none';
            } else if (isGcash) {
                reference.placeholder = 'Enter GCash transaction reference';
                hintCash.style.display = 'none';
                hintGcash.style.display = 'inline';
            } else {
                reference.placeholder = '';
                hintCash.style.display = 'none';
                hintGcash.style.display = 'none';
            }

            if (!isCash && !isGcash) {
                reference.value = '';
            }
        }

        method.addEventListener('change', toggleReferenceField);
        toggleReferenceField();
    })();

    (function () {
        const monthsInput = document.getElementById('months_covered');
        const preview = document.getElementById('initial_amount_preview');
        if (!monthsInput || !preview) {
            return;
        }
        const monthlyFee = parseFloat(monthsInput.dataset.monthlyFee || '0');

        function updatePreview() {
            const months = Math.max(2, parseInt(monthsInput.value, 10) || 2);
            const total = monthlyFee * months;
            preview.textContent = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        monthsInput.addEventListener('input', updatePreview);
        updatePreview();
    })();
</script>
<?= $this->endSection() ?>
