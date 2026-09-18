<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$plan = $plan ?? null;
$package = $package ?? null;
$inclusions = $inclusions ?? [];
$isEdit = $program_id !== null;
$actionUrl = $isEdit ? base_url('plan-builder/' . (int) $program_id . '/update') : base_url('plan-builder/store');
if (empty($inclusions)) {
    $inclusions = [['item_name' => '', 'description' => '']];
}
?>

<a class="text-decoration-none text-muted mb-3 d-inline-block" href="<?= base_url($isEdit ? 'plan-builder/' . (int) $program_id : 'plan-builder') ?>">
    <i class="ti ti-arrow-left me-1"></i> <?= $isEdit ? 'Back to Plan' : 'Back to Plan Builder' ?>
</a>

<form method="post" action="<?= $actionUrl ?>" id="planBuilderForm">
    <?= csrf_field() ?>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Plan details</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="plan_name">Plan Name</label>
                    <input id="plan_name" name="plan_name" class="form-control <?= isset($errors['plan_name']) ? 'is-invalid' : '' ?>" maxlength="150" required
                        value="<?= esc(old('plan_name', (string) ($plan['program_name'] ?? ''))) ?>">
                    <?php if (isset($errors['plan_name'])): ?><div class="invalid-feedback"><?= esc($errors['plan_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="plan_price">Plan Price</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input id="plan_price" name="plan_price" type="number" min="0.01" step="0.01" class="form-control <?= isset($errors['plan_price']) ? 'is-invalid' : '' ?>" required
                            value="<?= esc(old('plan_price', (string) ($plan['plan_price'] ?? '14500.00'))) ?>">
                    </div>
                    <?php if (isset($errors['plan_price'])): ?><div class="invalid-feedback d-block"><?= esc($errors['plan_price']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="monthly_fee">Monthly Contribution</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input id="monthly_fee" name="monthly_fee" type="number" min="0.01" step="0.01" class="form-control <?= isset($errors['monthly_fee']) ? 'is-invalid' : '' ?>" required
                            value="<?= esc(old('monthly_fee', (string) ($plan['monthly_fee'] ?? '240.00'))) ?>">
                    </div>
                    <?php if (isset($errors['monthly_fee'])): ?><div class="invalid-feedback d-block"><?= esc($errors['monthly_fee']) ?></div><?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label d-block">Term</label>
                    <strong id="termDisplay" class="fs-5">-</strong>
                    <div class="form-text">Calculated from plan price ÷ monthly contribution.</div>
                </div>
                <div class="col-md-9">
                    <label class="form-label" for="description">Description</label>
                    <input id="description" name="description" class="form-control" maxlength="500"
                        value="<?= esc(old('description', (string) ($plan['description'] ?? ''))) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label d-block">Status</label>
                    <?php $isActive = (int) old('is_active', (string) ($plan['is_active'] ?? 1)) === 1; ?>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active_switch" <?= $isActive ? 'checked' : '' ?>
                            onchange="document.getElementById('is_active').value = this.checked ? '1' : '0';">
                        <label class="form-check-label" for="is_active_switch">Active</label>
                    </div>
                    <input type="hidden" id="is_active" name="is_active" value="<?= $isActive ? '1' : '0' ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Entitled package</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="package_name">Package Name</label>
                    <input id="package_name" name="package_name" class="form-control <?= isset($errors['package_name']) ? 'is-invalid' : '' ?>" maxlength="100" required
                        value="<?= esc(old('package_name', (string) ($package['package_name'] ?? ''))) ?>">
                    <?php if (isset($errors['package_name'])): ?><div class="invalid-feedback"><?= esc($errors['package_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="package_description">Package Description</label>
                    <input id="package_description" name="package_description" class="form-control" maxlength="500"
                        value="<?= esc(old('package_description', (string) ($package['description'] ?? ''))) ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Inclusions</h2></div>
        <div class="cs-panel__body">
            <?php if (isset($errors['inclusions'])): ?>
                <div class="alert alert-danger py-2"><?= esc($errors['inclusions']) ?></div>
            <?php endif; ?>
            <div id="inclusionRows">
                <?php foreach ($inclusions as $i => $item): ?>
                    <div class="row g-2 mb-2 inclusion-row">
                        <div class="col-md-5">
                            <input name="item_name[]" class="form-control" placeholder="Item name (e.g. Embalming)" value="<?= esc((string) ($item['item_name'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <input name="item_description[]" class="form-control" placeholder="Details (optional)" value="<?= esc((string) ($item['description'] ?? '')) ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row"><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="addRowBtn"><i class="ti ti-plus me-1"></i>Add row</button>
        </div>
    </section>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Plan' ?></button>
        <a href="<?= base_url($isEdit ? 'plan-builder/' . (int) $program_id : 'plan-builder') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<template id="inclusionRowTemplate">
    <div class="row g-2 mb-2 inclusion-row">
        <div class="col-md-5">
            <input name="item_name[]" class="form-control" placeholder="Item name (e.g. Embalming)">
        </div>
        <div class="col-md-6">
            <input name="item_description[]" class="form-control" placeholder="Details (optional)">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" title="Remove row"><i class="ti ti-x"></i></button>
        </div>
    </div>
</template>

<script>
(function () {
    const priceInput = document.getElementById('plan_price');
    const feeInput = document.getElementById('monthly_fee');
    const termDisplay = document.getElementById('termDisplay');

    function updateTerm() {
        const price = parseFloat(priceInput.value || '0');
        const fee = parseFloat(feeInput.value || '0');
        if (price > 0 && fee > 0) {
            termDisplay.textContent = Math.ceil(price / fee) + ' months';
        } else {
            termDisplay.textContent = '-';
        }
    }

    priceInput.addEventListener('input', updateTerm);
    feeInput.addEventListener('input', updateTerm);
    updateTerm();

    const rowsContainer = document.getElementById('inclusionRows');
    const template = document.getElementById('inclusionRowTemplate');

    document.getElementById('addRowBtn').addEventListener('click', function () {
        rowsContainer.appendChild(template.content.cloneNode(true));
    });

    rowsContainer.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row-btn');
        if (!btn) {
            return;
        }
        const rows = rowsContainer.querySelectorAll('.inclusion-row');
        if (rows.length > 1) {
            btn.closest('.inclusion-row').remove();
        } else {
            // Keep at least one row - just clear it instead of removing.
            btn.closest('.inclusion-row').querySelectorAll('input').forEach(function (input) { input.value = ''; });
        }
    });
})();
</script>
<?= $this->endSection() ?>
