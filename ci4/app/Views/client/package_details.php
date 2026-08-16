<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'new'); ?>
<?php
$planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
$isDamayanMember = false;
if ($planHolderId > 0) {
    $isDamayanMember = (new \App\Services\DamayanService())->isQualifiedMember($planHolderId);
}
$variants = $variants ?? [];
$inclusions = $inclusions ?? [];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/damayan-calc.css') ?>">
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Package Details</h1>
            <p class="text-muted mb-0">Review the package information and select options.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=packages') ?>">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2"><?= esc((string) ($package['package_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>

            <!-- ===== Casket Variants ===== -->
            <div class="mb-4">
                <h6 class="fw-bold">Choose Casket Variant</h6>
                <?php if (empty($variants)): ?>
                    <div class="text-muted small">No variants available for this package.</div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($variants as $variant): ?>
                            <div class="col-md-6">
                                <label class="border rounded p-3 d-block" style="cursor:pointer;">
                                    <input type="radio" name="variant_id" value="<?= (int) $variant['variant_id'] ?>" <?= ((int) ($variant['is_default'] ?? 0) === 1) ? 'checked' : '' ?> class="me-2 variant-radio" data-price="<?= esc(number_format((float) ($variant['base_price'] ?? 0), 2, '.', '')) ?>">
                                    <strong><?= esc((string) ($variant['variant_name'] ?? '-')) ?></strong>
                                    <div class="text-primary fw-bold">₱<?= esc(number_format((float) ($variant['base_price'] ?? 0), 2)) ?></div>
                                    <?php if (! empty($variant['description'])): ?>
                                        <div class="small text-muted"><?= esc((string) $variant['description']) ?></div>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== Inclusions ===== -->
            <div class="mb-3">
                <h6 class="fw-bold">Package Includes:</h6>
                <ul class="list-unstyled mb-0">
                    <?php if (empty($inclusions)): ?>
                        <li class="text-muted">No inclusions listed.</li>
                    <?php else: ?>
                        <?php foreach ($inclusions as $inc): ?>
                            <li><i class="mdi mdi-check-circle text-success"></i> <?= esc((string) ($inc['item_name'] ?? '-')) ?>
                                <?php if (! empty($inc['description'])): ?>
                                    <small class="text-muted">— <?= esc((string) $inc['description']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== Optional Add-ons ===== -->
    <div class="card mb-3">
        <div class="card-header"><strong>Optional Add-ons</strong></div>
        <div class="card-body">
            <?php if (empty($add_ons)): ?>
                <div class="text-muted">No optional add-ons available.</div>
            <?php else: ?>
                <?php foreach ($add_ons as $addon): ?>
                    <div class="border rounded p-3 mb-2">
                        <label class="d-flex align-items-center justify-content-between" style="cursor:pointer;">
                            <div>
                                <input type="checkbox" name="addon_ids[]" value="<?= (int) $addon['addon_id'] ?>" class="me-2 addon-checkbox"
                                    data-min="<?= esc(number_format((float) ($addon['min_price'] ?? $addon['base_price'] ?? 0), 2, '.', '')) ?>"
                                    data-max="<?= esc(number_format((float) ($addon['max_price'] ?? $addon['base_price'] ?? 0), 2, '.', '')) ?>"
                                    data-base="<?= esc(number_format((float) ($addon['base_price'] ?? 0), 2, '.', '')) ?>">
                                <strong><?= esc((string) ($addon['addon_name'] ?? '-')) ?></strong>
                                <small class="text-muted">(₱<?= esc(number_format((float) ($addon['min_price'] ?? $addon['base_price'] ?? 0), 2)) ?>–₱<?= esc(number_format((float) ($addon['max_price'] ?? $addon['base_price'] ?? 0), 2)) ?>)</small>
                            </div>
                        </label>
                        <div class="mt-2 addon-price-row" style="display:none;">
                            <label class="form-label small">Select price within range:</label>
                            <input type="number" name="addon_price_<?= (int) $addon['addon_id'] ?>" class="form-control form-control-sm addon-price-input" step="0.01"
                                min="<?= esc(number_format((float) ($addon['min_price'] ?? $addon['base_price'] ?? 0), 2, '.', '')) ?>"
                                max="<?= esc(number_format((float) ($addon['max_price'] ?? $addon['base_price'] ?? 0), 2, '.', '')) ?>"
                                value="<?= esc(number_format((float) ($addon['base_price'] ?? 0), 2, '.', '')) ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Damayan Summary ===== -->
    <div class="card mb-3 border-info">
        <div class="card-header bg-info-subtle"><strong>Price Summary</strong></div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-1">
                <span>Package Price (<span id="variant-name-label">Selected variant</span>)</span>
                <strong>₱<span id="package-price-label">0.00</span></strong>
            </div>
            <div id="addon-summary-rows"></div>
            <hr>
            <div class="d-flex justify-content-between mb-1">
                <span>Subtotal</span>
                <strong>₱<span id="subtotal-label">0.00</span></strong>
            </div>
            <?php if ($isDamayanMember): ?>
                <div class="d-flex justify-content-between mb-1 text-success">
                    <span>Damayan Benefit (₱14,500 credit)</span>
                    <strong>-₱<span id="damayan-label">0.00</span></strong>
                </div>
            <?php endif; ?>
            <hr>
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-bold">Final Amount</span>
                <strong class="text-primary">₱<span id="final-label">0.00</span></strong>
            </div>
            <?php if ($isDamayanMember): ?>
                <div class="alert alert-success mt-2 mb-0 py-2 small">
                    <i class="mdi mdi-shield-check"></i> You are a qualified Damayan member. The ₱14,500 Damayan benefit is applied as a credit. Remaining contributions will be waived.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3">
        <?php if (in_array($state, ['approved', 'active'], true)): ?>
            <a class="btn btn-primary" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">Apply for Package</a>
        <?php elseif ($state === 'pending'): ?>
            <div class="alert alert-warning">Approval required before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const variantRadios = document.querySelectorAll('.variant-radio');
    const addonCheckboxes = document.querySelectorAll('.addon-checkbox');
    const packagePriceLabel = document.getElementById('package-price-label');
    const subtotalLabel = document.getElementById('subtotal-label');
    const finalLabel = document.getElementById('final-label');
    const damayanLabel = document.getElementById('damayan-label');
    const addonSummary = document.getElementById('addon-summary-rows');

    const isDamayan = <?= $isDamayanMember ? 'true' : 'false' ?>;
    const DAMAYAN_CREDIT = 14500;

    function recalc() {
        let packagePrice = 0;
        let variantSelected = false;
        variantRadios.forEach(r => {
            if (r.checked) {
                packagePrice = parseFloat(r.dataset.price || '0');
                variantSelected = true;
                const lbl = document.getElementById('variant-name-label');
                if (lbl) lbl.textContent = r.closest('label').querySelector('strong').textContent.trim();
            }
        });

        if (!variantSelected && variantRadios.length > 0) {
            packagePrice = parseFloat(variantRadios[0].dataset.price || '0');
        }

        let addonTotal = 0;
        addonSummary.innerHTML = '';
        addonCheckboxes.forEach(cb => {
            if (cb.checked) {
                const priceRow = cb.closest('.border').querySelector('.addon-price-input');
                const price = priceRow ? parseFloat(priceRow.value || '0') : parseFloat(cb.dataset.base || '0');
                addonTotal += price;
                const row = document.createElement('div');
                row.className = 'd-flex justify-content-between mb-1';
                row.innerHTML = '<span>' + (cb.closest('label').querySelector('strong').textContent.trim()) + '</span><strong>₱' + price.toFixed(2) + '</strong>';
                addonSummary.appendChild(row);
            }
        });

        const subtotal = packagePrice + addonTotal;
        packagePriceLabel.textContent = packagePrice.toFixed(2);
        subtotalLabel.textContent = subtotal.toFixed(2);

        let final = subtotal;
        if (isDamayan && subtotal > 20000) {
            final = subtotal - DAMAYAN_CREDIT;
            if (damayanLabel) damayanLabel.textContent = DAMAYAN_CREDIT.toFixed(2);
        } else if (isDamayan && damayanLabel) {
            damayanLabel.textContent = '0.00';
        }

        finalLabel.textContent = Math.max(0, final).toFixed(2);
    }

    variantRadios.forEach(r => r.addEventListener('change', recalc));
    addonCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const priceRow = cb.closest('.border').querySelector('.addon-price-row');
            if (priceRow) priceRow.style.display = cb.checked ? 'block' : 'none';
            recalc();
        });
    });
    document.querySelectorAll('.addon-price-input').forEach(inp => inp.addEventListener('input', recalc));

    recalc();
})();
</script>
<?= $this->endSection() ?>