<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/advance-payment.css') ?>?v=<?= date('YmdHis') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/payments.css') ?>?v=<?= date('YmdHis') ?>">

<?php
    $state = (string) ($access['state'] ?? 'unregistered');
    $viewMode = (string) ($view_mode ?? 'history');
    $monthlyFee = (float) ($monthly_fee ?? ($plan['monthly_fee'] ?? ($program['monthly_fee'] ?? 240)));
    $userName = (string) ($user_name ?? trim((string) (($access['user']['first_name'] ?? '') . ' ' . ($access['user']['last_name'] ?? ''))));
    $planName = (string) ($plan_name ?? ($program['name'] ?? 'Damayan Burial Program'));
    $lastPaymentStatus = (string) ($last_payment_status ?? 'None');
    $coordinatorGcash = $coordinator_gcash ?? null;
    $coordinatorName = $coordinator_name ?? null;

    // Months covered options. The total due is always monthly fee × months
    // (the server enforces this exact amount), so no discount is applied here.
    $monthsOptions = [
        1 => '1 Month',
        3 => '3 Months',
        6 => '6 Months',
        12 => '12 Months',
    ];
?>

<div class="ap">
<?php if ($state === 'unregistered'): ?>
    <!-- ====== Locked State ====== -->
    <div style="background:var(--ap-surface, #fff);border:1px solid var(--ap-border, #e2e8f0);border-radius:var(--ap-radius, 16px);padding:40px 20px;text-align:center;">
        <i class="mdi mdi-lock-outline" style="font-size:2.5rem;color:var(--ap-ink-faint, #a0aec0);"></i>
        <h3 style="margin:16px 0 8px;font-weight:800;">Register to access this feature</h3>
        <p style="color:var(--ap-ink-soft, #4a5568);margin-bottom:20px;">Payment is restricted until you complete plan registration.</p>
        <a href="<?= base_url('plan-info') ?>" style="display:inline-flex;align-items:center;gap:6px;padding:12px 22px;border-radius:10px;background:#1e3a5f;color:#fff;border:none;font-weight:800;font-size:0.92rem;text-decoration:none;">Register Now</a>
    </div>

<?php elseif ($state === 'awaiting_activation'): ?>
    <!-- ====== Awaiting Activation ====== -->
    <div style="background:var(--ap-surface, #fff);border:1px solid var(--ap-border, #e2e8f0);border-radius:var(--ap-radius, 16px);padding:40px 20px;text-align:center;">
        <i class="mdi mdi-cash-check" style="font-size:2.5rem;color:var(--ap-orange, #e67e22);"></i>
        <h3 style="margin:16px 0 8px;font-weight:800;">Complete Your Initial Payment</h3>
        <p style="color:var(--ap-ink-soft, #4a5568);margin-bottom:20px;">You already registered. Submit your initial payment to unlock your membership.</p>
        <a href="<?= base_url('initial-payment') ?>" style="display:inline-flex;align-items:center;gap:6px;padding:12px 22px;border-radius:10px;background:#1e3a5f;color:#fff;border:none;font-weight:800;font-size:0.92rem;text-decoration:none;">Go to Initial Payment</a>
    </div>

