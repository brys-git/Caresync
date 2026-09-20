<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'unregistered');
$canApply = (bool) ($can_apply ?? false);
$membership = $membership ?? [];
$monthsPaid = (int) ($membership['months_paid'] ?? 0);
$membershipState = strtolower((string) ($membership['membership_state'] ?? 'inactive'));
$isEligible = $canApply;
$activeTab = (string) ($active_tab ?? 'services');
?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Eligibility status</h2>
        <?= cs_status($isEligible ? 'approved' : 'pending', $isEligible ? 'Can apply' : 'Not yet eligible') ?>
    </div>
    <div class="cs-panel__body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="cs-muted">Membership state</div>
                <?= cs_status($membershipState) ?>
            </div>
            <div class="col-md-4">
                <div class="cs-muted">Months paid</div>
                <strong><?= esc((string) $monthsPaid) ?> / 2 months minimum</strong>
            </div>
            <div class="col-md-4">
                <div class="cs-muted">Can apply</div>
                <strong><?= $isEligible ? 'Yes' : 'No' ?></strong>
            </div>
        </div>
        <?php if (! $isEligible): ?>
            <div class="mt-3">
                <?= view('components/alert', [
                    'type' => 'warning',
                    'dismissible' => false,
                    'title' => 'Requirement:',
                    'message' => 'You must complete at least 2 months of contributions to apply for services. Currently: ' . $monthsPaid . ' month' . ($monthsPaid === 1 ? '' : 's') . ' paid.',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'services' ? 'active' : '' ?>" href="<?= site_url('/client/service?tab=services') ?>" role="tab">
            <i class="ti ti-truck me-1"></i> Services
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'packages' ? 'active' : '' ?>" href="<?= site_url('/client/service?tab=packages') ?>" role="tab">
            <i class="ti ti-package me-1"></i> Packages
        </a>
    </li>
</ul>

<?php if ($activeTab === 'services'): ?>
    <?php if (empty($services)): ?>
        <?= view('components/empty_state', ['icon' => 'ti-truck', 'title' => 'No services available at the moment']) ?>
    <?php else: ?>
        <div class="cs-catalog">
            <?php foreach ($services as $service): ?>
                <div class="cs-card">
                    <div class="cs-card__thumb">
                        <?php if (! empty($service['image_path'])): ?>
                            <img src="<?= esc(base_url((string) $service['image_path'])) ?>" alt="<?= esc((string) ($service['service_name'] ?? 'Service')) ?>">
                        <?php else: ?>
                            <i class="ti ti-truck" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div class="cs-card__body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h2 class="cs-card__title"><?= esc((string) ($service['service_name'] ?? 'Service')) ?></h2>
                            <?= cs_status('available', 'Available') ?>
                        </div>
                        <p class="cs-muted small mb-3"><?= esc((string) ($service['description'] ?? 'No description available.')) ?></p>

                        <?php if (! empty($service['routes'])): ?>
                            <div class="mb-3">
                                <?php foreach ($service['routes'] as $route): ?>
                                    <div class="cs-routerow">
                                        <span><?= esc((string) $route['route_name']) ?></span>
                                        <?= cs_money($route['price']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="mb-3"><?= cs_money($service['base_price'] ?? 0) ?></div>
                        <?php endif; ?>

                        <div class="cs-card__actions">
                            <a class="btn btn-outline-primary" href="<?= site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)) ?>">View details</a>
                            <?php if ($isEligible): ?>
                                <a class="btn btn-primary" href="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">Apply</a>
                            <?php else: ?>
                                <button type="button" class="btn btn-ghost" data-bs-toggle="popover" data-bs-trigger="hover" title="Not eligible" data-bs-content="You need 2 months of contributions to apply" aria-label="Locked: needs 2 months of contributions">
                                    <i class="ti ti-lock"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($activeTab === 'packages'): ?>
    <?php if (empty($packages)): ?>
        <?= view('components/empty_state', ['icon' => 'ti-package', 'title' => 'No packages available at the moment']) ?>
    <?php else: ?>
        <div class="cs-catalog">
            <?php foreach ($packages as $package): ?>
                <?php
                $isEntitlement = (int) ($package['is_damayan_entitlement'] ?? 0) === 1;
                $canClaim = (bool) ($package['can_claim'] ?? false);
                $badgeLabel = (string) ($package['badge_label'] ?? 'AVAILABLE');
                $badgeToneMap = ['success' => 'approved', 'secondary' => 'inactive', 'info' => 'scheduled'];
                $badgeTone = $badgeToneMap[$package['badge_class'] ?? 'info'] ?? 'scheduled';
                ?>
                <div class="cs-card <?= $isEntitlement ? 'cs-card--featured' : '' ?>">
                    <div class="cs-card__thumb">
                        <?php if (! empty($package['image_path'])): ?>
                            <img src="<?= esc(base_url((string) $package['image_path'])) ?>" alt="<?= esc((string) ($package['package_name'] ?? 'Package')) ?>">
                        <?php else: ?>
                            <i class="ti ti-package" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div class="cs-card__body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h2 class="cs-card__title"><?= esc((string) ($package['package_name'] ?? 'Package')) ?></h2>
                            <?= cs_status($badgeTone, $badgeLabel) ?>
                        </div>
                        <p class="cs-muted small mb-3 flex-grow-1"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
                        <div class="mb-3"><?= cs_money($package['base_price'] ?? 0) ?></div>

                        <?php if ($isEntitlement && ! $canClaim && ! empty($package['claim_locked_reason'])): ?>
                            <div class="cs-muted small mb-2"><i class="ti ti-info-circle me-1"></i><?= esc((string) $package['claim_locked_reason']) ?></div>
                        <?php endif; ?>

                        <div class="cs-card__actions">
                            <a class="btn btn-outline-primary" href="<?= site_url('/client/package/' . (int) ($package['package_id'] ?? 0)) ?>">View details</a>
                            <?php if ($isEntitlement): ?>
                                <?php if ($canClaim): ?>
                                    <a class="btn btn-primary" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>" title="Claim your Damayan entitlement">
                                        <i class="ti ti-gift me-1"></i> Claim
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary" disabled><i class="ti ti-lock me-1"></i> Claim</button>
                                <?php endif; ?>
                            <?php elseif ($isEligible): ?>
                                <a class="btn btn-primary" href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>" title="Avail this package">Avail</a>
                            <?php else: ?>
                                <button type="button" class="btn btn-ghost" data-bs-toggle="popover" data-bs-trigger="hover" title="Not eligible" data-bs-content="You need 2 months of contributions to apply" aria-label="Locked: needs 2 months of contributions">
                                    <i class="ti ti-lock"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
        new bootstrap.Popover(el);
    });
});
</script>
<?= $this->endSection() ?>
