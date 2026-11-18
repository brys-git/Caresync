<?php
/**
 * Reusable Advance Payment recording form.
 *
 * Expected view data:
 *   string $ap_action        Form post URL (e.g. base_url('staff/payment-management/record-cash'))
 *   string $ap_prefix        Unique id prefix so multiple copies of this form can share a page
 *   array  $ap_plan_options  Rows with plan_id, monthly_fee, first_name, last_name, unique_identifier
 *   string $ap_heading       Card heading, e.g. "Cash / GCash Advance Payment Entry"
 *   string $ap_button_label  Submit button text
 *   bool   $ap_require_official_receipt (optional) kept for backward compatibility; receipt
 *          numbers are always auto-generated now, so this no longer changes the form itself.
 */
$prefix = (string) ($ap_prefix ?? 'ap');
?>
<section class="cs-panel mb-3">
    <div class="cs-panel__head"><h2 class="cs-panel__title"><?= esc((string) ($ap_heading ?? 'Advance Payment Entry')) ?></h2></div>
    <div class="cs-panel__body">
        <form method="post" action="<?= esc((string) $ap_action, 'attr') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="<?= $prefix ?>_reference_number">Reference Number <span class="text-muted small">(GCash only)</span></label>
                    <input id="<?= $prefix ?>_reference_number" name="reference_number" class="form-control" placeholder="Required for GCash, leave blank for cash" maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="<?= $prefix ?>_plan_id">Plan Holder</label>
                    <select id="<?= $prefix ?>_plan_id" name="plan_id" class="form-select ap-plan-select" required>
                        <option value="">Select plan holder</option>
                        <?php foreach (($ap_plan_options ?? []) as $plan): ?>
                            <option value="<?= (int) $plan['plan_id'] ?>" data-monthly-fee="<?= esc((string) $plan['monthly_fee']) ?>" data-remaining-months="<?= (int) ($plan['remaining_months'] ?? 0) ?>" data-amount-mode="<?= ! empty($plan['amount_mode']) ? '1' : '0' ?>" data-has-down="<?= ! empty($plan['has_down_due']) ? '1' : '0' ?>" data-down-left="<?= esc((string) ($plan['down_left'] ?? 0)) ?>">
                                <?= esc((string) ($plan['first_name'] . ' ' . $plan['last_name'])) ?> (<?= esc((string) ($plan['unique_identifier'] ?: 'No ID')) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($ap_plan_options ?? [])): ?>
                        <small class="text-muted">No eligible plan holders found.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="<?= $prefix ?>_payment_method">Payment Method</label>
                    <select id="<?= $prefix ?>_payment_method" name="payment_method" class="form-select ap-method-select" required>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                    </select>
                </div>
                <div class="col-md-3 ap-kind-wrap d-none">
                    <label class="form-label" for="<?= $prefix ?>_payment_kind">This payment is for</label>
                    <select id="<?= $prefix ?>_payment_kind" name="payment_kind" class="form-select ap-kind-select" disabled>
                        <option value="regular">Plan and package instalments</option>
                        <option value="down_payment" class="ap-kind-down">Package down payment</option>
                    </select>
                </div>
                <div class="col-md-3 ap-months-wrap">
                    <label class="form-label" for="<?= $prefix ?>_months_covered">Months in Advance</label>
                    <select id="<?= $prefix ?>_months_covered" name="months_covered" class="form-select ap-months-select" required>
                        <?php $maxMonths = max(array_merge([1], array_map(static fn (array $p): int => (int) ($p['remaining_months'] ?? 0), $ap_plan_options ?? []))); ?>
                        <?php for ($m = 1; $m <= $maxMonths; $m++): ?>
                            <option value="<?= $m ?>"><?= $m ?> Month<?= $m === 1 ? '' : 's' ?></option>
                        <?php endfor; ?>
                    </select>
                    <div class="form-text ap-months-note">Choose a plan holder to see how many months are left.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label ap-amount-label" for="<?= $prefix ?>_amount">Amount (auto-computed)</label>
                    <input id="<?= $prefix ?>_amount" name="amount" type="number" step="0.01" class="form-control ap-amount-input" readonly required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="<?= $prefix ?>_payment_date">Payment Date</label>
                    <input id="<?= $prefix ?>_payment_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0 d-none ap-preview" role="status"></div>
            <button type="submit" class="btn btn-primary mt-3"><?= esc((string) ($ap_button_label ?? 'Record Advance Payment')) ?></button>
        </form>
    </div>
