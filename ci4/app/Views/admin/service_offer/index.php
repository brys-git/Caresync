<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">

<?php
    $totalPending = (int) ($pending_service_count ?? 0) + (int) ($pending_package_count ?? 0);
    $totalApproved = (int) ($approved_service_count ?? 0) + (int) ($approved_package_count ?? 0);
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
        <a href="<?= site_url('/admin/service-offer?tab=packages') ?>" class="so-tab <?= ($tab ?? '') === 'packages' ? 'so-tab--active' : '' ?>">
            Packages
            <span class="so-tab__badge so-tab__badge--green">Active</span>
        </a>
        <a href="<?= site_url('/admin/service-offer?tab=services') ?>" class="so-tab <?= ($tab ?? '') === 'services' ? 'so-tab--active' : '' ?>">
            Services
        </a>
        <a href="<?= site_url('/admin/service-offer?tab=approval') ?>" class="so-tab <?= ($tab ?? '') === 'approval' ? 'so-tab--active' : '' ?>">
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
    <?php if (($tab ?? '') === 'packages'): ?>

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
                    $pkgStatus = 'active';
                    $pkgStatus = strtolower((string) ($pkg['status'] ?? ($pkg['is_available'] ?? 1) == 1 ? 'active' : 'inactive'));
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
                        <button class="so-btn so-btn--outline so-btn--sm" onclick="soOpenPanel('create-package')">Edit</button>
                        <div class="so-dropdown">
                            <button class="so-btn so-btn--ghost so-btn--sm" onclick="soToggleDropdown(this)" style="cursor:pointer;">
                                <i class="mdi mdi-dots-horizontal"></i>
                            </button>
                            <div class="so-dropdown__menu">
                                <button class="so-dropdown__item" onclick="soCloseAllDropdowns()"><i class="mdi mdi-eye-outline"></i> View</button>
                                <button class="so-dropdown__item" onclick="soCloseAllDropdowns()"><i class="mdi mdi-pencil-outline"></i> Edit</button>
                                <button class="so-dropdown__item" onclick="soCloseAllDropdowns()"><i class="mdi mdi-trash-can-outline" style="color:var(--so-red);"></i> Delete</button>
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
    <?php if (($tab ?? '') === 'services'): ?>

        <!-- Filter bar -->
        <div class="so-filter-bar">
            <div class="so-search">
                <i class="mdi mdi-magnify"></i>
                <input type="text" id="so-svc-search" placeholder="Search" oninput="soFilterServices()">
            </div>
            <select class="so-select" id="so-svc-status" onchange="soFilterServices()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select class="so-select" id="so-svc-sort" onchange="soFilterServices()">
                <option value="">Sort by</option>
                <option value="name">Name</option>
                <option value="price-asc">Price: Low → High</option>
                <option value="price-desc">Price: High → Low</option>
            </select>
            <button type="button" class="so-btn so-btn--purple" onclick="soOpenPanel('create-service')">
                <i class="mdi mdi-plus"></i> Create Service
            </button>
        </div>

        <!-- Service cards -->
        <div style="display:flex;flex-direction:column;gap:12px;" id="so-svc-list">
            <?php if (empty($services ?? [])): ?>
                <div class="so-empty">
                    <i class="mdi mdi-wrench-outline"></i>
                    No services found. Click "Create Service" to add one.
                </div>
            <?php else: ?>
                <?php foreach ($services as $svc):
                    $svcStatus = strtolower((string) ($svc['status'] ?? ($svc['is_available'] ?? 1) == 1 ? 'active' : 'inactive'));
                ?>
                <div class="so-svc-card"
                     data-name="<?= esc(strtolower((string) ($svc['service_name'] ?? ''))) ?>"
                     data-status="<?= esc($svcStatus) ?>"
                     data-price="<?= (float) ($svc['base_price'] ?? 0) ?>">
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
                        <button class="so-btn so-btn--outline so-btn--sm" onclick="soOpenPanel('create-service')">Edit</button>
                        <div class="so-dropdown">
                            <button class="so-btn so-btn--ghost so-btn--sm" onclick="soToggleDropdown(this)" style="cursor:pointer;">
                                <i class="mdi mdi-dots-horizontal"></i>
                            </button>
                            <div class="so-dropdown__menu">
                                <button class="so-dropdown__item" onclick="soCloseAllDropdowns()"><i class="mdi mdi-eye-outline"></i> View</button>
                                <button class="so-dropdown__item" onclick="soCloseAllDropdowns()"><i class="mdi mdi-pencil-outline"></i> Edit</button>
                                <button class="so-dropdown__item" onclick="soCloseAllDropdowns()"><i class="mdi mdi-trash-can-outline" style="color:var(--so-red);"></i> Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TAB: Approval Queue                                              -->
    <!-- ================================================================ -->
    <?php if (($tab ?? '') === 'approval'): ?>
        <div class="so-card">
            <div class="so-subtabs">
                <a href="<?= site_url('/admin/service-offer?tab=approval&approval_tab=services') ?>" class="so-subtab <?= ($approval_tab ?? '') === 'services' ? 'so-subtab--active' : '' ?>">
                    Pending
                    <?php if (($pending_service_count ?? 0) > 0): ?>
                        <span class="so-tab__badge"><?= $pending_service_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= site_url('/admin/service-offer?tab=approval&approval_tab=packages') ?>" class="so-subtab <?= ($approval_tab ?? '') === 'packages' ? 'so-subtab--active' : '' ?>">
                    Approved
                    <?php if (($approved_service_count ?? 0) + ($approved_package_count ?? 0) > 0): ?>
                        <span class="so-tab__badge so-tab__badge--green"><?= $totalApproved ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="so-table-wrap">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $items = ($approval_tab ?? '') === 'services' ? ($pending_services ?? []) : ($pending_packages ?? []);
                        ?>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="so-empty">
                                        <i class="mdi mdi-clipboard-check-outline"></i>
                                        No items found.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $row):
                                $isService = ($approval_tab ?? '') === 'services';
                                $itemId = $isService ? (int) ($row['pending_service_id'] ?? 0) : (int) ($row['pending_package_id'] ?? 0);
                                $itemName = $isService ? ($row['service_name'] ?? '-') : ($row['package_name'] ?? '-');
                                $creatorName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?: 'Unknown';
                                $itemStatus = $row['status'] ?? 'pending';
                            ?>
                            <tr>
                                <td><strong><?= esc($itemName) ?></strong></td>
                                <td><?= esc(mb_strimwidth((string) ($row['description'] ?? '-'), 0, 60, '…')) ?></td>
                                <td><?= esc($creatorName) ?></td>
                                <td><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                                <td>
                                    <?php if ($itemStatus === 'pending'): ?>
                                        <form method="post" action="<?= site_url('/admin/service-offer/approval/' . ($isService ? 'service' : 'package') . '/approve/' . $itemId) ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="so-btn so-btn--purple so-btn--sm" style="background:var(--so-green);border-color:var(--so-green);">
                                                <i class="mdi mdi-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="post" action="<?= site_url('/admin/service-offer/approval/' . ($isService ? 'service' : 'package') . '/reject/' . $itemId) ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="so-btn so-btn--outline so-btn--sm" style="color:var(--so-red);border-color:var(--so-red);">
                                                <i class="mdi mdi-close"></i> Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="so-badge so-badge--green">Processed</span>
                                    <?php endif; ?>
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

