<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'new');
$canApply = (bool) ($can_apply ?? false);
$isEntitlement = (bool) ($is_entitlement ?? false);
$entitlement = $entitlement ?? null;
$planLabel = (string) ($plan_label ?? 'plan');
$formQuote = is_array($form_quote ?? null) ? $form_quote : null;
$quotePlain = $formQuote['plain'] ?? [];
$packageName = (string) ($package['package_name'] ?? 'package');
$attirePrice = (float) ($attire_price ?? 0);
$basePrice = (float) ($package['base_price'] ?? 0);
$eligibleToClaim = $isEntitlement && (bool) ($entitlement['eligible'] ?? false);
?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>
<div class="cs-narrow">
    <a class="btn btn-ghost mb-3" href="<?= site_url('/client/service?tab=packages') ?>">
        <i class="ti ti-arrow-left me-1"></i> Back to packages
    </a>

    <div class="cs-hero mb-3">
        <div class="cs-hero__image">
            <img src="<?= esc(cs_image_url($package['image_path'] ?? null)) ?>" alt="<?= esc((string) ($package['package_name'] ?? 'Package')) ?>" onerror="this.onerror=null;this.src='<?= esc(base_url('assets/images/no-image.png')) ?>'">
        </div>
        <div class="cs-hero__body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div class="fs-4 fw-semibold"><?= cs_money($basePrice) ?></div>
                <?php if ($isEntitlement): ?>
                    <?= $eligibleToClaim ? cs_status('approved', 'Claim') : cs_status('inactive', 'Your entitlement') ?>
                <?php else: ?>
                    <?= cs_status('available', 'Available') ?>
                <?php endif; ?>
            </div>
            <p class="cs-muted mb-0"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
        </div>
    </div>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">What's included</h2></div>
        <div class="cs-panel__body">
            <?php $this->setData(['inclusions' => $inclusions ?? []]); ?>
            <?= $this->include('client/partials/inclusions_list') ?>
        </div>
    </section>

    <?php if ($isEntitlement): ?>
        <section class="cs-panel cs-panel--brass mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Included in your <?= esc($planLabel) ?> plan</h2></div>
            <div class="cs-panel__body">
                <?php if ($eligibleToClaim): ?>
                    <p class="mb-3"><?= cs_status('approved', 'Eligible to claim') ?><br>
                        <span class="cs-muted small">You're eligible to claim <?= esc($packageName) ?> at no cost.</span>
                    </p>
                <?php else: ?>
                    <p class="mb-3"><?= cs_status('inactive', 'Not yet eligible') ?><br>
                        <span class="cs-muted small"><?= esc((string) ($entitlement['locked_reason'] ?? 'This entitlement is not available to claim yet.')) ?></span>
                    </p>
                <?php endif; ?>

                <?php if ($canApply && $eligibleToClaim): ?>
                    <a class="btn btn-primary" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">Claim <?= esc($packageName) ?></a>
                <?php elseif ($canApply): ?>
                    <button type="button" class="btn btn-primary" disabled>Claim <?= esc($packageName) ?></button>
                <?php else: ?>
                    <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'class' => 'mb-0', 'message' => (string) ($entitlement['locked_reason'] ?? 'You must register as an active Plan Holder to claim this entitlement.')]) ?>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Available package</h2></div>
            <div class="cs-panel__body">
                <?php if ($canApply && $quotePlain !== []): ?>
                    <?= view('partials/quote_summary', ['q' => $quotePlain]) ?>
                <?php else: ?>
                    <div class="cs-routerow"><span>Package price</span><span><?= cs_money($basePrice) ?></span></div>
                    <p class="cs-muted small mt-2 mb-3">The exact amount for your plan - the down payment and the monthly instalments - is shown once you are eligible to request this package.</p>
                <?php endif; ?>

                <?php if ($canApply): ?>
                    <a class="btn btn-primary" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">Avail package</a>
                <?php elseif ($state === 'pending'): ?>
                    <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'class' => 'mb-0', 'message' => 'Approval required before requesting services.']) ?>
                <?php else: ?>
                    <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'message' => 'You must register as a Plan Holder to apply.']) ?>
                    <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register now</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="cs-panel">
        <div class="cs-panel__body">
            <?= cs_status('scheduled', 'Optional add-on') ?>
            <div class="mt-2 fw-semibold">Burial attire package — <?= cs_money($attirePrice) ?></div>
            <p class="cs-muted small mb-0">A complete burial attire set for the deceased. Not included by default - you can select it on the application form.</p>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
