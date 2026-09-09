<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'new');
$canApply = (bool) ($can_apply ?? false);
$isEntitlement = (bool) ($is_entitlement ?? false);
$entitlement = $entitlement ?? null;
$benefitCredit = (float) ($benefit_credit ?? 0);
$attirePrice = (float) ($attire_price ?? 0);
$basePrice = (float) ($package['base_price'] ?? 0);
$eligibleToClaim = $isEntitlement && (bool) ($entitlement['eligible'] ?? false);
?>
<style>
    .pkg-hero { border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb; background: #fff; }
    .pkg-hero-image { height: 280px; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); display: flex; align-items: center; justify-content: center; }
    .pkg-hero-image img { width: 100%; height: 100%; object-fit: cover; }
    .pkg-hero-image .placeholder-icon { font-size: 4.5rem; color: #94a3b8; }
    .badge-pill { font-weight: 600; letter-spacing: .03em; padding: .45em .85em; border-radius: 999px; font-size: .75rem; text-transform: uppercase; display: inline-block; }
    .badge-entitlement-eligible { background-color: #dcfce7; color: #166534; }
    .badge-entitlement-locked { background-color: #f1f5f9; color: #475569; }
    .badge-available { background-color: #e0f2fe; color: #075985; }
    .entitlement-panel { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 2px solid #fcd34d; border-radius: 14px; padding: 1.5rem; }
    .btn-claim-big { background-color: #b45309; border-color: #b45309; color: #fff; font-weight: 700; padding: .75rem 1.5rem; letter-spacing: .03em; }
    .btn-claim-big:hover { background-color: #92400e; border-color: #92400e; color: #fff; }
    .btn-claim-big:disabled { opacity: .5; }
    .btn-avail-big { background-color: #0f766e; border-color: #0f766e; color: #fff; font-weight: 700; padding: .75rem 1.5rem; letter-spacing: .03em; }
    .btn-avail-big:hover { background-color: #115e59; border-color: #115e59; color: #fff; }
    .benefit-line { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px dashed #e2e8f0; }
    .benefit-line:last-child { border-bottom: none; }
    .attire-box { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem 1.25rem; background: #f8fafc; }
</style>

<div class="container-fluid py-4" style="max-width: 960px;">
    <a class="text-decoration-none text-muted mb-3 d-inline-block" href="<?= site_url('/client/service?tab=packages') ?>">
        <i class="bi bi-arrow-left me-1"></i> Back to Packages
    </a>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="pkg-hero mb-4">
        <div class="pkg-hero-image">
            <?php if (! empty($package['image_path'])): ?>
                <img src="<?= esc((string) $package['image_path']) ?>" alt="<?= esc((string) ($package['package_name'] ?? 'Package')) ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'bi bi-box2-fill placeholder-icon\'></i>';">
            <?php else: ?>
                <i class="bi bi-box2-fill placeholder-icon"></i>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <h2 class="h4 mb-0"><?= esc((string) ($package['package_name'] ?? '-')) ?></h2>
                <?php if ($isEntitlement): ?>
                    <span class="badge-pill <?= $eligibleToClaim ? 'badge-entitlement-eligible' : 'badge-entitlement-locked' ?>">
                        <i class="bi bi-gift me-1"></i> <?= $eligibleToClaim ? 'CLAIM' : 'YOUR ENTITLEMENT' ?>
                    </span>
                <?php else: ?>
                    <span class="badge-pill badge-available"><i class="bi bi-check2 me-1"></i>AVAILABLE</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-3"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
            <div class="h3 mb-0 text-dark">₱<?= number_format($basePrice, 2) ?></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-list-check me-1"></i> What's Included</div>
        <div class="card-body">
            <?php $this->setData(['inclusions' => $inclusions ?? []]); ?>
            <?= $this->include('client/partials/inclusions_list') ?>
        </div>
    </div>

    <?php if ($isEntitlement): ?>
        <div class="entitlement-panel mb-4">
            <h5 class="mb-3"><i class="bi bi-award me-2"></i>Your Damayan Entitlement</h5>
            <?php if ($eligibleToClaim): ?>
                <p class="mb-3"><strong>Status: <span class="text-success">ELIGIBLE TO CLAIM</span></strong><br>
                    <span class="text-muted small">Your ₱14,500 contribution cycle is fully paid - this Regular Wood Casket is covered at no cost.</span>
                </p>
            <?php else: ?>
                <p class="mb-3"><strong>Status: <span class="text-secondary">NOT YET ELIGIBLE</span></strong><br>
                    <span class="text-muted small">You must fully pay the ₱14,500 contribution target (and remain in good standing) before this entitlement can be claimed again.</span>
                </p>
            <?php endif; ?>

            <?php if ($canApply && $eligibleToClaim): ?>
                <a class="btn btn-claim-big" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">
                    [ CLAIM REGULAR CASKET ]
                </a>
            <?php elseif ($canApply): ?>
                <button type="button" class="btn btn-claim-big" disabled>[ CLAIM REGULAR CASKET ]</button>
            <?php else: ?>
                <div class="alert alert-info mb-0">You must register as an active Plan Holder to claim this entitlement.</div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-cash-coin me-1"></i> Available Package</div>
            <div class="card-body">
                <?php if ($canApply && $benefitCredit > 0): ?>
                    <div class="benefit-line">
                        <span>Package Price</span>
                        <span>₱<?= number_format($basePrice, 2) ?></span>
                    </div>
                    <div class="benefit-line text-success">
                        <span><i class="bi bi-award me-1"></i>Damayan Benefit Credit</span>
                        <span>- ₱<?= number_format($benefitCredit, 2) ?></span>
                    </div>
                    <div class="benefit-line fw-bold">
                        <span>Amount Due</span>
                        <span>₱<?= number_format(max(0, $basePrice - $benefitCredit), 2) ?></span>
                    </div>
                    <p class="text-muted small mt-2 mb-3">As a Damayan Plan Holder, your remaining unpaid contribution is waived against this package - it is not added on top of the amount due above.</p>
                <?php else: ?>
                    <p class="text-muted small mb-3">Full package price applies. Damayan Plan Holders in good standing receive a ₱14,500 benefit credit toward this package.</p>
                <?php endif; ?>

                <?php if ($canApply): ?>
                    <a class="btn btn-avail-big" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">
                        [ AVAIL PACKAGE ]
                    </a>
                <?php elseif ($state === 'pending'): ?>
                    <div class="alert alert-warning mb-0">Approval required before requesting services.</div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">You must register as a Plan Holder to apply.<br>
                        <a class="btn btn-primary btn-sm mt-2" href="<?= site_url('/plan-info') ?>">Register Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="attire-box">
        <span class="badge-pill" style="background-color:#ede9fe;color:#5b21b6;">OPTIONAL ADD-ON</span>
        <div class="mt-2 fw-semibold">Burial Attire Package - ₱<?= number_format($attirePrice, 2) ?></div>
        <p class="text-muted small mb-0">A complete burial attire set for the deceased. Not included by default - you can select it on the application form.</p>
    </div>
</div>
<?= $this->endSection() ?>
