<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">

<?php
    $activeTab = $active_tab ?? 'packages';
    $totalPending = (int) ($pending_count ?? 0);
    $totalApproved = (int) ($approved_count ?? 0);
?>

<div class="so">

    <!-- ====== KPI Bar ====== -->
    <div class="so-kpi-bar">
        <div class="so-kpi-item">Total Packages: <strong><?= (int) ($total_packages ?? 0) ?></strong></div>
        <div class="so-kpi-item">Total Services: <strong><?= (int) ($total_services ?? 0) ?></strong></div>
        <div class="so-kpi-item">Pending Requests: <strong><?= $totalPending ?></strong>
            <?php if ($totalPending > 0): ?>
                <span class="so-kpi-item__badge"><?= $totalPending ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ====== Header ====== -->
    <div>
        <h1 class="so-header__title">Service Offer</h1>
        <p class="so-header__sub">Create live packages and services, then review branch-admin requests in one place.</p>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== Tabs ====== -->
    <div class="so-tabs" role="tablist">
        <a href="<?= site_url('/branch-admin/service-package/packages') ?>" class="so-tab <?= $activeTab === 'packages' ? 'so-tab--active' : '' ?>">
            Packages
            <span class="so-tab__badge so-tab__badge--green">Active</span>
        </a>
        <a href="<?= site_url('/branch-admin/service-package/services') ?>" class="so-tab <?= $activeTab === 'services' ? 'so-tab--active' : '' ?>">
            Services
        </a>
        <a href="<?= site_url('/branch-admin/service-package/addons') ?>" class="so-tab <?= $activeTab === 'addons' ? 'so-tab--active' : '' ?>">
            Add-ons
        </a>
        <a href="<?= site_url('/branch-admin/service-package/rates') ?>" class="so-tab <?= $activeTab === 'rates' ? 'so-tab--active' : '' ?>">
            Service Rates
        </a>
        <a href="<?= site_url('/branch-admin/service-package/requests') ?>" class="so-tab <?= $activeTab === 'requests' ? 'so-tab--active' : '' ?>">
            Approval Queue
            <?php if ($totalPending > 0): ?>
                <span class="so-tab__badge">Pending: <?= $totalPending ?></span>
            <?php endif; ?>
            <?php if ($totalApproved > 0): ?>
                <span class="so-tab__badge so-tab__badge--green">Approved: <?= $totalApproved ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Packages                                                    -->
    <!-- ================================================================ -->
    <?php if ($activeTab === 'packages'): ?>

        <!-- Filter bar -->
        <div class="so-filter-bar">
            <div class="so-search">
                <i class="mdi mdi-magnify"></i>
                <input type="text" id="so-pkg-search" placeholder="Search" oninput="soFilterPackages()">
            </div>
            <select class="so-select" id="so-pkg-status" onchange="soFilterPackages()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select class="so-select" id="so-pkg-sort" onchange="soFilterPackages()">
                <option value="">Sort by</option>
                <option value="name">Name</option>
                <option value="price-asc">Price: Low → High</option>
                <option value="price-desc">Price: High → Low</option>
            </select>
            <button type="button" class="so-btn so-btn--purple" onclick="soOpenPanel('create-package')">
                <i class="mdi mdi-plus"></i> Create Package
            </button>
        </div>

        <!-- Package cards grid -->
        <div class="so-grid" id="so-pkg-grid">
            <?php if (empty($packages ?? [])): ?>
                <div class="so-empty">
                    <i class="mdi mdi-package-variant-closed"></i>
                    No packages found. Click "Create Package" to add one.
                </div>
            <?php else: ?>
                <?php foreach ($packages as $pkg):
                    $pkgStatus = strtolower((string) ($pkg['status'] ?? (($pkg['is_available'] ?? 1) == 1 ? 'active' : 'inactive')));
                    $desc = (string) ($pkg['description'] ?? '');
                    $descLines = array_filter(array_map('trim', explode("\n", $desc)));
                    $iconColors = ['so-pkg-card__icon--teal', 'so-pkg-card__icon--pink', 'so-pkg-card__icon--amber'];
                    $colorIdx = ((int) ($pkg['package_id'] ?? 0)) % 3;
                ?>
                <div class="so-pkg-card"
                     data-name="<?= esc(strtolower((string) ($pkg['package_name'] ?? ''))) ?>"
                     data-status="<?= esc($pkgStatus) ?>"
                     data-price="<?= (float) ($pkg['base_price'] ?? 0) ?>">
                    <div class="so-pkg-card__top">
                        <div class="so-pkg-card__icon <?= $iconColors[$colorIdx] ?>">
                            <i class="mdi mdi-file-document-outline"></i>
                        </div>
                        <div class="so-pkg-card__header">
                            <h3 class="so-pkg-card__name"><?= esc((string) ($pkg['package_name'] ?? 'Package')) ?></h3>
                            <div class="so-pkg-card__price">₱<?= esc(number_format((float) ($pkg['base_price'] ?? 0), 2)) ?></div>
                            <span class="so-pkg-card__badge so-pkg-card__badge--<?= $pkgStatus ?>"><?= $pkgStatus === 'active' ? 'Active' : 'Inactive' ?></span>
                        </div>
                    </div>
                    <div class="so-pkg-card__desc">
                        <?php if (! empty($descLines)): ?>
                            <ul>
                                <?php foreach (array_slice($descLines, 0, 4) as $line): ?>
                                    <li><?= esc($line) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span style="color:var(--so-ink-faint);font-size:0.82rem;">No description</span>
                        <?php endif; ?>
                    </div>
                    <div class="so-pkg-card__footer">
                        <input type="text" placeholder="" disabled>
                        <a href="<?= site_url('/branch-admin/packages/view/' . (int) $pkg['package_id']) ?>" class="so-btn so-btn--outline so-btn--sm">View</a>
                        <a href="<?= site_url('/branch-admin/packages/edit/' . (int) $pkg['package_id']) ?>" class="so-btn so-btn--outline so-btn--sm">Edit</a>
                        <div class="so-dropdown">
                            <button class="so-btn so-btn--ghost so-btn--sm" onclick="soToggleDropdown(this)" style="cursor:pointer;">
                                <i class="mdi mdi-dots-horizontal"></i>
                            </button>
                            <div class="so-dropdown__menu">
                                <a href="<?= site_url('/branch-admin/packages/view/' . (int) $pkg['package_id']) ?>" class="so-dropdown__item"><i class="mdi mdi-eye-outline"></i> View</a>
                                <a href="<?= site_url('/branch-admin/packages/edit/' . (int) $pkg['package_id']) ?>" class="so-dropdown__item"><i class="mdi mdi-pencil-outline"></i> Edit</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Services                                                    -->
    <!-- ================================================================ -->
    <?php if ($activeTab === 'services'): ?>
        <div class="so-filter-bar">
            <div class="so-search">
                <i class="mdi mdi-magnify"></i>
                <input type="text" id="so-svc-search" placeholder="Search" oninput="soFilterServices()">
            </div>
            <a href="<?= site_url('/branch-admin/services/create') ?>" class="so-btn so-btn--purple">
                <i class="mdi mdi-plus"></i> Create Service
            </a>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;" id="so-svc-list">
            <?php if (empty($services ?? [])): ?>
                <div class="so-empty">
                    <i class="mdi mdi-wrench-outline"></i>
                    No services found.
                </div>
            <?php else: ?>
                <?php foreach ($services as $svc):
                    $svcStatus = strtolower((string) ($svc['status'] ?? 'active'));
                ?>
                <div class="so-svc-card"
                     data-name="<?= esc(strtolower((string) ($svc['service_name'] ?? ''))) ?>"
                     data-status="<?= esc($svcStatus) ?>">
                    <div class="so-svc-card__icon"><i class="mdi mdi-wrench-outline"></i></div>
                    <div class="so-svc-card__body">
                        <div class="so-svc-card__name"><?= esc((string) ($svc['service_name'] ?? 'Service')) ?></div>
                        <div class="so-svc-card__meta">
                            <span class="so-badge so-badge--<?= $svcStatus === 'active' ? 'green' : 'amber' ?>"><?= $svcStatus === 'active' ? 'Active' : 'Inactive' ?></span>
                            <?php if (! empty($svc['description'])): ?>
                                — <?= esc(mb_strimwidth((string) $svc['description'], 0, 80, '…')) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="so-svc-card__price">₱<?= esc(number_format((float) ($svc['base_price'] ?? 0), 2)) ?></div>
                    <div class="so-svc-card__actions">
                        <a href="<?= site_url('/branch-admin/services/view/' . (int) $svc['service_list_id']) ?>" class="so-btn so-btn--outline so-btn--sm">View</a>
                        <a href="<?= site_url('/branch-admin/services/edit/' . (int) $svc['service_list_id']) ?>" class="so-btn so-btn--outline so-btn--sm">Edit</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Add-ons                                                     -->
    <!-- ================================================================ -->
    <?php if ($activeTab === 'addons'): ?>
        <div class="so-filter-bar">
            <div class="so-search">
                <i class="mdi mdi-magnify"></i>
                <input type="text" id="so-addon-search" placeholder="Search" oninput="soFilterAddOns()">
            </div>
            <button type="button" class="so-btn so-btn--purple" onclick="soOpenAddOnModal('add')">
                <i class="mdi mdi-plus"></i> Add Add-on
            </button>
        </div>

        <div class="so-card">
            <div class="so-table-wrap">
                <table class="so-table" id="so-addon-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Base Price</th>
                            <th>Min Price</th>
                            <th>Max Price</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($add_ons ?? [])): ?>
                            <tr><td colspan="8" class="text-center py-3">No add-ons found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($add_ons as $addon): ?>
                                <tr>
                                    <td><strong><?= esc($addon['addon_name']) ?></strong></td>
                                    <td><?= esc(mb_strimwidth((string) ($addon['description'] ?? ''), 0, 60, '…')) ?></td>
                                    <td>₱<?= number_format((float) ($addon['base_price'] ?? 0), 2) ?></td>
                                    <td><?= $addon['min_price'] !== null ? '₱' . number_format((float) $addon['min_price'], 2) : '-' ?></td>
                                    <td><?= $addon['max_price'] !== null ? '₱' . number_format((float) $addon['max_price'], 2) : '-' ?></td>
                                    <td><?= esc(ucfirst((string) ($addon['category'] ?? '-'))) ?></td>
                                    <td>
                                        <span class="so-badge so-badge--<?= (int) ($addon['is_active'] ?? 0) === 1 ? 'green' : 'amber' ?>">
                                            <?= (int) ($addon['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="so-btn so-btn--outline so-btn--sm" onclick="soOpenAddOnModal('edit', <?= (int) $addon['addon_id'] ?>, '<?= esc(addslashes($addon['addon_name'])) ?>', '<?= esc(addslashes((string) ($addon['description'] ?? ''))) ?>', '<?= (float) ($addon['base_price'] ?? 0) ?>', '<?= $addon['min_price'] !== null ? (float) $addon['min_price'] : '' ?>', '<?= $addon['max_price'] !== null ? (float) $addon['max_price'] : '' ?>', '<?= esc($addon['category'] ?? 'optional') ?>', <?= (int) ($addon['is_active'] ?? 1) ?>)">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </button>
                                        <form method="post" action="<?= site_url('/branch-admin/packages/addons/delete/' . (int) $addon['addon_id']) ?>" class="d-inline" onsubmit="return confirm('Delete this add-on?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="so-btn so-btn--danger so-btn--sm">
                                                <i class="mdi mdi-delete-outline"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Service Rates                                               -->
    <!-- ================================================================ -->
    <?php if ($activeTab === 'rates'): ?>
        <div class="so-filter-bar">
            <div class="so-search">
                <i class="mdi mdi-magnify"></i>
                <input type="text" id="so-rate-search" placeholder="Search" oninput="soFilterRates()">
            </div>
            <select class="so-select" id="so-rate-service" onchange="soFilterRates()">
                <option value="">All Services</option>
                <?php foreach (($services ?? []) as $svc): ?>
                    <option value="<?= (int) $svc['service_list_id'] ?>"><?= esc($svc['service_name'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;" id="so-rate-list">
            <?php if (empty($services ?? [])): ?>
                <div class="so-empty">
                    <i class="mdi mdi-wrench-outline"></i>
                    No services found.
                </div>
            <?php else: ?>
                <?php foreach ($services as $svc):
                    $rates = $svc['rates'] ?? [];
                    $svcStatus = strtolower((string) ($svc['status'] ?? 'active'));
                ?>
                <div class="so-svc-card"
                     data-name="<?= esc(strtolower((string) ($svc['service_name'] ?? ''))) ?>"
                     data-status="<?= esc($svcStatus) ?>"
                     data-service="<?= (int) $svc['service_list_id'] ?>">
                    <div class="so-svc-card__icon"><i class="mdi mdi-wrench-outline"></i></div>
                    <div class="so-svc-card__body">
                        <div class="so-svc-card__name"><?= esc((string) ($svc['service_name'] ?? 'Service')) ?></div>
                        <div class="so-svc-card__meta">
                            <span class="so-badge so-badge--<?= $svcStatus === 'active' ? 'green' : 'amber' ?>"><?= $svcStatus === 'active' ? 'Active' : 'Inactive' ?></span>
                        </div>
                    </div>
                    <div class="so-svc-card__price">
                        <button type="button" class="so-btn so-btn--purple so-btn--sm" onclick="soOpenRateModal('add', <?= (int) $svc['service_list_id'] ?>)">
                            <i class="mdi mdi-plus"></i> Add Rate
                        </button>
                    </div>
                    <div class="so-svc-card__actions" style="width:100%;">
                        <?php if (empty($rates)): ?>
                            <div class="so-empty" style="padding:8px 0;margin:0;">No rates configured.</div>
                        <?php else: ?>
                            <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
                                <table class="so-table mb-0" style="font-size:0.85rem;">
                                    <thead><tr><th>Origin</th><th>Destination</th><th>Rate</th><th>Status</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($rates as $rate): ?>
                                            <tr>
                                                <td><?= esc($rate['origin']) ?></td>
                                                <td><?= esc($rate['destination']) ?></td>
                                                <td>₱<?= number_format((float) ($rate['rate'] ?? 0), 2) ?></td>
                                                <td>
                                                    <span class="so-badge so-badge--<?= (int) ($rate['is_active'] ?? 0) === 1 ? 'green' : 'amber' ?>">
                                                        <?= (int) ($rate['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="so-btn so-btn--outline so-btn--sm" onclick="soOpenRateModal('edit', <?= (int) $rate['rate_id'] ?>, <?= (int) $svc['service_list_id'] ?>, '<?= esc(addslashes($rate['origin'])) ?>', '<?= esc(addslashes($rate['destination'])) ?>', '<?= (float) ($rate['rate'] ?? 0) ?>', '<?= esc(addslashes((string) ($rate['description'] ?? ''))) ?>', <?= (int) ($rate['is_active'] ?? 1) ?>)">
                                                        <i class="mdi mdi-pencil-outline"></i>
                                                    </button>
                                                    <form method="post" action="<?= site_url('/branch-admin/packages/rates/delete/' . (int) $rate['rate_id']) ?>" class="d-inline" onsubmit="return confirm('Delete this rate?');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="so-btn so-btn--danger so-btn--sm">
                                                            <i class="mdi mdi-delete-outline"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Requests (Approval Queue)                                   -->
    <!-- ================================================================ -->
    <?php if ($activeTab === 'requests'): ?>
        <div class="so-card">
            <div class="so-table-wrap">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_packages ?? [])): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="so-empty">
                                        <i class="mdi mdi-clipboard-check-outline"></i>
                                        No package requests found.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_packages as $row):
                                $itemStatus = $row['status'] ?? 'pending';
                            ?>
                            <tr>
                                <td><strong><?= esc((string) ($row['package_name'] ?? '-')) ?></strong></td>
                                <td><?= esc(mb_strimwidth((string) ($row['description'] ?? '-'), 0, 60, '…')) ?></td>
                                <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: 'Unknown') ?></td>
                                <td><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                                <td>
                                    <span class="so-badge so-badge--<?= $itemStatus === 'pending' ? 'amber' : 'green' ?>">
                                        <?= esc(strtoupper($itemStatus)) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ============ Add-on Modal ============ -->
<div class="so-modal-overlay" id="addonModalOverlay" onclick="soCloseAddOnModal()"></div>
<div class="so-modal" id="addonModal">
    <div class="so-modal__header">
        <h3 class="so-modal__title" id="addonModalTitle">Add Add-on</h3>
        <button class="so-modal__close" onclick="soCloseAddOnModal()"><i class="mdi mdi-close"></i></button>
    </div>
    <form method="post" action="<?= site_url('/branch-admin/packages/addons/add') ?>" id="addonForm">
        <?= csrf_field() ?>
        <input type="hidden" name="addon_id" id="addonId">
        <div class="so-modal__body">
            <div class="so-form-group">
                <label class="so-form-label">Add-on Name *</label>
                <input class="so-form-input" name="addon_name" id="addonName" type="text" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description" id="addonDesc"></textarea>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="so-form-label">Base Price *</label>
                    <input class="so-form-input" name="base_price" id="addonBasePrice" type="number" step="0.01" min="0.01" required>
                </div>
                <div class="col-md-4">
                    <label class="so-form-label">Min Price</label>
                    <input class="so-form-input" name="min_price" id="addonMinPrice" type="number" step="0.01" min="0" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="so-form-label">Max Price</label>
                    <input class="so-form-input" name="max_price" id="addonMaxPrice" type="number" step="0.01" min="0" placeholder="Optional">
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="so-form-label">Category *</label>
                    <select class="so-form-select" name="category" id="addonCategory" required>
                        <option value="optional">Optional</option>
                        <option value="standard">Standard</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="so-form-label">Status</label>
                    <select class="so-form-select" name="is_active" id="addonActive">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="so-modal__footer">
            <button type="button" class="so-btn so-btn--outline" onclick="soCloseAddOnModal()">Cancel</button>
            <button type="submit" class="so-btn so-btn--purple">Save Add-on</button>
        </div>
    </form>
</div>

<!-- ============ Rate Modal ============ -->
<div class="so-modal-overlay" id="rateModalOverlay" onclick="soCloseRateModal()"></div>
<div class="so-modal" id="rateModal">
    <div class="so-modal__header">
        <h3 class="so-modal__title" id="rateModalTitle">Add Rate</h3>
        <button class="so-modal__close" onclick="soCloseRateModal()"><i class="mdi mdi-close"></i></button>
    </div>
    <form method="post" action="<?= site_url('/branch-admin/packages/rates/add/') ?>" id="rateForm">
        <?= csrf_field() ?>
        <input type="hidden" name="rate_id" id="rateId">
        <input type="hidden" name="service_list_id" id="rateServiceId">
        <div class="so-modal__body">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="so-form-label">Origin *</label>
                    <input class="so-form-input" name="origin" id="rateOrigin" type="text" required>
                </div>
                <div class="col-md-6">
                    <label class="so-form-label">Destination *</label>
                    <input class="so-form-input" name="destination" id="rateDestination" type="text" required>
                </div>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Rate (₱) *</label>
                <input class="so-form-input" name="rate" id="rateValue" type="number" step="0.01" min="0.01" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description" id="rateDesc"></textarea>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Status</label>
                <select class="so-form-select" name="is_active" id="rateActive">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="so-modal__footer">
            <button type="button" class="so-btn so-btn--outline" onclick="soCloseRateModal()">Cancel</button>
            <button type="submit" class="so-btn so-btn--purple">Save Rate</button>
        </div>
    </form>
</div>

<!-- ====== Create Package Slide Panel ====== -->
<div class="so-panel-overlay" id="so-panel-overlay" onclick="soClosePanel()"></div>
<div class="so-panel" id="so-create-package">
    <div class="so-panel__header">
        <h2 class="so-panel__title">Create Package</h2>
        <button class="so-panel__close" onclick="soClosePanel()"><i class="mdi mdi-close"></i></button>
    </div>
    <div class="so-panel__body">
        <form method="post" action="<?= site_url('branch-admin/packages/store') ?>" id="so-pkg-form">
            <?= csrf_field() ?>
            <div class="so-form-group">
                <label class="so-form-label">Package Name</label>
                <input class="so-form-input" name="package_name" type="text" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Base Price</label>
                <input class="so-form-input" name="base_price" type="number" step="0.01" min="0" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Customizable</label>
                <select class="so-form-select" name="is_customizable" required>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Initial Effective Date</label>
                <input class="so-form-input" name="initial_effective_date" type="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description"></textarea>
            </div>
        </form>
    </div>
    <div class="so-panel__footer">
        <button type="submit" form="so-pkg-form" class="so-btn so-btn--purple" style="width:100%;justify-content:center;">
            <i class="mdi mdi-plus"></i> Create New Package
        </button>
    </div>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    /* --- Panel --- */
    window.soOpenPanel = function (id) {
        document.getElementById('so-panel-overlay').classList.add('show');
        document.getElementById(id).classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.soClosePanel = function () {
        document.querySelectorAll('.so-panel.show').forEach(function (p) { p.classList.remove('show'); });
        document.getElementById('so-panel-overlay').classList.remove('show');
        document.body.style.overflow = '';
    };

    /* --- Dropdowns --- */
    window.soToggleDropdown = function (btn) {
        var menu = btn.nextElementSibling;
        var isOpen = menu.classList.contains('show');
        soCloseAllDropdowns();
        if (!isOpen) menu.classList.add('show');
    };

    window.soCloseAllDropdowns = function () {
        document.querySelectorAll('.so-dropdown__menu.show').forEach(function (m) { m.classList.remove('show'); });
    };

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.so-dropdown')) soCloseAllDropdowns();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { soClosePanel(); soCloseAllDropdowns(); }
    });

    /* --- Client-side filters --- */
    window.soFilterPackages = function () {
        var search = (document.getElementById('so-pkg-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var status = (document.getElementById('so-pkg-status') || {}).value || '';
        var sort = (document.getElementById('so-pkg-sort') || {}).value || '';
        var cards = document.querySelectorAll('#so-pkg-grid .so-pkg-card');
        var visible = [];

        cards.forEach(function (card) {
            var matchSearch = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            var matchStatus = !status || card.getAttribute('data-status') === status;
            if (matchSearch && matchStatus) {
                card.style.display = '';
                visible.push(card);
            } else {
                card.style.display = 'none';
            }
        });

        if (sort) {
            visible.sort(function (a, b) {
                if (sort === 'name') return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
                if (sort === 'price-asc') return Number(a.getAttribute('data-price') || 0) - Number(b.getAttribute('data-price') || 0);
                if (sort === 'price-desc') return Number(b.getAttribute('data-price') || 0) - Number(a.getAttribute('data-price') || 0);
                return 0;
            });
            var grid = document.getElementById('so-pkg-grid');
            visible.forEach(function (c) { grid.appendChild(c); });
        }
    };

    window.soFilterServices = function () {
        var search = (document.getElementById('so-svc-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var cards = document.querySelectorAll('#so-svc-list .so-svc-card');

        cards.forEach(function (card) {
            var matchSearch = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            card.style.display = matchSearch ? '' : 'none';
        });
    };

    window.soFilterAddOns = function () {
        var search = (document.getElementById('so-addon-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var rows = document.querySelectorAll('#so-addon-table tbody tr');

        rows.forEach(function (row) {
            var name = (row.querySelector('td:first-child strong') || {}).textContent || '';
            var matchSearch = !search || name.toLowerCase().indexOf(search) !== -1;
            row.style.display = matchSearch ? '' : 'none';
        });
    };

    window.soFilterRates = function () {
        var search = (document.getElementById('so-rate-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var serviceFilter = (document.getElementById('so-rate-service') || {}).value || '';
        var cards = document.querySelectorAll('#so-rate-list .so-svc-card');

        cards.forEach(function (card) {
            var name = (card.querySelector('.so-svc-card__name') || {}).textContent || '';
            var matchSearch = !search || name.toLowerCase().indexOf(search) !== -1;
            var matchService = !serviceFilter || card.getAttribute('data-service') === serviceFilter;
            card.style.display = (matchSearch && matchService) ? '' : 'none';
        });
    };

    /* --- Add-on Modal --- */
    window.soOpenAddOnModal = function (mode, id, name, desc, basePrice, minPrice, maxPrice, category, isActive) {
        var overlay = document.getElementById('addonModalOverlay');
        var modal = document.getElementById('addonModal');
        var form = document.getElementById('addonForm');

        if (mode === 'edit') {
            document.getElementById('addonModalTitle').textContent = 'Edit Add-on';
            form.action = '<?= site_url('/branch-admin/packages/addons/update/') ?>' + id;
            document.getElementById('addonId').value = id;
            document.getElementById('addonName').value = name || '';
            document.getElementById('addonDesc').value = desc || '';
            document.getElementById('addonBasePrice').value = basePrice || '';
            document.getElementById('addonMinPrice').value = minPrice || '';
            document.getElementById('addonMaxPrice').value = maxPrice || '';
            document.getElementById('addonCategory').value = category || 'optional';
            document.getElementById('addonActive').value = isActive ? '1' : '0';
        } else {
            document.getElementById('addonModalTitle').textContent = 'Add Add-on';
            form.action = '<?= site_url('/branch-admin/packages/addons/add') ?>';
            form.reset();
            document.getElementById('addonId').value = '';
        }

        overlay.classList.add('show');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.soCloseAddOnModal = function () {
        document.getElementById('addonModalOverlay').classList.remove('show');
        document.getElementById('addonModal').classList.remove('show');
        document.body.style.overflow = '';
    };

    /* --- Rate Modal --- */
    window.soOpenRateModal = function (mode, rateId, serviceId, origin, destination, rateValue, desc, isActive) {
        var overlay = document.getElementById('rateModalOverlay');
        var modal = document.getElementById('rateModal');
        var form = document.getElementById('rateForm');

        if (mode === 'edit') {
            document.getElementById('rateModalTitle').textContent = 'Edit Rate';
            form.action = '<?= site_url('/branch-admin/packages/rates/update/') ?>' + rateId;
            document.getElementById('rateId').value = rateId;
            document.getElementById('rateServiceId').value = serviceId;
            document.getElementById('rateOrigin').value = origin || '';
            document.getElementById('rateDestination').value = destination || '';
            document.getElementById('rateValue').value = rateValue || '';
            document.getElementById('rateDesc').value = desc || '';
            document.getElementById('rateActive').value = isActive ? '1' : '0';
        } else {
            document.getElementById('rateModalTitle').textContent = 'Add Rate';
            form.action = '<?= site_url('/branch-admin/packages/rates/add/') ?>' + rateId;
            form.reset();
            document.getElementById('rateId').value = '';
            document.getElementById('rateServiceId').value = rateId;
        }

        overlay.classList.add('show');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.soCloseRateModal = function () {
        document.getElementById('rateModalOverlay').classList.remove('show');
        document.getElementById('rateModal').classList.remove('show');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.soClosePanel();
            window.soCloseAllDropdowns();
            window.soCloseAddOnModal();
            window.soCloseRateModal();
        }
    });
})();
</script>
<?= $this->endSection() ?>
