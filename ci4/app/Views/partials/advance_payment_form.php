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
                            <option value="<?= (int) $plan['plan_id'] ?>" data-monthly-fee="<?= esc((string) $plan['monthly_fee']) ?>" data-remaining-months="<?= (int) ($plan['remaining_months'] ?? 0) ?>">
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
                <div class="col-md-3">
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
                    <label class="form-label" for="<?= $prefix ?>_amount">Amount (auto-computed)</label>
                    <input id="<?= $prefix ?>_amount" name="amount" type="number" step="0.01" class="form-control ap-amount-input" readonly required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="<?= $prefix ?>_payment_date">Payment Date</label>
                    <input id="<?= $prefix ?>_payment_date" name="payment_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><?= esc((string) ($ap_button_label ?? 'Record Advance Payment')) ?></button>
        </form>
    </div>
</section>
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
        plan.addEventListener('change', function () { syncMonths(); updateAmount(); });
        if (months) { months.addEventListener('change', updateAmount); }
        syncMonths();
        updateAmount();
    })();
</script>