<!-- ====== Create Package Slide Panel ====== -->
<div class="so-panel-overlay" id="so-panel-overlay" onclick="soClosePanel()"></div>
<div class="so-panel" id="so-create-package">
    <div class="so-panel__header">
        <h2 class="so-panel__title">Create Package</h2>
        <button class="so-panel__close" onclick="soClosePanel()"><i class="mdi mdi-close"></i></button>
    </div>
    <div class="so-panel__body">
        <form method="post" action="<?= site_url('admin/service-offer/package/store') ?>" id="so-pkg-form">
            <?= csrf_field() ?>
            <div class="so-form-group">
                <label class="so-form-label">Package Name</label>
                <input class="so-form-input" name="package_name" type="text" value="<?= esc(old('package_name')) ?>" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Base Price</label>
                <input class="so-form-input" name="base_price" type="number" step="0.01" min="0" value="<?= esc(old('base_price')) ?>" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Customizable</label>
                <select class="so-form-select" name="is_customizable" required>
                    <option value="1" <?= old('is_customizable', '1') === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= old('is_customizable') === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Initial Effective Date</label>
                <input class="so-form-input" name="initial_effective_date" type="date" value="<?= esc(old('initial_effective_date', date('Y-m-d'))) ?>" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Initial Version Status</label>
                <select class="so-form-select" name="initial_version_status" required>
                    <option value="active" <?= old('initial_version_status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= old('initial_version_status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description"><?= esc(old('description')) ?></textarea>
            </div>
        </form>
    </div>
    <div class="so-panel__footer">
        <button type="submit" form="so-pkg-form" class="so-btn so-btn--purple" style="width:100%;justify-content:center;">
            <i class="mdi mdi-plus"></i> Create New Package
        </button>
    </div>
</div>

<!-- ====== Create Service Slide Panel ====== -->
<div class="so-panel" id="so-create-service">
    <div class="so-panel__header">
        <h2 class="so-panel__title">Create Service</h2>
        <button class="so-panel__close" onclick="soClosePanel()"><i class="mdi mdi-close"></i></button>
    </div>
    <div class="so-panel__body">
        <form method="post" action="<?= site_url('admin/service-offer/service/store') ?>" id="so-svc-form">
            <?= csrf_field() ?>
            <div class="so-form-group">
                <label class="so-form-label">Service Name</label>
                <input class="so-form-input" name="service_name" type="text" value="<?= esc(old('service_name')) ?>" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Base Price</label>
                <input class="so-form-input" name="base_price" type="number" step="0.01" min="0" value="<?= esc(old('base_price')) ?>" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Status</label>
                <select class="so-form-select" name="status" required>
                    <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description"><?= esc(old('description')) ?></textarea>
            </div>
        </form>
    </div>
    <div class="so-panel__footer">
        <button type="submit" form="so-svc-form" class="so-btn so-btn--purple" style="width:100%;justify-content:center;">
            <i class="mdi mdi-plus"></i> Create New Service
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
        var status = (document.getElementById('so-svc-status') || {}).value || '';
        var cards = document.querySelectorAll('#so-svc-list .so-svc-card');

        cards.forEach(function (card) {
            var matchSearch = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            var matchStatus = !status || card.getAttribute('data-status') === status;
            card.style.display = (matchSearch && matchStatus) ? '' : 'none';
        });
    };
})();
</script>
<?= $this->endSection() ?>
