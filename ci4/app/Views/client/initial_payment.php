<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$initialStatus = strtolower((string) (($latest_initial_payment['status'] ?? 'none')));
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$monthlyFee = (float) ($monthly_fee ?? ($program['monthly_fee'] ?? 240));
$coordinatorGcash = $coordinator_gcash ?? null;
$coordinatorName = $coordinator_name ?? null;
?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Initial Payment</h1>
        <p class="text-muted mb-0">Submit your first monthly contribution for verification.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
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

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Monthly Contribution</small><strong>P<?= number_format($monthlyFee, 2) ?></strong></div></div>
                            <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Plan</small><strong><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></strong></div></div>
                            <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Status</small><strong><?= esc((string) ($plan['status'] ?? 'inactive')) ?></strong></div></div>
                        </div>

                        <form method="post" action="<?= base_url('initial-payment') ?>">
                            <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="months_covered">Months Covered</label>
                            <input id="months_covered" name="months_covered" type="number" min="1" class="form-control" value="<?= esc(old('months_covered', '1')) ?>" required <?= in_array($initialStatus, ['pending', 'paid'], true) ? 'readonly' : '' ?>>
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
                    <div id="gcash_box" class="mt-3" style="display:none;">
                        <div class="alert alert-success p-3 mb-0">
                            <small class="text-muted d-block mb-2"><strong>Pay via GCash to your assigned coordinator:</strong></small>
                            <?php if ($coordinatorGcash && ! empty($coordinatorGcash['number'])): ?>
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <span>Account Name: <strong><?= esc((string) $coordinatorGcash['name']) ?></strong></span>
                                    <span>GCash Number: <strong><?= esc((string) $coordinatorGcash['number']) ?></strong></span>
                                </div>
                                <small class="text-muted d-block mt-2">Send your payment to the account above, then enter your GCash reference number. GCash payments are verified by staff before your membership is activated.</small>
                            <?php else: ?>
                                <span class="text-muted">No GCash account is set for your assigned coordinator (<?= esc($coordinatorName ?: 'unknown') ?>) yet. Please contact your branch to confirm the payment details before sending a GCash payment.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4" <?= $initialStatus === 'paid' ? 'disabled' : '' ?>>Submit Initial Payment</button>
                </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body small">
                    <h6 class="card-title">💳 Payment Methods</h6>
                    <p class="mb-2"><strong>GCash:</strong> Pay your assigned coordinator's GCash account (shown when GCash is selected) and enter the transaction reference.</p>
                    <p class="mb-3"><strong>Cash:</strong> Visit your branch to pay, then enter the official receipt number. The branch verifies cash payments against its records.</p>

                    <div class="alert alert-info p-2 mb-0">
                        <small><strong>Tip:</strong> GCash payments are verified by staff before your membership is activated. Cash payments are verified against your branch's official receipts.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const method = document.getElementById('payment_method');
        const reference = document.getElementById('reference_number');
        const hintCash = document.getElementById('hint_cash');
        const hintGcash = document.getElementById('hint_gcash');
        const gcashBox = document.getElementById('gcash_box');

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

            // Show the assigned coordinator's GCash account only for GCash.
            if (gcashBox) gcashBox.style.display = isGcash ? '' : 'none';
            
            if (!isCash && !isGcash) {
                reference.value = '';
            }
        }

        method.addEventListener('change', toggleReferenceField);
        toggleReferenceField();
    })();
</script>
<?= $this->endSection() ?>
