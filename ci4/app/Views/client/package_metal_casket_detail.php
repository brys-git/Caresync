<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-catalog.css') ?>">

<?php
$packageName = (string) ($package['package_name'] ?? 'Metal Casket');
$packageDesc = (string) ($package['description'] ?? '');
$basePrice = (float) ($package['base_price'] ?? 0);
$planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);

// Damayan constants
$DAMAYAN_BENEFIT = 14500;
$MIN_PACKAGE_FOR_BENEFIT = 20000;

// Pick first variant as default
$defaultVariant = null;
foreach ($variants as $v) {
    if ((int) ($v['is_default'] ?? 0) === 1) { $defaultVariant = $v; break; }
}
if (! $defaultVariant && ! empty($variants)) { $defaultVariant = $variants[0]; }
?>

<div class="sc">
    <div class="sc-container">

        <!-- ====== Page Hero ====== -->
        <div class="sc-page-hero" style="background-image: url('<?= base_url('assets/images/product-3.png') ?>');">
            <div class="sc-page-hero__overlay"></div>
            <div class="sc-page-hero__content">
                <div class="sc-page-hero__eyebrow">
                    <i class="mdi mdi-package-variant-closed"></i>
                    Casket Package
                </div>
                <h1 class="sc-page-hero__title"><?= esc($packageName) ?></h1>
                <p class="sc-page-hero__sub">Premium metal casket with superior durability and elegant finish. Multiple gauge variants and optional burial attire available.</p>
            </div>
        </div>

        <!-- ====== Breadcrumb ====== -->
        <nav class="sc-breadcrumb" style="padding:16px 0 8px;" aria-label="Breadcrumb">
            <a href="<?= site_url('/client/service?tab=packages') ?>" class="sc-breadcrumb__item"><i class="mdi mdi-arrow-left"></i> All Packages</a>
            <span class="sc-breadcrumb__sep">/</span>
            <span class="sc-breadcrumb__item sc-breadcrumb__item--current"><?= esc($packageName) ?></span>
        </nav>

        <!-- ====== Damayan Eligibility Callout ====== -->
        <?php if ($isDamayanMember): ?>
        <div class="sc-damayan-callout">
            <div class="sc-damayan-callout__icon"><i class="mdi mdi-shield-check-outline"></i></div>
            <div class="sc-damayan-callout__content">
                <div class="sc-damayan-callout__title">Damayan Member Benefit Active</div>
                <div class="sc-damayan-callout__text">You are a qualified Damayan member. You receive a <strong>₱14,500 credit</strong> on this package (over ₱20,000) with remaining contributions waived.</div>
            </div>
            <span class="sc-badge sc-badge--green">Qualified</span>
        </div>
        <?php else: ?>
        <div class="sc-damayan-callout" style="background:linear-gradient(135deg,var(--sc-amber-soft) 0%,#fff8e1 100%);border-color:var(--sc-amber);">
            <div class="sc-damayan-callout__icon" style="color:var(--sc-amber);"><i class="mdi mdi-shield-outline"></i></div>
            <div class="sc-damayan-callout__content">
                <div class="sc-damayan-callout__title">Damayan Benefit Available</div>
                <div class="sc-damayan-callout__text">Qualified Damayan members receive a <strong>₱14,500 credit</strong> on packages over ₱20,000 with remaining contributions waived. <a href="<?= site_url('/client/membership') ?>" style="color:var(--sc-amber);font-weight:600;">Check eligibility</a></div>
            </div>
            <span class="sc-badge sc-badge--amber">Not Qualified</span>
        </div>
        <?php endif; ?>

        <!-- ====== Main Content Grid ====== -->
        <div class="sc-detail-grid">

            <!-- Left Column: Hero Media & Info -->
            <div class="sc-detail-main">

                <!-- Hero Image -->
                <div class="sc-detail-hero">
                    <img src="<?= base_url('assets/images/product-3.png') ?>" alt="<?= esc($packageName) ?>">
                </div>

                <!-- Description -->
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-information-outline"></i>
                        <div>
                            <div class="sc-block__title">About This Package</div>
                            <div class="sc-block__sub">Superior metal casket with lasting protection and dignity</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <p><?= nl2br(esc($packageDesc)) ?></p>
                        <p style="margin-top:12px;">Our Metal Casket collection features premium-grade steel with protective finishes that ensure lasting durability. Each package includes complete memorial services and all standard inclusions. Choose from various gauge thicknesses and interior fabrics.</p>
                    </div>
                </div>

                <!-- Variants -->
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-package-variant-closed"></i>
                        <div>
                            <div class="sc-block__title">Select Variant</div>
                            <div class="sc-block__sub">Choose the metal gauge and interior that suits your needs</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <div class="sc-variant-grid" id="sc-variant-grid">
                            <?php if (! empty($variants)): ?>
                                <?php foreach ($variants as $i => $v):
                                    $vId = (int) ($v['variant_id'] ?? $i + 1);
                                    $vName = esc((string) ($v['variant_name'] ?? 'Variant'));
                                    $vDesc = esc((string) ($v['description'] ?? ''));
                                    $vPrice = (float) ($v['base_price'] ?? 0);
                                    $isDefault = (int) ($v['is_default'] ?? 0) === 1;
                                    $isChecked = $i === 0 ? 'checked' : '';
                                ?>
                                <label class="sc-variant-card <?= $isDefault ? 'sc-variant-card--default' : '' ?>" data-variant-id="<?= $vId ?>" data-price="<?= $vPrice ?>" onclick="scSelectVariant(this, <?= $vPrice ?>)">
                                    <input type="radio" name="sc_variant" value="<?= $vId ?>" <?= $isChecked ?> class="sc-variant-card__radio">
                                    <span class="sc-variant-card__check"><i class="mdi mdi-check-circle"></i></span>
                                    <span class="sc-variant-card__name"><?= $vName ?></span>
                                    <?php if ($vDesc): ?><span class="sc-variant-card__desc"><?= $vDesc ?></span><?php endif; ?>
                                    <span class="sc-variant-card__price"><?= $vPrice > 0 ? '₱' . number_format($vPrice, 2) : 'Included' ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <label class="sc-variant-card sc-variant-card--default" data-variant-id="1" data-price="0" onclick="scSelectVariant(this, 0)">
                                    <input type="radio" name="sc_variant" value="1" checked class="sc-variant-card__radio">
                                    <span class="sc-variant-card__check"><i class="mdi mdi-check-circle"></i></span>
                                    <span class="sc-variant-card__name">Standard</span>
                                    <span class="sc-variant-card__desc">Base configuration</span>
                                    <span class="sc-variant-card__price">Included</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Inclusions -->
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-format-list-checks"></i>
                        <div>
                            <div class="sc-block__title">What's Included</div>
                            <div class="sc-block__sub">8 standard inclusions with every Metal Casket package</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <div class="sc-inclusion-grid">
                            <?php if (! empty($inclusions)): ?>
                                <?php foreach ($inclusions as $inc):
                                    $incName = esc((string) ($inc['item_name'] ?? 'Inclusion'));
                                    $incDesc = esc((string) ($inc['description'] ?? ''));
                                ?>
                                <div class="sc-inclusion-item">
                                    <i class="mdi mdi-check-circle-outline"></i>
                                    <div>
                                        <div class="sc-inclusion-item__name"><?= $incName ?></div>
                                        <?php if ($incDesc): ?><div class="sc-inclusion-item__desc"><?= $incDesc ?></div><?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php
                                $defaultInclusions = [
                                    'Premium metal casket (18/20 gauge)',
                                    'Protective finish with rust resistance',
                                    'Professional embalming & preparation',
                                    'Memorial service arrangement',
                                    'Transport to cemetery (within Metro Manila)',
                                    'Viewing setup with floral tribute',
                                    'Obituary notice assistance',
                                    'Death certificate processing support'
                                ];
                                foreach ($defaultInclusions as $inc):
                                ?>
                                <div class="sc-inclusion-item">
                                    <i class="mdi mdi-check-circle-outline"></i>
                                    <div>
                                        <div class="sc-inclusion-item__name"><?= esc($inc) ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Add-ons -->
                <?php $burialAttire = null; foreach ($add_ons as $ao) { if (stripos((string)($ao['addon_name'] ?? ''), 'attire') !== false) { $burialAttire = $ao; break; } } ?>
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-plus-circle-outline"></i>
                        <div>
                            <div class="sc-block__title">Optional Add-ons</div>
                            <div class="sc-block__sub">Personalize your package with additional services</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <label class="sc-addon-card" onclick="scToggleAddon(this)">
                            <input type="checkbox" name="sc_addon_attire" class="sc-addon-card__check" onchange="scToggleAddonFromCheckbox(this)">
                            <span class="sc-addon-card__icon"><i class="mdi mdi-tshirt-crew"></i></span>
                            <div class="sc-addon-card__body">
                                <div class="sc-addon-card__name"><?= esc((string) ($burialAttire['addon_name'] ?? 'Burial Attire')) ?></div>
                                <div class="sc-addon-card__desc"><?= esc((string) ($burialAttire['description'] ?? 'Choose dignified attire for your loved one (range based on selection)')) ?></div>
                                <div class="sc-addon-card__price" data-min="<?= (float) ($burialAttire['min_price'] ?? 1500) ?>" data-max="<?= (float) ($burialAttire['max_price'] ?? 2000) ?>">
                                    ₱<?= number_format((float) ($burialAttire['min_price'] ?? 1500), 2) ?> – ₱<?= number_format((float) ($burialAttire['max_price'] ?? 2000), 2) ?>
                                </div>
                                <div class="sc-addon-card__input" style="display:none;margin-top:10px;">
                                    <label style="font-size:0.78rem;color:var(--sc-ink-faint);">Set price (₱<?= number_format((float) ($burialAttire['min_price'] ?? 1500), 2) ?> – ₱<?= number_format((float) ($burialAttire['max_price'] ?? 2000), 2) ?>)</label>
                                    <input type="number" id="sc-attire-price" class="sc-input" value="<?= (float) ($burialAttire['min_price'] ?? 1500) ?>" min="<?= (float) ($burialAttire['min_price'] ?? 1500) ?>" max="<?= (float) ($burialAttire['max_price'] ?? 2000) ?>" oninput="scUpdateAttirePrice(this)" onchange="scUpdateAttirePrice(this)">
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Right Column: Sticky Price Summary & Apply -->
            <div class="sc-detail-sidebar">
                <div class="sc-price-summary" id="sc-price-summary">

                    <!-- Base Price -->
                    <div class="sc-price-summary__row">
                        <span class="sc-price-summary__label">Base Package Price</span>
                        <span class="sc-price-summary__value" id="sc-base-price">₱<?= number_format($basePrice, 2) ?></span>
                    </div>

                    <!-- Selected Variant -->
                    <div class="sc-price-summary__row sc-price-summary__row--variant" id="sc-variant-row" style="display:none;">
                        <span class="sc-price-summary__label" id="sc-variant-label">Variant</span>
                        <span class="sc-price-summary__value" id="sc-variant-price">₱0.00</span>
                    </div>

                    <!-- Add-on -->
                    <div class="sc-price-summary__row sc-price-summary__row--addon" id="sc-addon-row" style="display:none;">
                        <span class="sc-price-summary__label" id="sc-addon-label">Burial Attire</span>
                        <span class="sc-price-summary__value" id="sc-addon-price">₱0.00</span>
                    </div>

                    <div class="sc-price-summary__divider"></div>

                    <!-- Subtotal -->
                    <div class="sc-price-summary__row sc-price-summary__row--subtotal">
                        <span class="sc-price-summary__label">Subtotal</span>
                        <span class="sc-price-summary__value" id="sc-subtotal-price">₱<?= number_format($basePrice, 2) ?></span>
                    </div>

                    <!-- Damayan Discount -->
                    <?php if ($isDamayanMember): ?>
                    <div class="sc-price-summary__row sc-price-summary__row--discount" id="sc-damayan-row">
                        <span class="sc-price-summary__label">
                            <i class="mdi mdi-shield-check-outline" style="margin-right:6px;color:var(--sc-success);"></i>
                            Damayan Benefit (₱14,500)
                        </span>
                        <span class="sc-price-summary__value sc-price-summary__value--discount" id="sc-damayan-discount">-₱14,500.00</span>
                    </div>
                    <?php endif; ?>

                    <div class="sc-price-summary__divider sc-price-summary__divider--thick"></div>

                    <!-- Total -->
                    <div class="sc-price-summary__row sc-price-summary__row--total">
                        <span class="sc-price-summary__label">Total</span>
                        <span class="sc-price-summary__value" id="sc-total-price">₱<?= number_format($basePrice, 2) ?></span>
                    </div>

                    <!-- Member Credit Note -->
                    <?php if ($isDamayanMember): ?>
                    <div class="sc-price-summary__note sc-price-summary__note--success">
                        <i class="mdi mdi-information-outline"></i> Damayan credit applied. Remaining contributions waived upon approval.
                    </div>
                    <?php else: ?>
                    <div class="sc-price-summary__note">
                        <i class="mdi mdi-information-outline"></i> Non-Damayan clients pay the full listed price.
                    </div>
                    <?php endif; ?>

                    <!-- Apply Button -->
                    <div class="sc-price-summary__actions">
                        <?php if ($can_apply): ?>
                            <a href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>" class="sc-btn sc-btn--primary sc-btn--lg sc-btn--block" id="sc-apply-btn">
                                <i class="mdi mdi-send-outline"></i> Apply Now
                            </a>
                        <?php else: ?>
                            <button class="sc-btn sc-btn--primary sc-btn--lg sc-btn--block" disabled style="opacity:.6;cursor:not-allowed;">
                                <i class="mdi mdi-lock-outline"></i> Complete 2 Months Contributions to Apply
                            </button>
                            <p style="text-align:center;font-size:0.78rem;color:var(--sc-ink-faint);margin-top:8px;">You need <strong><?= max(0, 2 - (int) ($membership['months_paid'] ?? 0)) ?></strong> more month(s) of contributions to unlock applications.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Trust Badges -->
                    <div class="sc-price-summary__trust">
                        <div class="sc-trust-item"><i class="mdi mdi-shield-check-outline"></i> Licensed & Insured</div>
                        <div class="sc-trust-item"><i class="mdi mdi-package-variant-closed"></i> Premium Durability</div>
                        <div class="sc-trust-item"><i class="mdi mdi-clock-outline"></i> 24/7 Support</div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

