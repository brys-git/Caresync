<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-catalog.css') ?>">

<?php
$serviceName = (string) ($service['service_name'] ?? 'Balik Probinsya');
$serviceDesc = (string) ($service['description'] ?? '');
$basePrice = (float) ($service['base_price'] ?? 0);
$planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);

// Damayan constants
$DAMAYAN_BENEFIT = 14500;
$MIN_PACKAGE_FOR_BENEFIT = 20000;
?>

<div class="sc">
    <div class="sc-container">

        <!-- ====== Page Hero ====== -->
        <div class="sc-page-hero" style="background-image: url('<?= base_url('assets/images/product-2.png') ?>');">
            <div class="sc-page-hero__overlay"></div>
            <div class="sc-page-hero__content">
                <div class="sc-page-hero__eyebrow">
                    <i class="mdi mdi-truck-outline"></i>
                    Service Detail
                </div>
                <h1 class="sc-page-hero__title"><?= esc($serviceName) ?></h1>
                <p class="sc-page-hero__sub">Dignified transport of your loved one from Metro Manila or Batangas to Mindoro. Professional handling, compassionate care.</p>
            </div>
        </div>

        <!-- ====== Breadcrumb ====== -->
        <nav class="sc-breadcrumb" style="padding:16px 0 8px;" aria-label="Breadcrumb">
            <a href="<?= site_url('/client/service?tab=services') ?>" class="sc-breadcrumb__item"><i class="mdi mdi-arrow-left"></i> All Services</a>
            <span class="sc-breadcrumb__sep">/</span>
            <span class="sc-breadcrumb__item sc-breadcrumb__item--current"><?= esc($serviceName) ?></span>
        </nav>

        <!-- ====== Damayan Eligibility Callout ====== -->
        <?php if ($isDamayanMember): ?>
        <div class="sc-damayan-callout">
            <div class="sc-damayan-callout__icon"><i class="mdi mdi-shield-check-outline"></i></div>
            <div class="sc-damayan-callout__content">
                <div class="sc-damayan-callout__title">Damayan Member Benefit Active</div>
                <div class="sc-damayan-callout__text">You are a qualified Damayan member. When applying for packages over ₱20,000, you receive a <strong>₱14,500 credit</strong> and remaining contributions are waived.</div>
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
                    <img src="<?= base_url('assets/images/service-balik-probinsya.jpg') ?>" alt="<?= esc($serviceName) ?>">
                </div>

                <!-- Description -->
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-information-outline"></i>
                        <div>
                            <div class="sc-block__title">About This Service</div>
                            <div class="sc-block__sub">Dignified inter-island transport for your loved one</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <p><?= nl2br(esc($serviceDesc)) ?></p>
                        <p style="margin-top:12px;">Our Balik Probinsya program provides professional, compassionate transport services from Metro Manila or Batangas ports to Mindoro. We handle all logistics including documentation, casket preparation, and coordination with receiving funeral homes.</p>
                    </div>
                </div>

                <!-- Route Pricing Table -->
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-map-marker-radius-outline"></i>
                        <div>
                            <div class="sc-block__title">Route Pricing</div>
                            <div class="sc-block__sub">Fixed rates for origin-destination routes</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <div class="sc-route-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Route</th>
                                        <th class="sc-text-right">Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rates as $rate):
                                        $origin = esc((string) ($rate['origin'] ?? ''));
                                        $destination = esc((string) ($rate['destination'] ?? ''));
                                        $ratePrice = (float) ($rate['rate'] ?? 0);
                                        $displayName = $origin . ' → ' . $destination;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="sc-route__origin"><?= $displayName ?></div>
                                        </td>
                                        <td class="sc-text-right">
                                            <div class="sc-route__price">₱<?= number_format($ratePrice, 2) ?></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($rates)): ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center;color:var(--sc-ink-faint);padding:24px;">
                                            <i class="mdi mdi-information-outline" style="font-size:1.5rem;margin-bottom:8px;display:block;"></i>
                                            Route rates will be loaded from the database.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Damayan Benefit Explanation -->
                <div class="sc-block">
                    <div class="sc-block__head">
                        <i class="mdi mdi-currency-php"></i>
                        <div>
                            <div class="sc-block__title">Damayan Member Benefit</div>
                            <div class="sc-block__sub">How the benefit applies to this service</div>
                        </div>
                    </div>
                    <div class="sc-block__body">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
                            <div style="padding:16px;background:var(--sc-surface);border-radius:var(--sc-radius-md);border:1px solid var(--sc-border);">
                                <div style="font-weight:700;color:var(--sc-primary);font-size:1.25rem;">₱14,500</div>
                                <div style="color:var(--sc-ink-faint);font-size:0.82rem;">Benefit Credit</div>
                            </div>
                            <div style="padding:16px;background:var(--sc-surface);border-radius:var(--sc-radius-md);border:1px solid var(--sc-border);">
                                <div style="font-weight:700;color:var(--sc-success);font-size:1.25rem;">Qualified</div>
                                <div style="color:var(--sc-ink-faint);font-size:0.82rem;">Members Only</div>
                            </div>
                            <div style="padding:16px;background:var(--sc-surface);border-radius:var(--sc-radius-md);border:1px solid var(--sc-border);">
                                <div style="font-weight:700;color:var(--sc-primary);font-size:1.25rem;">Waived</div>
                                <div style="color:var(--sc-ink-faint);font-size:0.82rem;">Remaining Contributions</div>
                            </div>
                        </div>
                        <div style="margin-top:16px;padding:16px;background:var(--sc-primary-soft);border-radius:var(--sc-radius-md);border-left:4px solid var(--sc-primary);">
                            <div style="font-weight:600;color:var(--sc-primary);margin-bottom:8px;">How it works for Balik Probinsya:</div>
                            <ul style="margin:0;padding-left:20px;color:var(--sc-ink);font-size:0.88rem;line-height:1.8;">
                                <li>The Damayan benefit (₱14,500) applies to <strong>packages over ₱20,000</strong> (casket packages).</li>
                                <li>For the Balik Probinsya transport service, the benefit <strong>does not directly apply</strong> as this is a service, not a package.</li>
                                <li>However, if you combine this service with a qualifying casket package, the ₱14,500 credit applies to the package portion.</li>
                                <li>Qualified members also have <strong>remaining monthly contributions waived</strong> upon service application approval.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Sticky Price Summary & Apply -->
            <div class="sc-detail-sidebar">
                <div class="sc-price-summary" id="sc-price-summary">

                    <!-- Service Base Price -->
                    <div class="sc-price-summary__row">
                        <span class="sc-price-summary__label">Base Service Rate</span>
                        <span class="sc-price-summary__value" id="sc-base-price">₱<?= number_format($basePrice, 2) ?></span>
                    </div>

                    <!-- Route Selection -->
                    <div class="sc-price-summary__row sc-price-summary__row--select">
                        <label class="sc-price-summary__label" for="sc-route-select">Select Route</label>
                        <select id="sc-route-select" class="sc-select sc-price-summary__select" onchange="scUpdatePriceSummary()">
                            <option value="">Choose a route...</option>
                            <?php foreach ($rates as $rate):
                                $origin = esc((string) ($rate['origin'] ?? ''));
                                $destination = esc((string) ($rate['destination'] ?? ''));
                                $ratePrice = (float) ($rate['rate'] ?? 0);
                                $displayName = $origin . ' → ' . $destination;
                            ?>
                            <option value="<?= $ratePrice ?>" data-origin="<?= $origin ?>" data-destination="<?= $destination ?>">
                                <?= $displayName ?> — ₱<?= number_format($ratePrice, 2) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sc-price-summary__divider"></div>

                    <!-- Selected Route Price -->
                    <div class="sc-price-summary__row" id="sc-selected-route-row" style="display:none;">
                        <span class="sc-price-summary__label" id="sc-selected-route-label">Selected Route</span>
                        <span class="sc-price-summary__value" id="sc-selected-route-price">₱0.00</span>
                    </div>

                    <!-- Damayan Discount (for packages only, shown for info) -->
                    <?php if ($isDamayanMember): ?>
                    <div class="sc-price-summary__row sc-price-summary__row--discount" id="sc-damayan-row" style="display:none;">
                        <span class="sc-price-summary__label">
                            <i class="mdi mdi-shield-check-outline" style="margin-right:6px;color:var(--sc-success);"></i>
                            Damayan Benefit
                        </span>
                        <span class="sc-price-summary__value sc-price-summary__value--discount" id="sc-damayan-discount">-₱0.00</span>
                    </div>
                    <?php endif; ?>

                    <div class="sc-price-summary__divider sc-price-summary__divider--thick"></div>

                    <!-- Total -->
                    <div class="sc-price-summary__row sc-price-summary__row--total">
                        <span class="sc-price-summary__label">Total</span>
                        <span class="sc-price-summary__value" id="sc-total-price">₱<?= number_format($basePrice, 2) ?></span>
                    </div>

                    <!-- Apply Button -->
                    <div class="sc-price-summary__actions">
                        <?php if ($can_apply): ?>
                            <a href="<?= site_url('/client/apply-balik-probinsya/' . (int) ($service['service_list_id'] ?? 0)) ?>" class="sc-btn sc-btn--primary sc-btn--lg sc-btn--block" id="sc-apply-btn">
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
                        <div class="sc-trust-item"><i class="mdi mdi-truck-outline"></i> Professional Transport</div>
                        <div class="sc-trust-item"><i class="mdi mdi-clock-outline"></i> 24/7 Coordination</div>
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

    const routeSelect = document.getElementById('sc-route-select');
    const selectedRouteRow = document.getElementById('sc-selected-route-row');
    const selectedRouteLabel = document.getElementById('sc-selected-route-label');
    const selectedRoutePrice = document.getElementById('sc-selected-route-price');
    const damayanRow = document.getElementById('sc-damayan-row');
    const damayanDiscount = document.getElementById('sc-damayan-discount');
    const totalPrice = document.getElementById('sc-total-price');

    let selectedRoutePriceValue = 0;

    function formatCurrency(amount) {
        return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function scUpdatePriceSummary() {
        const selectedOption = routeSelect.options[routeSelect.selectedIndex];
        selectedRoutePriceValue = parseFloat(selectedOption.value) || 0;

        if (selectedRoutePriceValue > 0) {
            selectedRouteRow.style.display = 'flex';
            selectedRouteLabel.textContent = selectedOption.textContent.split('—')[0].trim();
            selectedRoutePrice.textContent = formatCurrency(selectedRoutePriceValue);
        } else {
            selectedRouteRow.style.display = 'none';
        }

        // Damayan benefit only applies to packages > 20K, not services
        // But we show it for informational purposes if user is Damayan member
        if (isDamayanMember && damayanRow) {
            // For Balik Probinsya (service), the benefit doesn't directly apply
            // This is just informational - the actual benefit applies to packages
            damayanRow.style.display = 'none'; // Hide for services
        }

        // Total = base price + selected route
        const total = basePrice + selectedRoutePriceValue;
        totalPrice.textContent = formatCurrency(total);
    }

    // Initialize
    if (routeSelect) {
        routeSelect.addEventListener('change', scUpdatePriceSummary);
    }

    // Expose for global access
    window.scUpdatePriceSummary = scUpdatePriceSummary;

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
.sc-page-hero__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(30, 58, 95, 0.92) 0%, rgba(30, 58, 95, 0.78) 100%);
    z-index: 0;
}
.sc-page-hero__content { position: relative; z-index: 1; max-width: 800px; margin: 0 auto; text-align: center; padding: 0 24px; }
.sc-page-hero__eyebrow { display: inline-flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.9); font-size: 0.88rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 12px; }
.sc-page-hero__title { color: #fff; font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; line-height: 1.15; margin: 0 0 12px; }
.sc-page-hero__sub { color: rgba(255,255,255,0.88); font-size: clamp(0.95rem, 2vw, 1.1rem); line-height: 1.6; margin: 0; max-width: 640px; margin-left: auto; margin-right: auto; }

.sc-breadcrumb { font-size: 0.82rem; color: var(--sc-ink-faint); }
.sc-breadcrumb__item { color: var(--sc-ink-faint); text-decoration: none; transition: color var(--sc-fast); }
.sc-breadcrumb__item:hover { color: var(--sc-primary); }
.sc-breadcrumb__sep { margin: 0 8px; }
.sc-breadcrumb__item--current { color: var(--sc-ink); font-weight: 600; pointer-events: none; }

.sc-damayan-callout {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-radius: var(--sc-radius-lg);
    border: 1px solid var(--sc-primary);
    background: linear-gradient(135deg, var(--sc-primary-soft) 0%, rgba(30,58,95,0.04) 100%);
    margin-bottom: 24px;
}
.sc-damayan-callout__icon { font-size: 1.75rem; color: var(--sc-primary); flex-shrink: 0; }
.sc-damayan-callout__content { flex: 1; min-width: 0; }
.sc-damayan-callout__title { font-weight: 700; color: var(--sc-ink); font-size: 1rem; margin-bottom: 4px; }
.sc-damayan-callout__text { color: var(--sc-ink-dim); font-size: 0.86rem; line-height: 1.55; }
.sc-damayan-callout__text a { text-decoration: underline; }

.sc-detail-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
@media (min-width: 980px) { .sc-detail-grid { grid-template-columns: 1fr 360px; align-items: start; } }

.sc-detail-hero { border-radius: var(--sc-radius-lg); overflow: hidden; background: var(--sc-surface); }
.sc-detail-hero img { width: 100%; height: auto; display: block; aspect-ratio: 16/9; object-fit: cover; }

.sc-block { background: var(--sc-surface); border: 1px solid var(--sc-border); border-radius: var(--sc-radius-lg); padding: 24px; margin-bottom: 20px; }
.sc-block__head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
.sc-block__head i { font-size: 1.35rem; color: var(--sc-primary); margin-top: 2px; flex-shrink: 0; }
.sc-block__title { font-weight: 700; color: var(--sc-ink); font-size: 1.05rem; }
.sc-block__sub { color: var(--sc-ink-faint); font-size: 0.82rem; margin-top: 2px; }
.sc-block__body { color: var(--sc-ink-dim); line-height: 1.7; font-size: 0.9rem; }
.sc-block__body p { margin: 0 0 12px; }
.sc-block__body p:last-child { margin-bottom: 0; }

.sc-route-table { overflow-x: auto; }
.sc-route-table table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.sc-route-table th, .sc-route-table td { padding: 14px 16px; border-bottom: 1px solid var(--sc-border); text-align: left; }
.sc-route-table th { font-weight: 700; color: var(--sc-ink-faint); font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.06em; background: var(--sc-bg); }
.sc-route-table tbody tr:last-child td { border-bottom: none; }
.sc-route-table tbody tr:hover td { background: var(--sc-primary-soft); }
.sc-route__origin { font-weight: 600; color: var(--sc-ink); }
.sc-route__price { font-weight: 700; color: var(--sc-primary); font-size: 1rem; }
.sc-text-right { text-align: right; }

.sc-price-summary {
    position: sticky;
    top: 100px;
    background: var(--sc-surface);
    border: 1px solid var(--sc-border);
    border-radius: var(--sc-radius-lg);
    padding: 24px;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}
.sc-price-summary__row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 0.92rem; }
.sc-price-summary__label { color: var(--sc-ink-dim); font-weight: 500; }
.sc-price-summary__value { color: var(--sc-ink); font-weight: 700; font-size: 1rem; }
.sc-price-summary__value--discount { color: var(--sc-success); }
.sc-price-summary__row--total { font-size: 1.15rem; padding-top: 16px; margin-top: 8px; }
.sc-price-summary__row--total .sc-price-summary__label { font-weight: 700; color: var(--sc-ink); }
.sc-price-summary__row--total .sc-price-summary__value { font-size: 1.35rem; color: var(--sc-primary); font-weight: 800; }
.sc-price-summary__divider { height: 1px; background: var(--sc-border); margin: 8px 0; }
.sc-price-summary__divider--thick { height: 2px; background: linear-gradient(90deg, transparent, var(--sc-border), transparent); margin: 16px 0; }
.sc-price-summary__row--select { flex-direction: column; align-items: stretch; gap: 8px; }
.sc-price-summary__select { width: 100%; }
.sc-price-summary__actions { margin-top: 16px; }
.sc-btn--block { width: 100%; justify-content: center; }
.sc-price-summary__trust { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--sc-border); }
.sc-trust-item { display: inline-flex; align-items: center; gap: 6px; font-size: 0.76rem; color: var(--sc-ink-faint); background: var(--sc-bg); padding: 6px 12px; border-radius: var(--sc-radius-full); }
.sc-trust-item i { font-size: 0.85rem; color: var(--sc-primary); }

@media (max-width: 720px) {
    .sc-price-summary { position: static; max-height: none; top: auto; }
    .sc-page-hero { min-height: 260px; padding: 32px 0; }
}
</style>
<?= $this->endSection() ?>