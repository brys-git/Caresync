<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'unregistered');
$canApply = (bool) ($can_apply ?? false);
$membership = $membership ?? [];
$monthsPaid = (int) ($membership['months_paid'] ?? 0);
$membershipState = strtolower((string) ($membership['membership_state'] ?? 'inactive'));
$isEligible = $canApply && $monthsPaid >= 2;
$activeTab = (string) ($active_tab ?? 'services');
?>

<style>
    .status-badge-eligible { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .status-badge-ineligible { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .service-card { 
        border: none; 
        border-radius: 12px; 
        overflow: hidden; 
        background: #fff; 
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .service-card:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .service-thumb { 
        height: 200px; 
        background: linear-gradient(135deg, rgba(37,99,235,0.12), rgba(20,184,166,0.12));
        display: flex;
        align-items: center;
        justify-content: center;
    }
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
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3 mb-1">
            <i class="bi bi-heart-handshake me-2"></i> Services & Packages
        </h1>
        <p class="text-muted mb-0">Browse available funeral and membership services</p>
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

    <!-- Membership Status Alert (CRITICAL) -->
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
                <i class="bi bi-heart me-2"></i> Funeral Services
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
                    <div class="service-card h-100 d-flex flex-column">
                        <!-- Service Thumbnail -->
                        <div class="service-thumb">
                            <div class="text-center">
                                <i class="bi bi-heart-fill" style="font-size: 3rem; color: #e74c3c; opacity: 0.3;"></i>
                            </div>
                        </div>

                        <!-- Service Content -->
                        <div class="p-4 d-flex flex-column h-100">
                            <h5 class="mb-2">
                                <?= esc((string) ($service['service_name'] ?? 'Service')) ?>
                            </h5>
                            <p class="text-muted small mb-3 flex-grow-1">
                                <?= esc(substr((string) ($service['description'] ?? 'No description available.'), 0, 100)) ?>...
                            </p>

                            <!-- Price Badge -->
                            <div class="mb-3">
                                <span class="badge bg-primary">
                                    ₱<?= number_format((float) ($service['base_price'] ?? 0), 2) ?>
                                </span>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <small class="text-muted">Status:</small><br>
                                <span class="badge bg-success">Available</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 mt-auto">
                                <a class="btn btn-outline-primary btn-sm flex-grow-1" 
                                   href="<?= site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                                <?php if ($isEligible): ?>
                                    <a class="btn btn-primary btn-sm" 
                                       href="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>"
                                       title="Apply for this service">
                                        <i class="bi bi-arrow-right"></i>
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
                <div class="col-md-6 col-xl-4">
                    <div class="service-card h-100 d-flex flex-column">
                        <!-- Package Thumbnail -->
                        <div class="service-thumb">
                            <div class="text-center">
                                <i class="bi bi-box2-fill" style="font-size: 3rem; color: #3498db; opacity: 0.3;"></i>
                            </div>
                        </div>

                        <!-- Package Content -->
                        <div class="p-4 d-flex flex-column h-100">
                            <h5 class="mb-2">
                                <?= esc((string) ($package['package_name'] ?? 'Package')) ?>
                            </h5>
                            <p class="text-muted small mb-3 flex-grow-1">
                                <?= esc(substr((string) ($package['description'] ?? 'No description available.'), 0, 100)) ?>...
                            </p>

                            <!-- Price Badge -->
                            <div class="mb-3">
                                <span class="badge bg-primary">
                                    ₱<?= number_format((float) ($package['base_price'] ?? 0), 2) ?>
                                </span>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <small class="text-muted">Status:</small><br>
                                <span class="badge bg-success">Available</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 mt-auto">
                                <a class="btn btn-outline-primary btn-sm flex-grow-1" 
                                   href="<?= site_url('/client/service/package/' . (int) ($package['package_id'] ?? 0)) ?>">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                                <?php if ($isEligible): ?>
                                    <a class="btn btn-primary btn-sm" 
                                       href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>"
                                       title="Apply for this package">
                                        <i class="bi bi-arrow-right"></i>
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
// Initialize popovers
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
});
</script>

<?= $this->endSection() ?>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($state === 'awaiting_activation'): ?>
                    Complete your initial payment before requesting services.
                <?php elseif ($state === 'active'): ?>
                    You must complete at least 2 months of payments before requesting services.
                <?php else: ?>
                    You must register as a Plan Holder to apply.
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <?php if ($state === 'awaiting_activation'): ?>
                    <a href="<?= base_url('initial-payment') ?>" class="btn btn-primary">Go to Initial Payment</a>
                <?php elseif ($state === 'active'): ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
                <?php else: ?>
                    <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
