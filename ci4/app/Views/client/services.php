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

<style>
    .status-badge-eligible { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .status-badge-ineligible { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .svc-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .svc-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.10);
    }
    .svc-thumb {
        height: 190px;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #e5e7eb;
        position: relative;
    }
    .svc-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .svc-thumb .placeholder-icon { font-size: 3rem; color: #94a3b8; }
    .badge-claim { background-color: #b45309; color: #fff; }
    .badge-entitlement { background-color: #e2e8f0; color: #334155; }
    .badge-available { background-color: #0f766e; color: #fff; }
    .badge-pill { font-weight: 600; letter-spacing: .03em; padding: .4em .75em; border-radius: 999px; font-size: .72rem; text-transform: uppercase; }
    .price-tag { font-weight: 700; color: #1e293b; }
    .btn-claim { background-color: #b45309; border-color: #b45309; color: #fff; font-weight: 600; }
    .btn-claim:hover { background-color: #92400e; border-color: #92400e; color: #fff; }
    .btn-claim:disabled { opacity: .55; }
    .membership-alert-box {
        padding: 1.25rem;
        border-radius: 12px;
        margin-bottom: 2rem;
    }
    .membership-alert-eligible {
        background-color: #f0fdf4;
        border: 2px solid #86efac;
        color: #166534;
    }
    .membership-alert-ineligible {
        background-color: #fef2f2;
        border: 2px solid #fca5a5;
        color: #991b1b;
    }
    .membership-alert-pending {
        background-color: #fffbeb;
        border: 2px solid #fcd34d;
        color: #92400e;
    }
    .grid-gap-3 { gap: 1.5rem; }
    .route-row { display: flex; justify-content: space-between; font-size: .85rem; padding: .25rem 0; border-bottom: 1px dashed #e2e8f0; }
    .route-row:last-child { border-bottom: none; }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-1">
            <i class="bi bi-heart-handshake me-2"></i> Services & Packages
        </h1>
        <p class="text-muted mb-0">Browse available funeral services and casket packages.</p>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i> <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Membership Status Alert -->
    <div class="membership-alert-box <?= $isEligible ? 'membership-alert-eligible' : ($state === 'active' ? 'membership-alert-pending' : 'membership-alert-ineligible') ?>">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <div style="font-size: 2rem;">
                    <?= $isEligible ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-exclamation-circle-fill"></i>' ?>
                </div>
            </div>
            <div class="col">
                <div class="fw-bold mb-1">Eligibility Status</div>
                <div class="row g-3 small">
                    <div class="col-md-4">
                        <strong>Membership State:</strong><br>
                        <span class="badge <?= $membershipState === 'active' ? 'bg-success' : 'bg-warning' ?>">
                            <?= ucfirst($membershipState) ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Months Paid:</strong><br>
                        <span><?= $monthsPaid ?> / 2 months minimum</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Can Apply:</strong><br>
                        <span class="fw-bold" style="color: <?= $isEligible ? '#059669' : '#dc2626' ?>;">
                            <?= $isEligible ? '✓ YES' : '✗ NO' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php if (! $isEligible): ?>
            <div class="mt-3 p-2 rounded" style="background-color: rgba(0,0,0,0.08);">
                <small>
                    <strong><i class="bi bi-info-circle me-1"></i>Requirement:</strong>
                    You must complete at least 2 months of contributions to apply for services.
                    Currently: <?= $monthsPaid ?> month<?= $monthsPaid === 1 ? '' : 's' ?> paid.
                </small>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'services' ? 'active' : '' ?>"
               href="<?= site_url('/client/service?tab=services') ?>" role="tab">
                <i class="bi bi-truck me-2"></i> Services
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'packages' ? 'active' : '' ?>"
               href="<?= site_url('/client/service?tab=packages') ?>" role="tab">
                <i class="bi bi-box2 me-2"></i> Packages
            </a>
        </li>
    </ul>

    <!-- Services Tab -->
    <?php if ($activeTab === 'services'): ?>
        <div class="row grid-gap-3">
            <?php foreach (($services ?? []) as $service): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="svc-card h-100 d-flex flex-column">
                        <div class="svc-thumb">
                            <?php if (! empty($service['image_path'])): ?>
                                <img src="<?= esc((string) $service['image_path']) ?>" alt="<?= esc((string) ($service['service_name'] ?? 'Service')) ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'bi bi-truck placeholder-icon\'></i>';">
                            <?php else: ?>
                                <i class="bi bi-truck placeholder-icon"></i>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 d-flex flex-column h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0"><?= esc((string) ($service['service_name'] ?? 'Service')) ?></h5>
                                <span class="badge-pill badge-available"><i class="bi bi-check2 me-1"></i>AVAILABLE</span>
                            </div>
                            <p class="text-muted small mb-3">
                                <?= esc((string) ($service['description'] ?? 'No description available.')) ?>
                            </p>

                            <?php if (! empty($service['routes'])): ?>
                                <div class="mb-3 border rounded p-2 bg-light">
                                    <?php foreach ($service['routes'] as $route): ?>
                                        <div class="route-row">
                                            <span><?= esc((string) $route['route_name']) ?></span>
                                            <span class="price-tag">₱<?= number_format((float) $route['price'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <span class="price-tag">₱<?= number_format((float) ($service['base_price'] ?? 0), 2) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-auto">
                                <a class="btn btn-outline-secondary btn-sm flex-grow-1"
                                   href="<?= site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                                <?php if ($isEligible): ?>
                                    <a class="btn btn-primary btn-sm"
                                       href="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>"
                                       title="Apply for this service">
                                        Apply
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            data-bs-toggle="popover"
                                            data-bs-trigger="hover"
                                            title="Not Eligible"
                                            data-bs-content="You need 2 months of contributions to apply">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($services)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-info-circle" style="font-size: 2.5rem;"></i>
                        <p class="mt-2 mb-0">No services available at the moment.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Packages Tab -->
    <?php if ($activeTab === 'packages'): ?>
        <div class="row grid-gap-3">
            <?php foreach ($packages ?? [] as $package): ?>
                <?php
                    $isEntitlement = (int) ($package['is_damayan_entitlement'] ?? 0) === 1;
                    $canClaim = (bool) ($package['can_claim'] ?? false);
                    $badgeLabel = (string) ($package['badge_label'] ?? 'AVAILABLE');
                    $badgeClassMap = ['success' => 'badge-claim', 'secondary' => 'badge-entitlement', 'info' => 'badge-available'];
                    $badgeCss = $badgeClassMap[$package['badge_class'] ?? 'info'] ?? 'badge-available';
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="svc-card h-100 d-flex flex-column <?= $isEntitlement ? 'border-warning' : '' ?>">
                        <div class="svc-thumb">
                            <?php if (! empty($package['image_path'])): ?>
                                <img src="<?= esc((string) $package['image_path']) ?>" alt="<?= esc((string) ($package['package_name'] ?? 'Package')) ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'bi bi-box2-fill placeholder-icon\'></i>';">
                            <?php else: ?>
                                <i class="bi bi-box2-fill placeholder-icon"></i>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 d-flex flex-column h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0"><?= esc((string) ($package['package_name'] ?? 'Package')) ?></h5>
                                <span class="badge-pill <?= $badgeCss ?>"><?= esc($badgeLabel) ?></span>
                            </div>
                            <p class="text-muted small mb-3 flex-grow-1">
                                <?= esc((string) ($package['description'] ?? 'No description available.')) ?>
                            </p>

                            <div class="mb-3">
                                <span class="price-tag">₱<?= number_format((float) ($package['base_price'] ?? 0), 2) ?></span>
                            </div>

                            <?php if ($isEntitlement && ! $canClaim && ! empty($package['claim_locked_reason'])): ?>
                                <div class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i><?= esc((string) $package['claim_locked_reason']) ?></div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 mt-auto">
                                <a class="btn btn-outline-secondary btn-sm flex-grow-1"
                                   href="<?= site_url('/client/package/' . (int) ($package['package_id'] ?? 0)) ?>">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                                <?php if ($isEntitlement): ?>
                                    <?php if ($canClaim): ?>
                                        <a class="btn btn-claim btn-sm"
                                           href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>"
                                           title="Claim your Damayan entitlement">
                                            <i class="bi bi-gift me-1"></i> CLAIM
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-claim btn-sm" disabled>
                                            <i class="bi bi-lock me-1"></i> CLAIM
                                        </button>
                                    <?php endif; ?>
                                <?php elseif ($isEligible): ?>
                                    <a class="btn btn-primary btn-sm"
                                       href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>"
                                       title="Avail this package">
                                        Avail
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            data-bs-toggle="popover"
                                            data-bs-trigger="hover"
                                            title="Not Eligible"
                                            data-bs-content="You need 2 months of contributions to apply">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($packages)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-info-circle" style="font-size: 2.5rem;"></i>
                        <p class="mt-2 mb-0">No packages available at the moment.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
});
</script>

<?= $this->endSection() ?>
