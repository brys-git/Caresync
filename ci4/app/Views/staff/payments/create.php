<div class="card">
    <div class="card-header">Record Payment</div>
    <div class="card-body">
        <form action="<?= site_url('/staff/payments/store') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="<?= esc(old('status', 'paid')) ?>">
            <?php
                $selectedPlanId = (int) old('plan_id');
                $selectedPlanLabel = '';
                foreach ($plans as $plan) {
                    if ((int) $plan['plan_id'] === $selectedPlanId) {
                        $selectedPlanLabel = trim(((string) ($plan['first_name'] ?? '')) . ' ' . ((string) ($plan['last_name'] ?? ''))) . ' - Plan #' . (int) $plan['plan_id'];
                        break;
                    }
                }
            ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="staff_plan_holder_search" class="form-label">Plan Holder</label>
                    <input
                        type="search"
                        id="staff_plan_holder_search"
                        class="form-control"
                        list="staff_plan_holder_options"
                        value="<?= esc(old('plan_holder_search', $selectedPlanLabel)) ?>"
                        placeholder="Type plan holder name..."
                        autocomplete="off"
                        required
                    >
                    <datalist id="staff_plan_holder_options">
                        <?php foreach ($plans as $plan): ?>
                            <?php $holderName = trim(((string) ($plan['first_name'] ?? '')) . ' ' . ((string) ($plan['last_name'] ?? ''))); ?>
                            <?php $label = $holderName . ' - Plan #' . (int) $plan['plan_id']; ?>
                            <option value="<?= esc($label) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="plan_id" id="staff_plan_id" value="<?= $selectedPlanId > 0 ? $selectedPlanId : '' ?>" required>
                    <small id="staff_plan_holder_help" class="text-muted">Start typing the plan holder name, then choose from suggestions.</small>
                </div>

                <div class="col-md-3">
                    <label for="plan_id_display" class="form-label">Plan ID</label>
                    <input type="text" id="plan_id_display" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control" value="<?= esc(old('amount')) ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?= esc(old('payment_date', date('Y-m-d'))) ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-select" required>
                        <option value="cash" <?= old('payment_method') === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="gcash" <?= old('payment_method') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="reference_number" class="form-label">Reference Number (Optional)</label>
                    <input type="text" id="reference_number" name="reference_number" class="form-control" value="<?= esc(old('reference_number')) ?>">
                </div>

                <div class="col-12">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="3"><?= esc(old('remarks')) ?></textarea>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchEl = document.getElementById('staff_plan_holder_search');
    const hiddenPlanIdEl = document.getElementById('staff_plan_id');
    const planIdDisplayEl = document.getElementById('plan_id_display');
    const helpEl = document.getElementById('staff_plan_holder_help');
    const formEl = searchEl ? searchEl.closest('form') : null;

    const plans = [
        <?php foreach ($plans as $plan): ?>
            {
                id: <?= (int) $plan['plan_id'] ?>,
                label: <?= json_encode(trim(((string) ($plan['first_name'] ?? '')) . ' ' . ((string) ($plan['last_name'] ?? ''))) . ' - Plan #' . (int) $plan['plan_id']) ?>,
                name: <?= json_encode(trim(((string) ($plan['first_name'] ?? '')) . ' ' . ((string) ($plan['last_name'] ?? '')))) ?>,
            },
        <?php endforeach; ?>
    ];

    const syncPlanSelection = function () {
        const typed = (searchEl.value || '').trim().toLowerCase();

        if (!typed) {
            hiddenPlanIdEl.value = '';
            planIdDisplayEl.value = '';
            if (helpEl) {
                helpEl.textContent = 'Start typing the plan holder name, then choose from suggestions.';
            }
            return;
        }

        const exact = plans.find((plan) => plan.label.toLowerCase() === typed);
        if (exact) {
            hiddenPlanIdEl.value = String(exact.id);
            planIdDisplayEl.value = String(exact.id);
            if (helpEl) {
                helpEl.textContent = 'Selected: ' + exact.label;
            }
            return;
        }

        const byName = plans.find((plan) => plan.name.toLowerCase() === typed);
        if (byName) {
            hiddenPlanIdEl.value = String(byName.id);
            searchEl.value = byName.label;
            planIdDisplayEl.value = String(byName.id);
            if (helpEl) {
                helpEl.textContent = 'Selected: ' + byName.label;
            }
            return;
        }

        hiddenPlanIdEl.value = '';
        planIdDisplayEl.value = '';
        if (helpEl) {
            helpEl.textContent = 'No exact match yet. Choose a suggestion to continue.';
        }
    };

    if (searchEl) {
        searchEl.addEventListener('input', syncPlanSelection);
        searchEl.addEventListener('change', syncPlanSelection);
        syncPlanSelection();
    }

    if (formEl) {
        formEl.addEventListener('submit', function (event) {
            syncPlanSelection();
            if (!hiddenPlanIdEl.value) {
                event.preventDefault();
                searchEl.focus();
                if (helpEl) {
                    helpEl.textContent = 'Please select a valid plan holder from suggestions.';
                }
            }
        });
    }
});
</script>
