<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$packageItemsByPackage = [];
foreach (($package_items ?? []) as $packageItem) {
    $packageId = (int) ($packageItem['package_id'] ?? 0);
    if (! isset($packageItemsByPackage[$packageId])) {
        $packageItemsByPackage[$packageId] = [];
    }
    $packageItemsByPackage[$packageId][] = $packageItem;
}

$profileData = $current_profile ?? [];
$profileValue = static function (string $key, string $default = '') use ($profileData) {
    return old($key, $profileData[$key] ?? $default);
};
$membershipValue = $profileValue;
$canApply = (bool) ($can_apply ?? ((int) session('is_plan_holder') === 1));
?>

<style>
    .service-page-shell {
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 40%),
            radial-gradient(circle at top left, rgba(15, 118, 110, 0.08), transparent 36%);
        border-radius: 28px;
        padding: 1rem;
    }

    .service-tabs .nav-link {
        border-radius: 999px;
        font-weight: 700;
        color: #64748b;
        padding: 0.9rem 1.3rem;
        border: 1px solid transparent;
        background: rgba(255, 255, 255, 0.72);
    }

    .service-tabs {
        flex-wrap: wrap;
    }

    .service-tabs .nav-link.active {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: #fff;
        box-shadow: 0 12px 24px rgba(15, 118, 110, 0.18);
    }

    .service-card {
        position: relative;
        border-radius: 28px;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
    }

    .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 26px 58px rgba(15, 23, 42, 0.12);
        border-color: rgba(15, 118, 110, 0.18);
    }

    .service-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #0f766e, #2563eb, #16a34a);
    }

    .service-card__icon {
        width: 3rem;
        height: 3rem;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(15, 118, 110, 0.12), rgba(37, 99, 235, 0.12));
        color: #0f766e;
    }

    .service-tag {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.35rem 0.7rem;
        background: rgba(37, 99, 235, 0.08);
        color: #2563eb;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .package-card {
        position: relative;
        border-radius: 28px;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .package-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.12);
        border-color: rgba(37, 99, 235, 0.22);
    }

    .package-card.popular {
        border-color: rgba(37, 99, 235, 0.55);
        box-shadow: 0 26px 60px rgba(37, 99, 235, 0.12);
    }

    .package-card__header {
        min-height: 9rem;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.2), transparent 36%),
            linear-gradient(135deg, rgba(15, 118, 110, 0.08), rgba(37, 99, 235, 0.04));
    }

    .package-feature {
        display: flex;
        gap: 0.65rem;
        align-items: flex-start;
        color: #475569;
        font-weight: 500;
    }

    .package-feature i {
        color: #16a34a;
        margin-top: 0.15rem;
    }

    .detail-overlay {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
    }

    .detail-overlay.show {
        display: flex;
    }

    .detail-modal {
        width: min(100%, 980px);
        border-radius: 34px;
        overflow: hidden;
        box-shadow: 0 34px 80px rgba(15, 23, 42, 0.25);
        background: #fff;
    }

    .detail-modal__left {
        background: linear-gradient(160deg, #0f766e, #2563eb);
        color: #fff;
        padding: 2.25rem;
        position: relative;
        overflow: hidden;
    }

    .detail-modal__left::after {
        content: '';
        position: absolute;
        inset: auto -4rem -4rem auto;
        width: 12rem;
        height: 12rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        filter: blur(10px);
    }

    .detail-modal__right {
        padding: 2rem;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
    }

    .ghost-btn {
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.88);
        border-radius: 16px;
        width: 2.75rem;
        height: 2.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .history-card {
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
    }

    .history-row {
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 18px;
        background: #fff;
        padding: 1rem;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.32rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .status-pill.pending {
        color: #b45309;
        background: rgba(245, 158, 11, 0.16);
    }

    .status-pill.approved,
    .status-pill.completed {
        color: #166534;
        background: rgba(22, 163, 74, 0.16);
    }

    .status-pill.rejected,
    .status-pill.cancelled {
        color: #b91c1c;
        background: rgba(239, 68, 68, 0.16);
    }

    .status-pill.active {
        color: #0f766e;
        background: rgba(15, 118, 110, 0.16);
    }

    .section-hero {
        border-radius: 28px;
        padding: 1.5rem;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.18), transparent 28%),
            linear-gradient(135deg, rgba(15, 118, 110, 0.96), rgba(37, 99, 235, 0.94));
        color: #fff;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
    }

    .soft-card {
        border: 1px solid rgba(226, 232, 240, 0.96);
        border-radius: 26px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
    }

    .info-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .info-card {
        border-radius: 24px;
        padding: 1.25rem;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        height: 100%;
    }

    .info-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-weight: 800;
        margin-bottom: 0.35rem;
    }

    .info-value {
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    .form-panel {
        border-radius: 26px;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    }

    .form-panel .form-label {
        color: #334155;
        font-weight: 700;
    }

    .readonly-field {
        background: #f8fafc;
        color: #475569;
    }
</style>

<div class="container-fluid service-page-shell">
    <h1 class="h4 mb-3">Service Applications</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if ((int) session('role_id') === 4): ?>
        <?php if (! $canApply): ?>
            <div class="alert alert-warning mb-4">
                Your account is pending plan holder approval. You can browse services and packages, but Apply actions are disabled until approval.
            </div>
        <?php endif; ?>
        <div class="soft-card p-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="text-uppercase small fw-bold text-muted mb-2">Member Details</div>
                    <h2 class="h4 fw-bold mb-1"><?= esc((string) ($profileData['first_name'] ?? 'Plan Holder')) ?> <?= esc((string) ($profileData['last_name'] ?? '')) ?></h2>
                    <p class="text-secondary mb-0"><?= esc((string) ($profileData['email'] ?? 'No email available')) ?></p>
                </div>
                <div class="d-grid gap-2 text-lg-end">
                    <div><span class="fw-semibold">Member ID:</span> <?= esc((string) ($profileData['unique_identifier'] ?? 'Not assigned')) ?></div>
                    <div><span class="fw-semibold">Branch:</span> <?= esc((string) ($profileData['branch_name'] ?? 'Not assigned')) ?></div>
                    <div><span class="fw-semibold">Status:</span> <span class="text-capitalize"><?= esc((string) ($profileData['plan_holder_status'] ?? 'active')) ?></span></div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4" id="serviceOptionsHeader">
            <div>
                <h3 class="h4 mb-1">Service Options</h3>
                <p class="text-secondary mb-0">Browse services and packages using cards, then apply directly from each card or from details.</p>
            </div>
            <ul class="nav service-tabs gap-2" id="serviceTabs">
                <li class="nav-item">
                    <button class="nav-link active" type="button" data-tab-target="services-tab"><i class="ti ti-briefcase me-1"></i> Services</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-tab-target="packages-tab"><i class="ti ti-package me-1"></i> Packages</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-tab-target="history-tab"><i class="ti ti-history me-1"></i> Application History</button>
                </li>
            </ul>
        </div>

        <div class="tab-panel" id="services-tab">
            <div class="row g-3 mb-4">
                <?php foreach (($service_catalog ?? []) as $service): ?>
                    <div class="col-md-6 col-xl-4">
                        <?php
                            $serviceName = (string) ($service['service_name'] ?? '-');
                            $serviceDescription = (string) ($service['description'] ?? 'No description provided.');
                            $servicePrice = (float) ($service['base_price'] ?? 0);
                            $serviceStatus = (string) ($service['status'] ?? 'active');
                            $serviceFeatures = [
                                'Service: ' . $serviceName,
                                'Status: ' . strtoupper($serviceStatus),
                                'Base price: P' . number_format($servicePrice, 2),
                            ];
                        ?>
                        <div
                            class="service-card h-100 p-4"
                            role="button"
                            tabindex="0"
                            data-detail-title="<?= esc($serviceName) ?>"
                            data-detail-tag="<?= esc(strtoupper($serviceStatus)) ?>"
                            data-detail-price="P<?= number_format($servicePrice, 2) ?>"
                            data-detail-description="<?= esc($serviceDescription) ?>"
                            data-detail-overview="<?= esc($serviceDescription) ?>"
                            data-detail-features='<?= esc(json_encode($serviceFeatures), 'attr') ?>'
                            data-apply-type="service"
                            data-apply-id="<?= esc((string) ($service['service_id'] ?? 0)) ?>"
                            data-service-name="<?= esc($serviceName) ?>"
                        >
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="service-card__icon"><i class="ti ti-briefcase"></i></div>
                                <span class="service-tag"><?= esc(strtoupper($serviceStatus)) ?></span>
                            </div>
                            <h3 class="h4 mb-2"><?= esc($serviceName) ?></h3>
                            <p class="text-secondary mb-4"><?= esc($serviceDescription) ?></p>
                            <div class="d-flex justify-content-between align-items-center gap-2 pt-3 border-top">
                                <div class="small text-muted">P<?= number_format($servicePrice, 2) ?> base price</div>
                                <div class="d-flex gap-2 align-items-center">
                                    <button type="button" class="btn btn-link p-0 text-decoration-none fw-bold text-primary">Details <i class="ti ti-arrow-right ms-1"></i></button>
                                    <form method="post" action="<?= base_url('service-applications/request') ?>" class="apply-form m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="apply_target" value="service">
                                        <input type="hidden" name="service_id" value="<?= esc((string) ($service['service_id'] ?? 0)) ?>">
                                        <input type="hidden" name="service_name" value="<?= esc($serviceName) ?>">
                                        <button type="submit" class="btn btn-primary btn-sm apply-btn" <?= $canApply ? '' : 'disabled' ?> title="<?= $canApply ? '' : 'Pending plan holder approval' ?>">
                                            <?= $canApply ? 'Apply' : 'Pending Approval' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($service_catalog)): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-4 text-center text-secondary">No active services available yet.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-panel d-none" id="packages-tab">
            <div class="row g-3 mb-4">
                <?php foreach (($packages ?? []) as $index => $package): ?>
                    <?php
                        $isPopular = $index === 1 || ((int) ($package['is_customizable'] ?? 0) === 1 && $index === 0);
                        $packageId = (int) ($package['package_id'] ?? 0);
                        $items = $packageItemsByPackage[$packageId] ?? [];
                        $featureNames = array_slice(array_map(static fn ($item) => $item['item_name'] ?? '-', $items), 0, 4);
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div
                            class="package-card h-100 <?= $isPopular ? 'popular' : '' ?>"
                            role="button"
                            tabindex="0"
                            data-detail-title="<?= esc((string) ($package['package_name'] ?? '-')) ?>"
                            data-detail-tag="<?= $isPopular ? 'Most Popular' : ((int) ($package['is_customizable'] ?? 0) === 1 ? 'Customizable' : 'Standard') ?>"
                            data-detail-price="P<?= number_format((float) ($package['base_price'] ?? 0), 2) ?>"
                            data-detail-description="<?= esc((string) ($package['description'] ?? '')) ?>"
                            data-detail-overview="<?= esc((string) ($package['description'] ?? '')) ?>"
                            data-detail-features='<?= esc(json_encode(array_merge($featureNames, [((int) ($package['is_customizable'] ?? 0) === 1) ? 'Customizable package' : 'Fixed package', 'Included items: ' . (int) ($package['item_count'] ?? 0)])), 'attr') ?>'
                            data-apply-type="package"
                            data-apply-id="<?= esc((string) ($package['package_id'] ?? 0)) ?>"
                        >
                            <div class="package-card__header p-4 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <div class="service-card__icon"><i class="ti ti-package"></i></div>
                                    <span class="service-tag"><?= $isPopular ? 'Most Popular' : (((int) ($package['is_customizable'] ?? 0) === 1) ? 'Customizable' : 'Standard') ?></span>
                                </div>
                                <div>
                                    <h3 class="h4 mb-1"><?= esc((string) ($package['package_name'] ?? '-')) ?></h3>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <span class="display-6 fw-bold mb-0">P<?= number_format((float) ($package['base_price'] ?? 0), 2) ?></span>
                                        <span class="text-secondary fw-semibold">/ plan</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 d-flex flex-column gap-3">
                                <p class="text-secondary mb-0"><?= esc((string) ($package['description'] ?? 'No description provided.')) ?></p>
                                <div class="d-grid gap-2">
                                    <?php foreach ($featureNames as $featureName): ?>
                                        <div class="package-feature"><i class="ti ti-check"></i><span><?= esc((string) $featureName) ?></span></div>
                                    <?php endforeach; ?>
                                    <div class="package-feature"><i class="ti ti-layers-subtract"></i><span><?= esc((string) ($package['item_count'] ?? 0)) ?> package items</span></div>
                                </div>
                                <div class="pt-2 d-flex justify-content-between align-items-center gap-2 border-top">
                                    <span class="small text-muted fw-semibold">Tap for details</span>
                                    <div class="d-flex gap-2 align-items-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm">Details</button>
                                        <form method="post" action="<?= base_url('service-applications/request') ?>" class="apply-form m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="apply_target" value="package">
                                            <input type="hidden" name="package_id" value="<?= esc((string) ($package['package_id'] ?? 0)) ?>">
                                            <button type="submit" class="btn btn-primary btn-sm apply-btn" <?= $canApply ? '' : 'disabled' ?> title="<?= $canApply ? '' : 'Pending plan holder approval' ?>">
                                                <?= $canApply ? 'Apply' : 'Pending Approval' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($packages)): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-4 text-center text-secondary">No packages available yet.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-panel d-none" id="history-tab">
            <div class="history-card p-4 p-lg-5 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1">Application History</h3>
                        <p class="text-secondary mb-0">Track the progress of all your submitted applications.</p>
                    </div>
                    <div class="service-card__icon"><i class="ti ti-file-invoice"></i></div>
                </div>

                <div class="d-grid gap-3">
                    <?php foreach ($applications as $application): ?>
                        <?php $status = strtolower((string) ($application['status'] ?? 'pending')); ?>
                        <div class="history-row">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                <div>
                                    <div class="fw-bold mb-1">#<?= esc((string) $application['application_id']) ?> <?= esc((string) ($application['application_name'] ?? '-')) ?></div>
                                    <div class="small text-secondary">Submitted <?= esc((string) $application['created_at']) ?></div>
                                </div>
                                <div class="text-lg-end">
                                    <span class="status-pill <?= esc($status) ?>"><?= esc((string) $application['status']) ?></span>
                                    <div class="small text-secondary mt-2 text-uppercase fw-bold"><?= esc((string) ($application['application_type'] ?? 'package')) ?></div>
                                    <div class="small text-secondary mt-2"><?= esc($application['first_name'] . ' ' . $application['last_name']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($applications)): ?>
                        <div class="history-row text-center text-secondary py-5">No application history yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-panel d-none" id="profile-tab">
            <div class="row g-4 mb-4">
                <div class="col-xl-4">
                    <div class="section-hero h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                            <div>
                                <div class="text-white-50 text-uppercase small fw-bold mb-2">My Profile</div>
                                <h3 class="h2 fw-bold mb-2">Account details at a glance</h3>
                                <p class="text-white-75 mb-0">Update the information tied to your account and keep your contact details current.</p>
                            </div>
                            <div class="service-card__icon bg-white text-primary"><i class="ti ti-user-edit"></i></div>
                        </div>

                        <div class="d-grid gap-3">
                            <div>
                                <div class="info-label text-white-50">Username</div>
                                <div class="info-value text-white"><?= esc((string) ($profileData['username'] ?? '')) ?></div>
                            </div>
                            <div>
                                <div class="info-label text-white-50">Email</div>
                                <div class="info-value text-white"><?= esc((string) ($profileData['email'] ?? '')) ?></div>
                            </div>
                            <div>
                                <div class="info-label text-white-50">Contact number</div>
                                <div class="info-value text-white"><?= esc((string) ($profileData['contact_number'] ?? 'Not provided')) ?></div>
                            </div>
                            <div>
                                <div class="info-label text-white-50">Account name</div>
                                <div class="info-value text-white"><?= esc(trim((string) ($profileData['first_name'] ?? '') . ' ' . (string) ($profileData['last_name'] ?? ''))) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <form method="post" action="<?= base_url('service-applications/profile-update') ?>" class="form-panel p-4 p-lg-5 h-100">
                        <?= csrf_field() ?>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                            <div>
                                <h3 class="h4 mb-1">Edit profile</h3>
                                <p class="text-secondary mb-0">Adjust the account details that were used when the account was created.</p>
                            </div>
                            <span class="status-pill active">Editable</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" value="<?= esc((string) $profileValue('username')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= esc((string) $profileValue('email')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">First name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?= esc((string) $profileValue('first_name')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Last name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?= esc((string) $profileValue('last_name')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contact_number">Contact number</label>
                                <input type="text" id="contact_number" name="contact_number" class="form-control" value="<?= esc((string) $profileValue('contact_number')) ?>" placeholder="09xxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="profile_status">Account status</label>
                                <input type="text" id="profile_status" class="form-control readonly-field" value="<?= esc((string) ($profileData['plan_holder_status'] ?? 'active')) ?>" readonly>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-panel d-none" id="membership-tab">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= base_url('plan-holder-registration') ?>" class="btn btn-primary">
                    Register plan Now!
                </a>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-xl-4">
                    <div class="soft-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                            <div>
                                <div class="text-uppercase small fw-bold text-muted mb-2">Membership Details</div>
                                <h3 class="h4 mb-2">Your plan-holder record</h3>
                                <p class="text-secondary mb-0">Review the membership information submitted during registration and update the editable parts when needed.</p>
                            </div>
                            <div class="service-card__icon"><i class="ti ti-id-badge"></i></div>
                        </div>

                        <div class="d-grid gap-3">
                            <div class="info-card">
                                <div class="info-label">Member ID</div>
                                <div class="info-value"><?= esc((string) ($profileData['unique_identifier'] ?? '')) ?></div>
                            </div>
                            <div class="info-card">
                                <div class="info-label">Branch</div>
                                <div class="info-value"><?= esc((string) ($profileData['branch_name'] ?? 'Not assigned')) ?></div>
                            </div>
                            <div class="info-card">
                                <div class="info-label">Program</div>
                                <div class="info-value"><?= esc((string) ($profileData['plan']['program_name'] ?? $profileData['plan']['package_name'] ?? \App\Services\MembershipService::PROGRAM_NAME)) ?></div>
                            </div>
                            <div class="info-card">
                                <div class="info-label">Membership status</div>
                                <div class="info-value text-capitalize"><?= esc((string) ($profileData['plan_holder_status'] ?? 'active')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <form method="post" action="<?= base_url('service-applications/membership-update') ?>" class="form-panel p-4 p-lg-5 h-100">
                        <?= csrf_field() ?>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                            <div>
                                <h3 class="h4 mb-1">Membership details</h3>
                                <p class="text-secondary mb-0">Details are read-only by default to keep this page clean. Click Edit Details to update.</p>
                            </div>
                            <span class="status-pill active" id="membershipModePill">View Only</span>
                        </div>

                        <fieldset id="membershipFieldset" disabled>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="membership_status">Status</label>
                                <select id="membership_status" name="status" class="form-select" required>
                                    <option value="active" <?= $membershipValue('plan_holder_status') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $membershipValue('plan_holder_status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="unique_identifier">Member ID</label>
                                <input type="text" id="unique_identifier" class="form-control readonly-field" value="<?= esc((string) $profileValue('unique_identifier')) ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="branch_name">Branch</label>
                                <input type="text" id="branch_name" class="form-control readonly-field" value="<?= esc((string) $profileValue('branch_name')) ?>" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="age">Age</label>
                                <input type="number" min="0" id="age" name="age" class="form-control" value="<?= esc((string) $membershipValue('age')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-select">
                                    <?php $genderValue = (string) $membershipValue('gender'); ?>
                                    <option value="" <?= $genderValue === '' ? 'selected' : '' ?>>Select</option>
                                    <option value="male" <?= $genderValue === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= $genderValue === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= $genderValue === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="civil_status">Civil status</label>
                                <input type="text" id="civil_status" name="civil_status" class="form-control" value="<?= esc((string) $membershipValue('civil_status')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="citizenship">Citizenship</label>
                                <input type="text" id="citizenship" name="citizenship" class="form-control" value="<?= esc((string) $membershipValue('citizenship')) ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="date_of_birth">Date of birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= esc((string) $membershipValue('date_of_birth')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="place_of_birth">Place of birth</label>
                                <input type="text" id="place_of_birth" name="place_of_birth" class="form-control" value="<?= esc((string) $membershipValue('place_of_birth')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="senior_citizen_id">Senior citizen ID</label>
                                <input type="text" id="senior_citizen_id" name="senior_citizen_id" class="form-control" value="<?= esc((string) $membershipValue('senior_citizen_id')) ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="height">Height</label>
                                <input type="text" id="height" name="height" class="form-control" value="<?= esc((string) $membershipValue('height')) ?>" placeholder="cm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="weight">Weight</label>
                                <input type="text" id="weight" name="weight" class="form-control" value="<?= esc((string) $membershipValue('weight')) ?>" placeholder="kg">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="spouse_name">Spouse name</label>
                                <input type="text" id="spouse_name" name="spouse_name" class="form-control" value="<?= esc((string) $membershipValue('spouse_name')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="spouse_birthdate">Spouse birthdate</label>
                                <input type="date" id="spouse_birthdate" name="spouse_birthdate" class="form-control" value="<?= esc((string) $membershipValue('spouse_birthdate')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="spouse_occupation">Spouse occupation</label>
                                <input type="text" id="spouse_occupation" name="spouse_occupation" class="form-control" value="<?= esc((string) $membershipValue('spouse_occupation')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="organization_affiliation">Organization affiliation</label>
                                <input type="text" id="organization_affiliation" name="organization_affiliation" class="form-control" value="<?= esc((string) $membershipValue('organization_affiliation')) ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label" for="address_no">No.</label>
                                <input type="text" id="address_no" name="address_no" class="form-control" value="<?= esc((string) $membershipValue('address_no')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="address_street">Street</label>
                                <input type="text" id="address_street" name="address_street" class="form-control" value="<?= esc((string) $membershipValue('address_street')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="address_barangay">Barangay</label>
                                <input type="text" id="address_barangay" name="address_barangay" class="form-control" value="<?= esc((string) $membershipValue('address_barangay')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="address_city">City</label>
                                <input type="text" id="address_city" name="address_city" class="form-control" value="<?= esc((string) $membershipValue('address_city')) ?>">
                            </div>
                        </div>
                        </fieldset>

                        <div class="d-flex justify-content-end flex-wrap gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-primary px-4" id="enableMembershipEdit">Edit Details</button>
                            <button type="button" class="btn btn-light px-4 d-none" id="cancelMembershipEdit">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 d-none" id="saveMembershipDetails">Save Membership Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array((int) session('role_id'), [1, 2], true)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">Review Pending Applications</h2>
                <form method="post" action="<?= base_url('service-applications/review') ?>" class="mb-3">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="application_id">Application</label>
                            <select id="application_id" name="application_id" class="form-select" required>
                                <option value="">Select application</option>
                                <?php foreach ($applications as $application): ?>
                                    <?php if ($application['status'] === 'pending'): ?>
                                        <option value="<?= esc((string) $application['application_id']) ?>">
                                            #<?= esc((string) $application['application_id']) ?> - <?= esc($application['first_name'] . ' ' . $application['last_name']) ?> - <?= esc((string) $application['application_name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="review_status">Decision</label>
                            <select id="review_status" name="status" class="form-select" required>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-success mt-3" type="submit">Submit Decision</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int) session('role_id') !== 4): ?>
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3">Application History</h2>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Application</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td>#<?= esc((string) $application['application_id']) ?></td>
                                    <td><?= esc($application['first_name'] . ' ' . $application['last_name']) ?><br><small class="text-muted"><?= esc((string) $application['unique_identifier']) ?></small></td>
                                    <td><?= esc((string) $application['application_name']) ?><br><small class="text-muted text-uppercase"><?= esc((string) ($application['application_type'] ?? 'package')) ?></small></td>
                                    <td><?= esc((string) $application['status']) ?></td>
                                    <td><?= esc((string) $application['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ((int) session('role_id') === 4): ?>
<div class="detail-overlay" id="detailOverlay" aria-hidden="true">
    <div class="detail-modal">
        <div class="row g-0">
            <div class="col-md-4 detail-modal__left">
                <button type="button" class="ghost-btn position-absolute top-0 end-0 m-3" id="closeDetail" aria-label="Close details">
                    <i class="ti ti-x"></i>
                </button>
                <div class="position-relative" style="z-index: 1;">
                    <div class="mb-3">
                        <div class="text-white-50 small text-uppercase fw-semibold mb-2">Detail</div>
                        <h3 class="h2 fw-bold mb-2" id="detailTitle">Service</h3>
                        <div class="fw-semibold text-white-75" id="detailTag">Plan holder</div>
                    </div>
                    <div class="display-6 fw-bold mb-3" id="detailPrice">-</div>
                    <p class="text-white-75 mb-0" id="detailOverview"></p>
                </div>
            </div>
            <div class="col-md-8 detail-modal__right">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="text-uppercase small fw-bold text-muted mb-1">What's included</div>
                        <h4 class="h5 mb-0" id="detailDescription"></h4>
                    </div>
                    <button type="button" class="ghost-btn" id="closeDetailTop" aria-label="Close details">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <div class="row g-3" id="detailFeatures"></div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <form method="post" action="<?= base_url('service-applications/request') ?>" id="detailApplyForm" class="m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="apply_target" id="detailApplyTarget" value="package">
                        <input type="hidden" name="package_id" id="detailApplyPackageId" value="">
                        <input type="hidden" name="service_id" id="detailApplyServiceId" value="">
                        <input type="hidden" name="service_name" id="detailApplyServiceName" value="">
                        <button type="submit" class="btn btn-primary" id="detailApplyButton" <?= $canApply ? '' : 'disabled' ?> title="<?= $canApply ? '' : 'Pending plan holder approval' ?>">
                            <?= $canApply ? 'Apply' : 'Pending Approval' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const canApply = <?= $canApply ? 'true' : 'false' ?>;
        const tabs = document.querySelectorAll('[data-tab-target]');
        const panels = document.querySelectorAll('.tab-panel');
        const detailOverlay = document.getElementById('detailOverlay');
        const detailTitle = document.getElementById('detailTitle');
        const detailTag = document.getElementById('detailTag');
        const detailPrice = document.getElementById('detailPrice');
        const detailOverview = document.getElementById('detailOverview');
        const detailDescription = document.getElementById('detailDescription');
        const detailFeatures = document.getElementById('detailFeatures');
        const detailApplyTarget = document.getElementById('detailApplyTarget');
        const detailApplyPackageId = document.getElementById('detailApplyPackageId');
        const detailApplyServiceId = document.getElementById('detailApplyServiceId');
        const detailApplyServiceName = document.getElementById('detailApplyServiceName');
        const detailApplyButton = document.getElementById('detailApplyButton');
        const closeButtons = [document.getElementById('closeDetail'), document.getElementById('closeDetailTop')];
        const serviceOptionsHeader = document.getElementById('serviceOptionsHeader');
        const serviceTabs = document.getElementById('serviceTabs');
        const membershipFieldset = document.getElementById('membershipFieldset');
        const membershipModePill = document.getElementById('membershipModePill');
        const enableMembershipEdit = document.getElementById('enableMembershipEdit');
        const cancelMembershipEdit = document.getElementById('cancelMembershipEdit');
        const saveMembershipDetails = document.getElementById('saveMembershipDetails');

        function openDetail(card) {
            const featuresRaw = card.getAttribute('data-detail-features') || '[]';
            let features = [];

            try {
                features = JSON.parse(featuresRaw);
            } catch (error) {
                features = [];
            }

            detailTitle.textContent = card.getAttribute('data-detail-title') || '';
            detailTag.textContent = card.getAttribute('data-detail-tag') || '';
            detailPrice.textContent = card.getAttribute('data-detail-price') || '';
            detailOverview.textContent = card.getAttribute('data-detail-overview') || '';
            detailDescription.textContent = card.getAttribute('data-detail-description') || '';

            const applyType = card.getAttribute('data-apply-type') || 'package';
            const applyId = card.getAttribute('data-apply-id') || '';
            detailApplyTarget.value = applyType;
            detailApplyPackageId.value = applyType === 'package' ? applyId : '';
            detailApplyServiceId.value = applyType === 'service' ? applyId : '';
            detailApplyServiceName.value = applyType === 'service' ? (card.getAttribute('data-service-name') || '') : '';
            if (canApply) {
                detailApplyButton.textContent = applyType === 'service' ? 'Apply to this service' : 'Apply to this package';
            } else {
                detailApplyButton.textContent = 'Pending Approval';
                detailApplyButton.setAttribute('disabled', 'disabled');
            }

            detailFeatures.innerHTML = '';
            features.forEach((feature) => {
                const column = document.createElement('div');
                column.className = 'col-sm-6';

                const featureCard = document.createElement('div');
                featureCard.className = 'd-flex align-items-start gap-2 p-3 rounded-4';
                featureCard.style.background = 'rgba(15,23,42,.03)';
                featureCard.style.border = '1px solid rgba(148,163,184,.16)';

                const icon = document.createElement('i');
                icon.className = 'ti ti-check text-success mt-1';

                const label = document.createElement('span');
                label.className = 'fw-medium text-secondary';
                label.textContent = feature;

                featureCard.append(icon, label);
                column.appendChild(featureCard);
                detailFeatures.appendChild(column);
            });
            detailOverlay.classList.add('show');
            detailOverlay.setAttribute('aria-hidden', 'false');
        }

        function closeDetail() {
            detailOverlay.classList.remove('show');
            detailOverlay.setAttribute('aria-hidden', 'true');
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                activateTab(tab.getAttribute('data-tab-target') || 'services-tab', true, tab);
            });
        });

        function activateTab(targetId, updateHash, triggerButton) {
            tabs.forEach((button) => button.classList.remove('active'));
            panels.forEach((panel) => panel.classList.add('d-none'));

            const targetButton = triggerButton || Array.from(tabs).find((button) => button.getAttribute('data-tab-target') === targetId);
            if (targetButton) {
                targetButton.classList.add('active');
            }

            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.remove('d-none');
            }

            if (updateHash && targetId) {
                window.location.hash = targetId;
            }
        }

        function hideServiceHistoryTabsForMembership(targetId) {
            if (targetId !== 'membership-tab') {
                if (serviceOptionsHeader) {
                    serviceOptionsHeader.classList.remove('d-none');
                }
                if (serviceTabs) {
                    serviceTabs.classList.remove('d-none');
                }
                return;
            }

            if (serviceOptionsHeader) {
                serviceOptionsHeader.classList.add('d-none');
            }
            if (serviceTabs) {
                serviceTabs.classList.add('d-none');
            }

            ['services-tab', 'packages-tab', 'history-tab'].forEach((tabId) => {
                const button = Array.from(tabs).find((tab) => tab.getAttribute('data-tab-target') === tabId);
                if (button) {
                    const navItem = button.closest('.nav-item');
                    if (navItem) {
                        navItem.classList.add('d-none');
                    } else {
                        button.classList.add('d-none');
                    }
                }
            });
        }

        const initialTab = window.location.hash ? window.location.hash.replace('#', '') : 'services-tab';
        if (document.getElementById(initialTab)) {
            activateTab(initialTab, false);
            hideServiceHistoryTabsForMembership(initialTab);
        }

        if (enableMembershipEdit && membershipFieldset) {
            enableMembershipEdit.addEventListener('click', () => {
                membershipFieldset.disabled = false;
                membershipModePill.textContent = 'Editing';
                enableMembershipEdit.classList.add('d-none');
                cancelMembershipEdit.classList.remove('d-none');
                saveMembershipDetails.classList.remove('d-none');
            });
        }

        if (cancelMembershipEdit && membershipFieldset) {
            cancelMembershipEdit.addEventListener('click', () => {
                membershipFieldset.disabled = true;
                membershipModePill.textContent = 'View Only';
                cancelMembershipEdit.classList.add('d-none');
                saveMembershipDetails.classList.add('d-none');
                enableMembershipEdit.classList.remove('d-none');
            });
        }

        document.querySelectorAll('.service-card, .package-card').forEach((card) => {
            card.addEventListener('click', () => openDetail(card));
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openDetail(card);
                }
            });
        });

        document.querySelectorAll('.apply-form, .apply-btn').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });

        closeButtons.forEach((button) => {
            if (button) {
                button.addEventListener('click', closeDetail);
            }
        });

        detailOverlay.addEventListener('click', (event) => {
            if (event.target === detailOverlay) {
                closeDetail();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDetail();
            }
        });
    })();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