<?php else: ?>
    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="ap-alert ap-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="ap-alert ap-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <?php if ($viewMode === 'advance'): ?>
        <!-- ====== Advance Payment ====== -->
        <form method="post" action="<?= base_url('client/payment/submit-gcash') ?>" enctype="multipart/form-data" id="ap-form">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_date" value="<?= esc(date('Y-m-d')) ?>">
            <input type="hidden" name="months_covered" id="ap-months-hidden" value="1">
            <input type="hidden" name="amount" id="ap-amount-hidden" value="<?= number_format($monthlyFee, 2, '.', '') ?>">

            <div class="ap-layout">
                <!-- ====== Left Column: Payment Details ====== -->
                <div class="ap-main">
                    <div class="ap-card">
                        <div class="ap-card__header">
                            <h2 class="ap-card__title">Payment Details</h2>
                        </div>
                        <div class="ap-card__body">

                            <!-- Client Info -->
                            <div class="ap-client-info">
                                <strong>Client</strong>
                            </div>
                            <div class="ap-client-info">
                                <?= esc($userName) ?>
                                <span class="ap-client-info__sep">|</span>
                                Plan: <strong><?= esc($planName) ?></strong>
                                <span class="ap-client-info__sep">|</span>
                                Last Payment: <span class="ap-client-info__status ap-client-info__status--<?= strtolower($lastPaymentStatus) === 'paid' ? 'paid' : 'pending' ?>"><?= esc($lastPaymentStatus) ?></span>
                            </div>

                            <!-- Select Advance Months -->
                            <div class="ap-section-label">Select Advance Months</div>
                            <div class="ap-months-grid">
                                <?php foreach ($monthsOptions as $months => $label): ?>
                                    <button type="button" class="ap-month-btn <?= $months === 1 ? 'ap-month-btn--active' : '' ?>"
                                            data-months="<?= $months ?>"
                                            onclick="apSelectMonths(this)">
                                        <span class="ap-month-btn__label"><?= $label ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- Progress Bar -->
                            <div class="ap-progress-wrap">
                                <div class="ap-progress">
                                    <div class="ap-progress__fill" id="ap-progress-fill" style="width:8.33%"></div>
                                </div>
                                <span class="ap-progress__label">Months Covered</span>
                            </div>

                            <!-- Payment Method -->
                            <div class="ap-section-label">Payment Method</div>
                            <div class="ap-method-grid">
                                <button type="button" class="ap-method-btn ap-method-btn--active" data-method="gcash" onclick="apSelectMethod(this)">
                                    <span class="ap-method-icon ap-method-icon--gcash"><i class="mdi mdi-cash"></i></span>
                                    GCash
                                </button>
                                <button type="button" class="ap-method-btn" data-method="cash" onclick="apSelectMethod(this)">
                                    <span class="ap-method-icon ap-method-icon--cash"><i class="mdi mdi-cash-multiple"></i></span>
                                    Cash
                                </button>
                            </div>
                            <input type="hidden" name="payment_method" id="ap-method-hidden" value="gcash">

                            <!-- GCash QR Section -->
                            <div id="ap-gcash-section" class="ap-qr-section">
                                <div class="ap-qr-img">
                                    <i class="mdi mdi-qrcode" style="font-size:3rem;color:var(--ap-ink-faint);"></i>
                                </div>
                                <div class="ap-qr-info">
                                    <h4>GCash Payment</h4>
                                    <?php if ($coordinatorGcash): ?>
                                        <p>Send the total due to your assigned coordinator's GCash account below.</p>
                                        <div class="ap-qr-account">
                                            <span>Account Details:</span>
                                            <strong id="ap-gcash-number"><?= esc((string) ($coordinatorGcash['number'] ?? '')) ?></strong>
                                            <?php if (! empty($coordinatorGcash['name'])): ?>
                                                <small style="color:var(--ap-ink-faint);display:block;margin-top:4px;"><?= esc((string) $coordinatorGcash['name']) ?></small>
                                            <?php endif; ?>
                                            <button type="button" class="ap-copy-btn" onclick="apCopyAccount()">
                                                <i class="mdi mdi-content-copy"></i> Copy Account
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <p>No GCash account is set for your assigned coordinator
                                            (<?= esc($coordinatorName ?: 'unknown') ?>) yet. Please contact your branch to confirm
                                            the payment details before sending a GCash payment.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Cash Section (hidden by default) -->
                            <div id="ap-cash-section" class="ap-cash-section" style="display:none;">
                                <h4><i class="mdi mdi-cash-multiple" style="color:var(--ap-green);"></i> Cash Payment</h4>
                                <p>Visit your branch office to make a cash payment. Ask for the official receipt number after paying, then enter it in the Reference field below.</p>
                            </div>

                            <!-- Reference -->
                            <div style="margin-top:20px;">
                                <div class="ap-ref-label">Reference <span>*</span></div>
                                <input type="text" class="ap-input" name="reference_number" id="ap-reference" placeholder="GCash Reference Number (e.g., GCASH123456789)" value="<?= esc(old('reference_number')) ?>" required>
                            </div>

                            <!-- Proof of Payment -->
                            <div style="margin-top:20px;">
                                <div class="ap-upload-label">Proof of Payment</div>
                                <div class="ap-upload-zone" onclick="document.getElementById('ap-proof-input').click();">
                                    <div class="ap-upload-zone__icon" id="ap-proof-preview">
                                        <i class="mdi mdi-receipt-text-outline"></i>
                                    </div>
                                    <div class="ap-upload-zone__text">
                                        <h5>Drag-and-drop upload zone</h5>
                                        <p>PNG, JPG, PDF up to 5MB</p>
                                    </div>
                                </div>
                                <input type="file" id="ap-proof-input" name="proof_image" accept="image/*,.pdf" style="display:none;" onchange="apPreviewProof(this)">
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ====== Right Column: Summary + History ====== -->
                <div class="ap-sidebar">

                    <!-- Summary Card -->
                    <div class="ap-card">
                        <div class="ap-card__header">
                            <h3 class="ap-card__title">Advance Payment Summary</h3>
                        </div>
                        <div class="ap-card__body">
                            <div class="ap-summary-row">
                                <span class="ap-summary-row__label">Monthly Rate:</span>
                                <span class="ap-summary-row__value">₱<?= number_format($monthlyFee, 2) ?></span>
                            </div>
                            <div class="ap-summary-row">
                                <span class="ap-summary-row__label">Months Selected:</span>
                                <span class="ap-summary-row__value" id="ap-summary-months">x 1</span>
                            </div>
                            <div class="ap-summary-row">
                                <span class="ap-summary-row__label">Subtotal:</span>
                                <span class="ap-summary-row__value" id="ap-summary-subtotal">₱<?= number_format($monthlyFee, 2) ?></span>
                            </div>
                            <div class="ap-summary-row" id="ap-discount-row" style="display:none;color:var(--ap-green,#38a169);">
                                <span class="ap-summary-row__label">Advance Discount:</span>
                                <span class="ap-summary-row__value" id="ap-summary-discount">−₱0.00</span>
                            </div>
                            <hr class="ap-summary-divider">
                            <div class="ap-summary-row">
                                <span class="ap-summary-row__label" style="font-weight:800;">Total Due:</span>
                                <span class="ap-summary-row__value ap-summary-row__value--total" id="ap-summary-total">₱<?= number_format($monthlyFee, 2) ?></span>
                            </div>
                            <div class="ap-summary-row">
                                <span class="ap-summary-row__label">Payment Status:</span>
                                <span class="ap-status-badge ap-status-badge--pending">Pending Submission</span>
                            </div>

                            <div style="margin-top:18px;">
                                <button type="submit" class="ap-btn ap-btn--primary" id="ap-submit-btn">
                                    Proceed to Pay <span id="ap-submit-amount">₱<?= number_format($monthlyFee, 2) ?></span>
                                </button>
                            </div>
                            <a href="<?= base_url('client/dashboard') ?>" class="ap-btn ap-btn--ghost">Cancel and Return</a>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="ap-card">
                        <div class="ap-card__body">
                            <div class="ap-history">
                                <div class="ap-history__header">
                                    <h4 class="ap-history__title">Payment History</h4>
                                    <p class="ap-history__subtitle">
                                        Refined & consolidated summary:<br>
                                        <?= $completed_count ?? 0 ?> Completed payments, as <?= $pending_count ?? 0 ?> Pending payments.
                                    </p>
                                </div>
                                <table class="ap-history-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Rate</th>
                                            <th>Status</th>
                                            <th>Primary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payments ?? [])): ?>
                                            <tr><td colspan="4" class="ap-empty">No payment history yet.</td></tr>
                                        <?php else: ?>
                                            <?php foreach (array_slice($payments, 0, 10) as $payment):
                                                $pStatus = strtolower((string) ($payment['status'] ?? 'pending'));
                                            ?>
                                            <tr>
                                                <td><?= esc((string) ($payment['payment_date'] ?? '-')) ?></td>
                                                <td>₱<?= number_format((float) ($payment['amount'] ?? 0), 2) ?></td>
                                                <td>
                                                    <span class="ap-status-badge <?= $pStatus === 'paid' ? 'ap-status-badge--paid' : 'ap-status-badge--pending' ?>" style="<?= $pStatus === 'paid' ? 'background:#f0fff4;color:#38a169;' : '' ?>">
                                                        <?= strtoupper($pStatus) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($pStatus === 'paid'): ?>
                                                        <span class="ap-status-badge" style="background:#f0fff4;color:#38a169;">PAID</span>
                                                    <?php else: ?>
                                                        <span class="ap-status-badge" style="background:#fef5e7;color:#c2760a;">PENDING VERIFICATION</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    <?php else: ?>
        <!-- ====== Payment History Mode ====== -->
        <div class="ap-card">
            <div class="ap-card__header">
                <h2 class="ap-card__title">Payment History</h2>
            </div>
            <div class="ap-card__body">
                <table class="ap-history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Months</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments ?? [])): ?>
                            <tr><td colspan="7" class="ap-empty">No payment records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment):
                                $pStatus = strtolower((string) ($payment['status'] ?? 'pending'));
                                $method = strtolower((string) ($payment['payment_method'] ?? 'cash'));
                            ?>
                            <tr>
                                <td><strong>#<?= esc((string) ($payment['payment_id'] ?? '-')) ?></strong></td>
                                <td><?= esc((string) ($payment['payment_date'] ?? '-')) ?></td>
                                <td><?= esc((string) ((int) ($payment['months_covered'] ?? 1))) ?></td>
                                <td><strong>₱<?= number_format((float) ($payment['amount'] ?? 0), 2) ?></strong></td>
                                <td><?= esc(strtoupper($method)) ?></td>
                                <td><?= esc((string) ($payment['reference_number'] ?? '-')) ?></td>
                                <td>
                                    <span class="ap-status-badge <?= $pStatus === 'paid' ? '' : 'ap-status-badge--pending' ?>" style="<?= $pStatus === 'paid' ? 'background:#f0fff4;color:#38a169;' : '' ?>">
                                        <?= strtoupper($pStatus) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    var monthlyFee = <?= json_encode($monthlyFee) ?>;
    // Advance-payment discount schedule — same source of truth as the server
    // (PaymentService::ADVANCE_DISCOUNTS). Mirrors the server exactly.
    var discountSchedule = <?= json_encode(\App\Services\PaymentService::ADVANCE_DISCOUNTS) ?>;
    var currentMonths = 1;

    function roundMoney(value) {
        return Math.round(value * 100) / 100;
    }

    function updateSummary() {
        var monthsEl = document.getElementById('ap-summary-months');
        var discountRow = document.getElementById('ap-discount-row');
        var discountValueEl = document.getElementById('ap-summary-discount');
        var subtotalEl = document.getElementById('ap-summary-subtotal');
        var totalEl = document.getElementById('ap-summary-total');
        var submitAmount = document.getElementById('ap-submit-amount');
        var progressFill = document.getElementById('ap-progress-fill');
        var hiddenMonths = document.getElementById('ap-months-hidden');
        var hiddenAmount = document.getElementById('ap-amount-hidden');

        if (!monthsEl) return;

        var subtotal = roundMoney(monthlyFee * currentMonths);
        var pct = discountSchedule[currentMonths] || 0;
        var discount = roundMoney(subtotal * pct / 100);
        var total = roundMoney(subtotal - discount);

        monthsEl.textContent = 'x ' + currentMonths;

        if (discountRow) {
            discountRow.style.display = (discount > 0) ? '' : 'none';
        }
        if (discountValueEl) {
            discountValueEl.textContent = '−₱' + discount.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        subtotalEl.textContent = '₱' + subtotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        totalEl.textContent = '₱' + total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        submitAmount.textContent = '₱' + total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

        if (progressFill) {
            progressFill.style.width = (currentMonths / 12 * 100) + '%';
        }

        if (hiddenMonths) {
            hiddenMonths.value = currentMonths;
        }

        if (hiddenAmount) {
            hiddenAmount.value = total.toFixed(2);
        }
    }

    window.apSelectMonths = function (btn) {
        document.querySelectorAll('.ap-month-btn').forEach(function (b) { b.classList.remove('ap-month-btn--active'); });
        btn.classList.add('ap-month-btn--active');
        currentMonths = parseInt(btn.getAttribute('data-months')) || 1;
        updateSummary();
    };

    window.apSelectMethod = function (btn) {
        document.querySelectorAll('.ap-method-btn').forEach(function (b) { b.classList.remove('ap-method-btn--active'); });
        btn.classList.add('ap-method-btn--active');

        var method = btn.getAttribute('data-method');
        var gcashSection = document.getElementById('ap-gcash-section');
        var cashSection = document.getElementById('ap-cash-section');
        var hiddenMethod = document.getElementById('ap-method-hidden');
        var refInput = document.getElementById('ap-reference');

        if (method === 'gcash') {
            if (gcashSection) gcashSection.style.display = '';
            if (cashSection) cashSection.style.display = 'none';
            if (refInput) { refInput.placeholder = 'GCash Reference Number (e.g., GCASH123456789)'; refInput.required = true; }
        } else {
            if (gcashSection) gcashSection.style.display = 'none';
            if (cashSection) cashSection.style.display = '';
            if (refInput) { refInput.placeholder = 'Enter official receipt number from branch'; refInput.required = true; }
        }

        if (hiddenMethod) hiddenMethod.value = method;
    };

    window.apCopyAccount = function () {
        var account = (document.getElementById('ap-gcash-number') || {}).textContent || '';
        account = account.trim();
        if (!account) return;
        navigator.clipboard.writeText(account).then(function () {
            var btn = document.querySelector('.ap-copy-btn');
            if (btn) {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="mdi mdi-check"></i> Copied!';
                setTimeout(function () { btn.innerHTML = orig; }, 1500);
            }
        });
    };

    window.apPreviewProof = function (input) {
        if (input.files && input.files[0]) {
            var preview = document.getElementById('ap-proof-preview');
            if (preview) {
                var file = input.files[0];
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Proof">';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '<i class="mdi mdi-file-pdf-box" style="font-size:2rem;color:var(--ap-red);"></i>';
                }
            }
        }
    };

    updateSummary();
})();
</script>
<?= $this->endSection() ?>
