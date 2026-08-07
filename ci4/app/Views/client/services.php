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
        border: 1px solid rgba(229, 231, 235, 1);
        border-radius: 1.75rem;
        overflow: hidden;
        background: #ffffff;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        display: flex;
        flex-direction: column;
    }
    .service-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    }
    .service-thumb {
        height: 220px;
        min-height: 220px;
        flex: 0 0 220px;
        background: linear-gradient(135deg, rgba(224, 242, 254, 0.8), rgba(3, 105, 161, 0.08));
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        overflow: hidden;
    }
    .service-thumb img {
        width: 100%;
        min-height: 220px;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .service-card-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }
    .service-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }
    .service-description {
        color: #6b7280;
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .service-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .service-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1d4ed8;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.45rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .status-pill.available {
        background: #ecfdf5;
        color: #166534;
        border: 1px solid #86efac;
    }
    .status-pill.unavailable {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .service-actions {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.75rem;
        margin-top: auto;
    }
    .service-actions .btn {
        border-radius: 1rem;
        font-size: 0.9rem;
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
    }
    .service-actions .btn-outline-secondary,
    .service-actions .btn-outline-primary {
        border-width: 1px;
    }
    .service-actions .btn-primary {
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.12);
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
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach (($services ?? []) as $service): ?>
                <?php $serviceAvailable = (int) ($service['is_available'] ?? 1) === 1; ?>
                <div class="col">
                    <div class="service-card h-100">
                        <div class="service-thumb">
                            <img src="<?= base_url('assets/images/temporary-image.png') ?>" alt="Service image" class="img-fluid w-100 h-100 object-fit-cover">
                        </div>

                        <div class="service-card-body">
                            <div class="service-title">
                                <?= esc((string) ($service['service_name'] ?? 'Service')) ?>
                            </div>
                            <div class="service-description">
                                <?= esc(substr((string) ($service['description'] ?? 'No description available.'), 0, 110)) ?>
                            </div>

                            <div class="service-meta">
                                <div class="service-price">₱<?= number_format((float) ($service['base_price'] ?? 0), 2) ?></div>
                                <div class="status-pill <?= $serviceAvailable ? 'available' : 'unavailable' ?>">
                                    <?= $serviceAvailable ? 'Available' : 'Not available' ?>
                                </div>
                            </div>

                            <div class="service-actions">
                                <a class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center"
                                   href="<?= site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                                <?php if ($isEligible && $serviceAvailable): ?>
                                    <a class="btn btn-primary btn-sm d-flex align-items-center justify-content-center"
                                       href="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>"
                                       title="Apply for this service">
                                        <i class="bi bi-arrow-right me-1"></i> Apply
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm d-flex align-items-center justify-content-center"
                                            data-bs-toggle="popover"
                                            data-bs-trigger="hover"
                                            title="Cannot apply"
                                            data-bs-content="<?= $serviceAvailable ? 'You need 2 months of contributions to apply.' : 'This service is currently unavailable.' ?>">
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
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($packages ?? [] as $package): ?>
                <?php $packageAvailable = (int) ($package['is_available'] ?? 1) === 1; ?>
                <div class="col">
                    <div class="service-card h-100">
                        <div class="service-thumb">
                            <img src="<?= base_url('assets/images/temporary-image.png') ?>" alt="Package image" class="img-fluid w-100 h-100 object-fit-cover">
                        </div>

                        <div class="service-card-body">
                            <div class="service-title">
                                <?= esc((string) ($package['package_name'] ?? 'Package')) ?>
                            </div>
                            <div class="service-description">
                                <?= esc(substr((string) ($package['description'] ?? 'No description available.'), 0, 110)) ?>
                            </div>

                            <div class="service-meta">
                                <div class="service-price">₱<?= number_format((float) ($package['base_price'] ?? 0), 2) ?></div>
                                <div class="status-pill <?= $packageAvailable ? 'available' : 'unavailable' ?>">
                                    <?= $packageAvailable ? 'Available' : 'Not available' ?>
                                </div>
                            </div>

                            <div class="service-actions">
                                <a class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center"
                                   href="<?= site_url('/client/package/' . (int) ($package['package_id'] ?? 0)) ?>">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                                <?php if ($isEligible && $packageAvailable): ?>
                                    <a class="btn btn-primary btn-sm d-flex align-items-center justify-content-center"
                                       href="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>"
                                       title="Apply for this package">
                                        <i class="bi bi-arrow-right me-1"></i> Apply
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm d-flex align-items-center justify-content-center"
                                            data-bs-toggle="popover"
                                            data-bs-trigger="hover"
                                            title="Cannot apply"
                                            data-bs-content="<?= $packageAvailable ? 'You need 2 months of contributions to apply.' : 'This package is currently unavailable.' ?>">
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

    <!-- Application Status Tracking -->
    <?php
        $myApplications = [];
        $planHolderId = (int) ($access['plan_holder']['plan_holder_id'] ?? 0);
        if ($planHolderId > 0) {
            $db = db_connect();
            if ($db->tableExists('service_applications')) {
                $myApplications = $db->table('service_applications sa')
                    ->select('sa.application_id, sa.service_list_id, sa.package_id, sa.status, sa.created_at, sa.rejection_reason, p.package_name, sl.service_name')
                    ->join('packages p', 'p.package_id = sa.package_id', 'left')
                    ->join('service_list sl', 'sl.service_list_id = sa.service_list_id', 'left')
                    ->where('sa.plan_holder_id', $planHolderId)
                    ->orderBy('sa.created_at', 'DESC')
                    ->get()
                    ->getResultArray();
            }
        }
    ?>

    <?php if (! empty($myApplications)): ?>
    <div class="card mt-4" style="border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden;">
        <div class="card-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
            <h5 class="mb-0" style="font-weight: 700;">My Applications</h5>
            <small class="text-muted">Track the status of your service and package applications.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th class="px-4 py-3" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b;">Application</th>
                            <th class="px-4 py-3" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b;">Type</th>
                            <th class="px-4 py-3" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b;">Submitted</th>
                            <th class="px-4 py-3" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myApplications as $app): ?>
                            <?php
                                $appStatus = strtolower((string) ($app['status'] ?? 'pending'));
                                $appName = (string) ($app['service_name'] ?? $app['package_name'] ?? 'Unknown');
                                $appType = ! empty($app['service_list_id']) ? 'Service' : 'Package';
                                $statusColors = [
                                    'pending' => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#fcd34d'],
                                    'approved' => ['bg' => '#dcfce7', 'text' => '#166534', 'border' => '#86efac'],
                                    'rejected' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
                                ];
                                $colors = $statusColors[$appStatus] ?? $statusColors['pending'];
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td class="px-4 py-3">
                                    <div style="font-weight: 600; color: #1e293b;"><?= esc($appName) ?></div>
                                    <?php if ($appStatus === 'rejected' && ! empty($app['rejection_reason'])): ?>
                                        <div style="font-size: 0.78rem; color: #dc2626; margin-top: 2px;">
                                            <i class="bi bi-exclamation-circle me-1"></i> <?= esc((string) $app['rejection_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span style="padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; background: #ede9fe; color: #6d28d9;">
                                        <?= $appType ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3" style="color: #64748b; font-size: 0.84rem;">
                                    <?= esc(date('M d, Y', strtotime((string) ($app['created_at'] ?? '')))) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span style="padding: 4px 12px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; background: <?= $colors['bg'] ?>; color: <?= $colors['text'] ?>; border: 1px solid <?= $colors['border'] ?>;">
                                        <?= strtoupper($appStatus) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