<script>
(function() {
    'use strict';

    const DAMAYAN_BENEFIT = 14500;
    const MIN_PACKAGE_FOR_BENEFIT = 20000;

    const basePrice = <?= $basePrice ?>;
    const isDamayanMember = <?= $isDamayanMember ? 'true' : 'false' ?>;

    const basePriceEl = document.getElementById('sc-base-price');
    const variantRow = document.getElementById('sc-variant-row');
    const variantLabel = document.getElementById('sc-variant-label');
    const variantPrice = document.getElementById('sc-variant-price');
    const addonRow = document.getElementById('sc-addon-row');
    const addonLabel = document.getElementById('sc-addon-label');
    const addonPrice = document.getElementById('sc-addon-price');
    const subtotalEl = document.getElementById('sc-subtotal-price');
    const damayanRow = document.getElementById('sc-damayan-row');
    const damayanDiscount = document.getElementById('sc-damayan-discount');
    const totalEl = document.getElementById('sc-total-price');

    let selectedVariantPrice = 0;
    let selectedVariantName = '';
    let addonPriceValue = 0;

    function formatCurrency(amount) {
        return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function scSelectVariant(el, price) {
        document.querySelectorAll('.sc-variant-card').forEach(function(c) { c.classList.remove('sc-variant-card--active'); });
        el.classList.add('sc-variant-card--active');
        el.querySelector('input[type="radio"]').checked = true;

        selectedVariantPrice = parseFloat(price) || 0;
        selectedVariantName = el.querySelector('.sc-variant-card__name').textContent.trim();

        if (selectedVariantPrice > 0) {
            variantRow.style.display = 'flex';
            variantLabel.textContent = selectedVariantName;
            variantPrice.textContent = formatCurrency(selectedVariantPrice);
        } else {
            variantRow.style.display = 'none';
        }
        scCalculateTotal();
    }

    function scToggleAddonFromCheckbox(checkbox) {
        const card = checkbox.closest('.sc-addon-card');
        if (checkbox.checked) {
            card.classList.add('sc-addon-card--active');
            card.querySelector('.sc-addon-card__input').style.display = 'block';
            const priceInput = document.getElementById('sc-attire-price');
            addonPriceValue = parseFloat(priceInput.value) || 0;
            addonRow.style.display = 'flex';
            addonLabel.textContent = card.querySelector('.sc-addon-card__name').textContent.trim();
            addonPrice.textContent = formatCurrency(addonPriceValue);
        } else {
            card.classList.remove('sc-addon-card--active');
            card.querySelector('.sc-addon-card__input').style.display = 'none';
            addonPriceValue = 0;
            addonRow.style.display = 'none';
        }
        scCalculateTotal();
    }

    function scToggleAddon(card) {
        const checkbox = card.querySelector('input[type="checkbox"]');
        if (! checkbox.checked) {
            checkbox.checked = true;
            scToggleAddonFromCheckbox(checkbox);
        }
    }

    function scUpdateAttirePrice(input) {
        let val = parseFloat(input.value) || 0;
        const min = parseFloat(input.min) || 1500;
        const max = parseFloat(input.max) || 2000;
        if (val < min) val = min;
        if (val > max) val = max;
        input.value = val;
        addonPriceValue = val;
        addonPrice.textContent = formatCurrency(val);
        scCalculateTotal();
    }

    function scCalculateTotal() {
        const subtotal = basePrice + selectedVariantPrice + addonPriceValue;
        subtotalEl.textContent = formatCurrency(subtotal);

        let damayanCredit = 0;
        if (isDamayanMember && damayanRow) {
            const packageEligible = (basePrice + selectedVariantPrice) > MIN_PACKAGE_FOR_BENEFIT;
            if (packageEligible) {
                damayanCredit = DAMAYAN_BENEFIT;
                damayanRow.style.display = 'flex';
                damayanDiscount.textContent = '-' + formatCurrency(damayanCredit);
            } else {
                damayanRow.style.display = 'none';
            }
        }

        const total = Math.max(0, subtotal - damayanCredit);
        totalEl.textContent = formatCurrency(total);
    }

    // Initialize
    const firstVariant = document.querySelector('.sc-variant-card');
    if (firstVariant) {
        scSelectVariant(firstVariant, parseFloat(firstVariant.getAttribute('data-price')) || 0);
    }
    scCalculateTotal();

    window.scSelectVariant = scSelectVariant;
    window.scToggleAddon = scToggleAddon;
    window.scToggleAddonFromCheckbox = scToggleAddonFromCheckbox;
    window.scUpdateAttirePrice = scUpdateAttirePrice;
    window.scCalculateTotal = scCalculateTotal;

})();
</script>

<style>
/* Page-specific overrides */
.sc-page-hero {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    min-height: 320px;
    display: flex;
    align-items: center;
    padding: 48px 0;
    border-radius: var(--sc-radius-xl);
    overflow: hidden;
}
.sc-page-hero__overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(30, 58, 95, 0.92) 0%, rgba(30, 58, 95, 0.78) 100%); z-index: 0; }
.sc-page-hero__content { position: relative; z-index: 1; max-width: 800px; margin: 0 auto; text-align: center; padding: 0 24px; }
.sc-page-hero__eyebrow { display: inline-flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.9); font-size: 0.88rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 12px; }
.sc-page-hero__title { color: #fff; font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; line-height: 1.15; margin: 0 0 12px; }
.sc-page-hero__sub { color: rgba(255,255,255,0.88); font-size: clamp(0.95rem, 2vw, 1.1rem); line-height: 1.6; margin: 0; max-width: 640px; margin-left: auto; margin-right: auto; }

.sc-breadcrumb { font-size: 0.82rem; color: var(--sc-ink-faint); }
.sc-breadcrumb__item { color: var(--sc-ink-faint); text-decoration: none; transition: color var(--sc-fast); }
.sc-breadcrumb__item:hover { color: var(--sc-primary); }
.sc-breadcrumb__sep { margin: 0 8px; }
.sc-breadcrumb__item--current { color: var(--sc-ink); font-weight: 600; pointer-events: none; }

.sc-damayan-callout { display: flex; align-items: center; gap: 16px; padding: 16px 20px; border-radius: var(--sc-radius-lg); border: 1px solid var(--sc-primary); background: linear-gradient(135deg, var(--sc-primary-soft) 0%, rgba(30,58,95,0.04) 100%); margin-bottom: 24px; }
.sc-damayan-callout__icon { font-size: 1.75rem; color: var(--sc-primary); flex-shrink: 0; }
.sc-damayan-callout__content { flex: 1; min-width: 0; }
.sc-damayan-callout__title { font-weight: 700; color: var(--sc-ink); font-size: 1rem; margin-bottom: 4px; }
.sc-damayan-callout__text { color: var(--sc-ink-dim); font-size: 0.86rem; line-height: 1.55; }
.sc-damayan-callout__text a { text-decoration: underline; }

.sc-detail-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
@media (min-width: 980px) { .sc-detail-grid { grid-template-columns: 1fr 360px; align-items: start; } }

.sc-detail-hero { border-radius: var(--sc-radius-lg); overflow: hidden; background: var(--sc-surface); box-shadow: var(--sc-shadow-sm); }
.sc-detail-hero img { width: 100%; height: auto; display: block; aspect-ratio: 4/3; object-fit: cover; }

.sc-block { background: var(--sc-surface); border: 1px solid var(--sc-border); border-radius: var(--sc-radius-lg); padding: 24px; margin-bottom: 20px; }
.sc-block__head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
.sc-block__head i { font-size: 1.35rem; color: var(--sc-primary); margin-top: 2px; flex-shrink: 0; }
.sc-block__title { font-weight: 700; color: var(--sc-ink); font-size: 1.05rem; }
.sc-block__sub { color: var(--sc-ink-faint); font-size: 0.82rem; margin-top: 2px; }
.sc-block__body { color: var(--sc-ink-dim); line-height: 1.7; font-size: 0.9rem; }
.sc-block__body p { margin: 0 0 12px; }
.sc-block__body p:last-child { margin-bottom: 0; }

/* Variants */
.sc-variant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.sc-variant-card { position: relative; display: flex; flex-direction: column; padding: 16px; border: 2px solid var(--sc-border); border-radius: var(--sc-radius-md); background: var(--sc-bg); cursor: pointer; transition: all var(--sc-fast); }
.sc-variant-card:hover { border-color: var(--sc-primary); box-shadow: var(--sc-shadow-sm); transform: translateY(-2px); }
.sc-variant-card--active { border-color: var(--sc-primary); background: var(--sc-primary-soft); }
.sc-variant-card--default::before { content: 'Popular'; position: absolute; top: -10px; right: 12px; background: var(--sc-primary); color: #fff; font-size: 0.66rem; font-weight: 700; padding: 2px 10px; border-radius: var(--sc-radius-full); text-transform: uppercase; letter-spacing: 0.04em; }
.sc-variant-card__radio { position: absolute; opacity: 0; pointer-events: none; }
.sc-variant-card__check { position: absolute; top: 12px; right: 12px; font-size: 1.3rem; color: var(--sc-border); transition: color var(--sc-fast); }
.sc-variant-card--active .sc-variant-card__check { color: var(--sc-primary); }
.sc-variant-card__name { font-weight: 700; color: var(--sc-ink); font-size: 0.95rem; margin-bottom: 4px; }
.sc-variant-card__desc { font-size: 0.78rem; color: var(--sc-ink-faint); line-height: 1.4; margin-bottom: 8px; }
.sc-variant-card__price { font-size: 0.88rem; font-weight: 700; color: var(--sc-primary); margin-top: auto; }

/* Inclusions */
.sc-inclusion-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.sc-inclusion-item { display: flex; align-items: flex-start; gap: 10px; padding: 12px; background: var(--sc-bg); border-radius: var(--sc-radius-md); border: 1px solid var(--sc-border); }
.sc-inclusion-item i { font-size: 1.2rem; color: var(--sc-success); flex-shrink: 0; margin-top: 1px; }
.sc-inclusion-item__name { font-weight: 600; color: var(--sc-ink); font-size: 0.85rem; line-height: 1.4; }
.sc-inclusion-item__desc { font-size: 0.74rem; color: var(--sc-ink-faint); line-height: 1.4; margin-top: 2px; }

/* Add-ons */
.sc-addon-card { display: flex; align-items: flex-start; gap: 14px; padding: 16px; border: 2px solid var(--sc-border); border-radius: var(--sc-radius-md); background: var(--sc-bg); cursor: pointer; transition: all var(--sc-fast); }
.sc-addon-card:hover { border-color: var(--sc-primary); }
.sc-addon-card--active { border-color: var(--sc-success); background: var(--sc-success-soft); }
.sc-addon-card__check { margin-top: 3px; width: 18px; height: 18px; accent-color: var(--sc-success); flex-shrink: 0; }
.sc-addon-card__icon { font-size: 1.6rem; color: var(--sc-primary); }
.sc-addon-card__body { flex: 1; min-width: 0; }
.sc-addon-card__name { font-weight: 700; color: var(--sc-ink); font-size: 0.92rem; }
.sc-addon-card__desc { font-size: 0.78rem; color: var(--sc-ink-faint); line-height: 1.5; margin: 4px 0; }
.sc-addon-card__price { font-weight: 700; color: var(--sc-primary); font-size: 0.88rem; }
.sc-input { width: 100%; max-width: 200px; padding: 8px 12px; border: 1px solid var(--sc-border); border-radius: var(--sc-radius-sm); font-size: 0.88rem; }

/* Price Summary */
.sc-price-summary { position: sticky; top: 100px; background: var(--sc-surface); border: 1px solid var(--sc-border); border-radius: var(--sc-radius-lg); padding: 24px; max-height: calc(100vh - 120px); overflow-y: auto; }
.sc-price-summary__row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 0.92rem; }
.sc-price-summary__label { color: var(--sc-ink-dim); font-weight: 500; text-align: left; }
.sc-price-summary__value { color: var(--sc-ink); font-weight: 700; font-size: 1rem; }
.sc-price-summary__value--discount { color: var(--sc-success); }
.sc-price-summary__row--total { font-size: 1.15rem; padding-top: 16px; margin-top: 8px; }
.sc-price-summary__row--total .sc-price-summary__label { font-weight: 700; color: var(--sc-ink); }
.sc-price-summary__row--total .sc-price-summary__value { font-size: 1.35rem; color: var(--sc-primary); font-weight: 800; }
.sc-price-summary__divider { height: 1px; background: var(--sc-border); margin: 8px 0; }
.sc-price-summary__divider--thick { height: 2px; background: linear-gradient(90deg, transparent, var(--sc-border), transparent); margin: 16px 0; }
.sc-price-summary__note { font-size: 0.76rem; color: var(--sc-ink-faint); background: var(--sc-bg); padding: 10px 14px; border-radius: var(--sc-radius-md); margin: 12px 0; line-height: 1.5; display: flex; gap: 8px; }
.sc-price-summary__note i { flex-shrink: 0; margin-top: 1px; }
.sc-price-summary__note--success { color: var(--sc-success); background: var(--sc-success-soft); }
.sc-price-summary__actions { margin-top: 16px; }
.sc-btn--block { width: 100%; justify-content: center; }
.sc-price-summary__trust { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--sc-border); }
.sc-trust-item { display: inline-flex; align-items: center; gap: 6px; font-size: 0.76rem; color: var(--sc-ink-faint); background: var(--sc-bg); padding: 6px 12px; border-radius: var(--sc-radius-full); }
.sc-trust-item i { font-size: 0.85rem; color: var(--sc-primary); }

@media (max-width: 720px) {
    .sc-price-summary { position: static; max-height: none; top: auto; }
    .sc-page-hero { min-height: 260px; padding: 32px 0; }
    .sc-variant-grid, .sc-inclusion-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .sc-variant-grid, .sc-inclusion-grid { grid-template-columns: 1fr; }
}
</style>
<?= $this->endSection() ?>