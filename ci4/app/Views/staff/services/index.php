<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">

<?php
    $activeTab = $active_tab ?? 'services';
?>

<div class="so">

    <!-- ====== KPI Bar ====== -->
    <div class="so-kpi-bar">
        <div class="so-kpi-item">Total Packages: <strong><?= (int) ($total_packages ?? 0) ?></strong></div>
        <div class="so-kpi-item">Total Services: <strong><?= (int) ($total_services ?? 0) ?></strong></div>
        <div class="so-kpi-item">Branch: <strong><?= esc(session('branch_name') ?? 'Current Branch') ?></strong></div>
    </div>

    <!-- ====== Header ====== -->
    <div>
        <h1 class="so-header__title">Services</h1>
        <p class="so-header__sub">View services and packages available in your branch.</p>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (! empty($branch_issue)): ?>
        <div class="so-alert so-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc($branch_issue) ?>
        </div>
    <?php endif; ?>
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
        <a href="<?= site_url('/staff/services?tab=services') ?>" class="so-tab <?= $activeTab === 'services' ? 'so-tab--active' : '' ?>">
            Services
        </a>
        <a href="<?= site_url('/staff/services?tab=packages') ?>" class="so-tab <?= $activeTab === 'packages' ? 'so-tab--active' : '' ?>">
            Packages
        </a>
        <a href="<?= site_url('/staff/services/requests') ?>" class="so-tab">
            Service Requests
        </a>
        <a href="<?= site_url('/staff/services/ongoing') ?>" class="so-tab">
            Ongoing Services
        </a>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Services                                                    -->
    <!-- ================================================================ -->
    <?php if ($activeTab === 'services'): ?>

        <!-- Filter bar -->
        <div class="so-filter-bar">
            <div class="so-search">
                <i class="mdi mdi-magnify"></i>
                <input type="text" id="so-svc-search" placeholder="Search" oninput="soFilterServices()">
            </div>
        </div>

        <!-- Service cards -->
        <div style="display:flex;flex-direction:column;gap:12px;" id="so-svc-list">
            <?php if (empty($services ?? [])): ?>
                <div class="so-empty">
                    <i class="mdi mdi-wrench-outline"></i>
                    No services found for your branch.
                </div>
            <?php else: ?>
                <?php foreach ($services as $svc):
                    $svcStatus = strtolower((string) ($svc['status'] ?? 'scheduled'));
                    $clientName = esc(trim((string) (($svc['first_name'] ?? '') . ' ' . ($svc['last_name'] ?? ''))));
                    $assignedToMe = (int) ($svc['assigned_staff'] ?? 0) === (int) session()->get('user_id');
                ?>
                <div class="so-svc-card"
                     data-name="<?= esc(strtolower((string) ($svc['service_name'] ?? ''))) ?>"
                     data-status="<?= esc($svcStatus) ?>">
                    <div class="so-svc-card__icon"><i class="mdi mdi-wrench-outline"></i></div>
                    <div class="so-svc-card__body">
                        <div class="so-svc-card__name"><?= esc((string) ($svc['service_name'] ?? 'Service')) ?></div>
                        <div class="so-svc-card__meta">
                            <span class="so-badge so-badge--<?= $svcStatus === 'completed' ? 'green' : ($svcStatus === 'ongoing' ? 'green' : ($svcStatus === 'pending' ? 'amber' : 'slate')) ?>">
                                <?= esc(ucfirst($svcStatus)) ?>
                            </span>
                            — <?= esc($clientName) ?>
                            <?php if ($assignedToMe): ?>
                                <span class="so-badge so-badge--teal" style="margin-left:4px;">Assigned to you</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.78rem;color:var(--so-ink-faint);"><?= esc((string) ($svc['service_date'] ?? '-')) ?></div>
                        <div style="font-size:0.82rem;font-weight:700;color:var(--so-ink);"><?= esc((string) ($svc['package_name'] ?? '-')) ?></div>
                    </div>
                    <div class="so-svc-card__actions">
                        <a href="<?= site_url('/staff/services?tab=services&service_id=' . (int) ($svc['service_id'] ?? 0)) ?>" class="so-btn so-btn--outline so-btn--sm">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (! empty($selected_service)): ?>
            <div class="so-card" style="margin-top:16px;">
                <div class="so-card__header"><h3 class="so-card__title">Service Details</h3></div>
                <div class="so-card__body">
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Plan Holder</span><span class="bd-stat-row__value"><?= esc(trim((string) (($selected_service['first_name'] ?? '') . ' ' . ($selected_service['last_name'] ?? '')))) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Service</span><span class="bd-stat-row__value"><?= esc((string) ($selected_service['service_name'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Package</span><span class="bd-stat-row__value"><?= esc((string) ($selected_service['package_name'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Date</span><span class="bd-stat-row__value"><?= esc((string) ($selected_service['service_date'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Status</span><span class="bd-stat-row__value"><?= esc(ucfirst((string) ($selected_service['status'] ?? '-'))) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Assigned Staff</span><span class="bd-stat-row__value"><?= ((int) ($selected_service['assigned_staff'] ?? 0) === (int) session()->get('user_id') ? 'You' : esc(trim((string) (($selected_service['staff_first_name'] ?? '') . ' ' . ($selected_service['staff_last_name'] ?? ''))))) ?: '-' ?></span></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

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
        </div>

        <!-- Package cards grid -->
        <div class="so-grid" id="so-pkg-grid">
            <?php if (empty($packages ?? [])): ?>
                <div class="so-empty">
                    <i class="mdi mdi-package-variant-closed"></i>
                    No packages found.
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
                        <a href="<?= site_url('/staff/services?tab=packages&package_id=' . (int) $pkg['package_id']) ?>" class="so-btn so-btn--outline so-btn--sm">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (! empty($selected_package)): ?>
            <div class="so-card" style="margin-top:16px;">
                <div class="so-card__header"><h3 class="so-card__title">Package Details</h3></div>
                <div class="so-card__body">
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Package</span><span class="bd-stat-row__value"><?= esc((string) ($selected_package['package_name'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Description</span><span class="bd-stat-row__value"><?= esc((string) ($selected_package['description'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Base Price</span><span class="bd-stat-row__value">₱<?= number_format((float) ($selected_package['base_price'] ?? 0), 2) ?></span></div>
                </div>
            </div>

            <div class="so-grid" style="margin-top:16px;">
                <div class="so-card">
                    <div class="so-card__header"><h3 class="so-card__title">Services Included</h3></div>
                    <div class="so-card__body">
                        <?php $serviceRows = $selected_package_services ?? []; ?>
                        <?php if (empty($serviceRows)): ?>
                            <div class="so-empty">No services found.</div>
                        <?php else: ?>
                            <?php foreach ($serviceRows as $service): ?>
                                <div class="bd-stat-row">
                                    <span class="bd-stat-row__label"><?= esc((string) ($service['service_name'] ?? '-')) ?></span>
                                    <span class="bd-stat-row__value">₱<?= esc(number_format((float) ($service['base_price'] ?? 0), 2)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="so-card">
                    <div class="so-card__header"><h3 class="so-card__title">Price Versions</h3></div>
                    <div class="so-card__body">
                        <?php $versionRows = $selected_package_versions ?? []; ?>
                        <?php if (empty($versionRows)): ?>
                            <div class="so-empty">No versions found.</div>
                        <?php else: ?>
                            <?php foreach ($versionRows as $version): ?>
                                <div class="bd-stat-row">
                                    <span class="bd-stat-row__label"><?= esc((string) ($version['effective_date'] ?? '-')) ?></span>
                                    <span class="bd-stat-row__value">₱<?= number_format((float) ($version['price'] ?? 0), 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    window.soFilterServices = function () {
        var search = (document.getElementById('so-svc-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var cards = document.querySelectorAll('#so-svc-list .so-svc-card');

        cards.forEach(function (card) {
            var matchSearch = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            card.style.display = matchSearch ? '' : 'none';
        });
    };

    window.soFilterPackages = function () {
        var search = (document.getElementById('so-pkg-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var cards = document.querySelectorAll('#so-pkg-grid .so-pkg-card');

        cards.forEach(function (card) {
            var matchSearch = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            card.style.display = matchSearch ? '' : 'none';
        });
    };
})();
</script>
<?= $this->endSection() ?>
