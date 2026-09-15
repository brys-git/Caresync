<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<section class="cs-panel">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Make Payment</h2>
    </div>
    <div class="cs-panel__body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <small class="cs-muted d-block">Plan Name</small>
                <strong><?= esc($plan_name !== '' ? $plan_name : '-') ?></strong>
            </div>
            <div class="col-md-6">
                <small class="cs-muted d-block">Monthly Contribution</small>
                <strong><?= cs_money($plan['monthly_fee'] ?? 0) ?></strong>
            </div>
        </div>

        <?php if ($remaining_months <= 0): ?>
            <div class="alert alert-info mb-0">Your plan has no remaining months left to pay. There's nothing more to submit right now.</div>
        <?php else: ?>
            <form method="post" action="<?= base_url('client/payment/make') ?>" id="makePaymentForm" data-cs-once>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="months_covered">Number of Months to Pay</label>
                        <select id="months_covered" name="months_covered" class="form-select" required>
                            <?php for ($m = 1; $m <= $remaining_months; $m++): ?>
                                <option value="<?= $m ?>"><?= $m ?> Month<?= $m === 1 ? '' : 's' ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="form-text"><?= esc((string) $remaining_months) ?> month(s) left on this plan.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Total Amount</label>
                        <strong id="totalAmountDisplay" class="fs-5"></strong>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label d-block">Payment Method</label>
                        <strong>GCash</strong>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reference_number">GCash Reference Number</label>
                        <input
                            id="reference_number"
                            name="reference_number"
                            class="form-control"
                            inputmode="numeric"
                            pattern="\d{13}"
                            maxlength="13"
                            placeholder="13-digit reference number"
                            value="<?= old('reference_number', '') ?>"
                            required
                        >
                        <div class="form-text">Found on your GCash payment receipt. Digits only, 13 numbers.</div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary mt-4" id="openConfirmModal">Submit</button>
            </form>

            <div class="modal fade" id="confirmPaymentModal" tabindex="-1" aria-labelledby="confirmPaymentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmPaymentModalLabel">Confirm Your Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6"><small class="cs-muted d-block">Plan Name</small><strong><?= esc($plan_name !== '' ? $plan_name : '-') ?></strong></div>
                                <div class="col-6"><small class="cs-muted d-block">Months</small><strong id="confirmMonths"></strong></div>
                                <div class="col-6"><small class="cs-muted d-block">Total Amount</small><strong id="confirmTotal"></strong></div>
                                <div class="col-6"><small class="cs-muted d-block">Payment Method</small><strong>GCash</strong></div>
                                <div class="col-12"><small class="cs-muted d-block">Reference Number</small><strong id="confirmReference"></strong></div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmReviewedCheck">
                                <label class="form-check-label" for="confirmReviewedCheck">
                                    I have reviewed this payment information and confirm it is correct.
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                            <button type="submit" class="btn btn-primary" id="confirmSubmitBtn" form="makePaymentForm" disabled>Confirm &amp; Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($remaining_months > 0): ?>
<script>
    (function () {
        const monthlyFee = Number('<?= esc((string) ($plan['monthly_fee'] ?? 0)) ?>');
        const monthsSelect = document.getElementById('months_covered');
        const totalDisplay = document.getElementById('totalAmountDisplay');
        const referenceInput = document.getElementById('reference_number');
        const openModalBtn = document.getElementById('openConfirmModal');
        const confirmCheck = document.getElementById('confirmReviewedCheck');
        const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
        const confirmMonths = document.getElementById('confirmMonths');
        const confirmTotal = document.getElementById('confirmTotal');
        const confirmReference = document.getElementById('confirmReference');
        const modalEl = document.getElementById('confirmPaymentModal');

        function formatMoney(value) {
            return '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function currentTotal() {
            const months = Number(monthsSelect.value || 1);
            return monthlyFee * months;
        }

        function updateTotal() {
            totalDisplay.textContent = formatMoney(currentTotal());
        }

        monthsSelect.addEventListener('change', updateTotal);
        updateTotal();

        openModalBtn.addEventListener('click', function () {
            if (!referenceInput.reportValidity()) {
                return;
            }

            confirmMonths.textContent = monthsSelect.value + ' month(s)';
            confirmTotal.textContent = formatMoney(currentTotal());
            confirmReference.textContent = referenceInput.value.trim();
            confirmCheck.checked = false;
            confirmSubmitBtn.disabled = true;

            if (window.bootstrap && modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        confirmCheck.addEventListener('change', function () {
            confirmSubmitBtn.disabled = !confirmCheck.checked;
        });
    })();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