</section>
<script src="<?= base_url('assets/js/allocation-preview.js') ?>"></script>
<script>
    (function () {
        const scope = document.getElementById('<?= $prefix ?>_plan_id')?.closest('form');
        if (!scope) { return; }
        const plan = scope.querySelector('.ap-plan-select');
        const amount = scope.querySelector('.ap-amount-input');
        const months = scope.querySelector('.ap-months-select');
        if (!plan || !amount) { return; }
        const note = scope.querySelector('.ap-months-note');
        // Months in Advance only goes up to what the chosen plan holder has left.
        function syncMonths() {
            if (!months) { return; }
            const selected = plan.options[plan.selectedIndex];
            if (!selected || !selected.value) { return; }
            const left = Number(selected.getAttribute('data-remaining-months') || 0);
            const keep = Number(months.value || 1);
            months.innerHTML = '';
            for (let m = 1; m <= left; m++) {
                const opt = document.createElement('option');
                opt.value = String(m);
                opt.textContent = m + (m === 1 ? ' Month' : ' Months');
                months.appendChild(opt);
            }
            months.disabled = left < 1;
            if (left > 0) { months.value = String(Math.min(keep, left)); }
            if (note) { note.textContent = left > 0 ? left + ' month(s) left in this plan\'s current cycle.' : 'No months left to pay in this plan\'s current cycle.'; }
        }
        function updateAmount() {
            const selected = plan.options[plan.selectedIndex];
            const fee = selected ? Number(selected.getAttribute('data-monthly-fee') || 0) : 0;
            const monthsValue = months ? Number(months.value || 1) : 1;
            amount.value = fee > 0 ? (fee * monthsValue).toFixed(2) : '';
        }
        // A client who also owes a package amount: enter an AMOUNT, with a preview of where it goes.
        const monthsWrap = scope.querySelector('.ap-months-wrap');
        const kindWrap = scope.querySelector('.ap-kind-wrap');
        const kind = scope.querySelector('.ap-kind-select');
        const preview = scope.querySelector('.ap-preview');
        const dateInput = scope.querySelector('input[name="payment_date"]');
        let amountMode = false;
        function applyMode() {
            const selected = plan.options[plan.selectedIndex];
            amountMode = !!selected && selected.getAttribute('data-amount-mode') === '1';
            if (monthsWrap) { monthsWrap.classList.toggle('d-none', amountMode); }
            if (months) { months.disabled = amountMode || months.options.length < 1; months.required = !amountMode; }
            if (kindWrap) { kindWrap.classList.toggle('d-none', !amountMode); }
            if (kind) {
                kind.disabled = !amountMode;
                const down = kind.querySelector('.ap-kind-down');
                const hasDown = !!selected && selected.getAttribute('data-has-down') === '1';
                if (down) { down.disabled = !hasDown; down.hidden = !hasDown; }
                if (!hasDown) { kind.value = 'regular'; }
            }
            amount.readOnly = !amountMode;
            const label = scope.querySelector('.ap-amount-label');
            if (label) { label.textContent = amountMode ? 'Amount received' : 'Amount (auto-computed)'; }
            if (preview) { preview.classList.add('d-none'); }
            if (amountMode) { amount.value = ''; amount.focus(); }
        }
        let previewer = null;
        if (preview && window.csAllocationPreview) {
            previewer = csAllocationPreview({
                url: '<?= base_url('payments/allocation-preview') ?>',
                plan: function () { return amountMode ? plan.value : ''; },
                amount: amount, date: dateInput,
                kind: function () { return kind ? kind.value : 'regular'; },
                out: preview,
            });
            if (kind) { kind.addEventListener('change', function () { previewer.refresh(); }); }
        }
        plan.addEventListener('change', function () { applyMode(); if (!amountMode) { syncMonths(); updateAmount(); } });
        if (months) { months.addEventListener('change', function () { if (!amountMode) { updateAmount(); } }); }
        syncMonths();
        updateAmount();
        applyMode();
    })();
</script>
