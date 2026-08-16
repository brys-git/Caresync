<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-catalog.css') ?>">

<?php
$state = (string) ($access['state'] ?? 'unregistered');
$canApply = (bool) ($can_apply ?? false);
$membership = $membership ?? [];
$monthsPaid = (int) ($membership['months_paid'] ?? 0);
$membershipState = strtolower((string) ($membership['membership_state'] ?? 'inactive'));
$isEligible = $canApply && $monthsPaid >= 2;
$activeTab = (string) ($active_tab ?? 'packages');
$services = $services ?? [];
$packages = $packages ?? [];
$totalServices = (int) ($total_services ?? count($services));
$totalPackages = (int) ($total_packages ?? count($packages));
$isDamayanMember = false;
if ((int) ($access['plan_holder']['plan_holder_id'] ?? 0) > 0) {
    $isDamayanMember = (new \App\Services\DamayanService())->isQualifiedMember((int) $access['plan_holder']['plan_holder_id']);
}
?>

<div class="sc">
    <div class="sc-container">

        <!-- ====== Page Hero ====== -->
        <div class="sc-page-hero">
            <div class="sc-page-hero__eyebrow">
                <i class="mdi mdi-package-variant-closed"></i>
                Services & Packages
            </div>
            <h1 class="sc-page-hero__title">Funeral Services & Casket Packages</h1>
            <p class="sc-page-hero__sub">Professional, compassionate care for your loved ones. Browse our Balik Probinsya transport service and premium Wood & Metal Casket packages with Damayan member benefits.</p>
        </div>

        <!-- ====== KPI Strip ====== -->
        <div class="sc-kpi-strip">
            <div class="sc-kpi">
                <div class="sc-kpi__icon"><i class="mdi mdi-file-document-outline"></i></div>
                <div>
                    <div class="sc-kpi__value"><?= $totalPackages ?></div>
                    <div class="sc-kpi__label">Packages Available</div>
                </div>
            </div>
            <div class="sc-kpi">
                <div class="sc-kpi__icon"><i class="mdi mdi-wrench-outline"></i></div>
                <div>
                    <div class="sc-kpi__value"><?= $totalServices ?></div>
                    <div class="sc-kpi__label">Services Available</div>
                </div>
            </div>
            <div class="sc-kpi">
                <div class="sc-kpi__icon"><i class="mdi mdi-truck-outline"></i></div>
                <div>
                    <div class="sc-kpi__value">2</div>
                    <div class="sc-kpi__label">Balik Probinsya Routes</div>
                </div>
            </div>
            <div class="sc-kpi">
                <div class="sc-kpi__icon"><i class="mdi mdi-shield-check-outline"></i></div>
                <div>
                    <div class="sc-kpi__value">₱14,500</div>
                    <div class="sc-kpi__label">Damayan Benefit Credit</div>
                </div>
            </div>
        </div>

        <!-- ====== Membership Eligibility Strip ====== -->
        <div class="sc-membership">
            <div class="sc-membership__left">
                <div class="sc-membership__icon <?= $isEligible ? '' : 'sc-membership__icon--locked' ?>">
                    <i class="mdi <?= $isEligible ? 'mdi-shield-check-outline' : 'mdi-shield-lock-outline' ?>"></i>
                </div>
                <div>
                    <div class="sc-membership__title">Eligibility Status</div>
                    <div class="sc-membership__meta">
                        Membership: <strong><?= ucfirst($membershipState) ?></strong> ·
                        Months paid: <strong><?= $monthsPaid ?> / 2 minimum</strong>
                        <?= $isDamayanMember ? ' · <span class="sc-badge sc-badge--green" style="margin-left:8px;">Damayan Qualified</span>' : '' ?>
                    </div>
                </div>
            </div>
            <div>
                <?php if ($isEligible): ?>
                    <span class="sc-badge sc-badge--green" style="font-size:0.82rem;">✓ Eligible to Apply</span>
                <?php else: ?>
                    <span class="sc-badge sc-badge--amber" style="font-size:0.82rem;">✗ Not Yet Eligible</span>
                    <div style="font-size:0.76rem;color:var(--sc-ink-faint);margin-top:6px;text-align:right;">
                        Complete <?= max(0, 2 - $monthsPaid) ?> more month(s) of contributions to unlock applications.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====== Flash Messages ====== -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="sc-alert sc-alert--error">
                <i class="mdi mdi-alert-circle-outline"></i>
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="sc-alert sc-alert--success">
                <i class="mdi mdi-check-circle-outline"></i>
                <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>

        <!-- ====== Tabs ====== -->
        <div class="sc-tabs" role="tablist">
            <a href="<?= site_url('/client/service?tab=packages') ?>"
               class="sc-tab <?= $activeTab === 'packages' ? 'sc-tab--active' : '' ?>">
                Packages
                <span class="sc-tab__count"><?= $totalPackages ?></span>
            </a>
            <a href="<?= site_url('/client/service?tab=services') ?>"
               class="sc-tab <?= $activeTab === 'services' ? 'sc-tab--active' : '' ?>">
                Services
                <span class="sc-tab__count"><?= $totalServices ?></span>
            </a>
        </div>

        <!-- ================================================================ -->
        <!-- TAB: Packages                                                    -->
        <!-- ================================================================ -->
        <?php if ($activeTab === 'packages'): ?>

            <!-- Filter bar -->
            <div class="sc-filter-bar">
                <div class="sc-search">
                    <i class="mdi mdi-magnify"></i>
                    <input type="text" id="sc-pkg-search" placeholder="Search packages..." oninput="scFilterPackages()">
                </div>
                <select class="sc-select" id="sc-pkg-sort" onchange="scFilterPackages()">
                    <option value="">Sort by</option>
                    <option value="name">Name</option>
                    <option value="price-asc">Price: Low → High</option>
                    <option value="price-desc">Price: High → Low</option>
                </select>
            </div>

            <!-- Package cards grid -->
            <div class="sc-section-head">
                <div>
                    <h2 class="sc-section-head__title">Casket Packages</h2>
                    <p class="sc-section-head__sub">Premium wood and metal caskets with customizable variants and optional burial attire add-on</p>
                </div>
            </div>

            <div class="sc-grid" id="sc-pkg-grid">
                <?php if (empty($packages)): ?>
                    <div class="sc-empty" style="grid-column: 1 / -1;">
                        <i class="mdi mdi-package-variant-closed"></i>
                        <div class="sc-empty__text">No packages found.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($packages as $pkg):
                        $pkgStatus = strtolower((string) ($pkg['status'] ?? (($pkg['is_available'] ?? 1) == 1 ? 'active' : 'inactive')));
                        $desc = (string) ($pkg['description'] ?? '');
                        $descLines = array_filter(array_map('trim', explode("\n", $desc)));
                        $colorIdx = ((int) ($pkg['package_id'] ?? 0)) % 3;
                        $iconMap = [
                            0 => 'mdi-coffin',
                            1 => 'mdi-package-variant-closed',
                            2 => 'mdi-box'
                        ];
                        $ribbonMap = [
                            0 => ['Wood Casket', 'sc-card__ribbon--gold'],
                            1 => ['Metal Casket', 'sc-card__ribbon--gold'],
                            2 => ['Premium', 'sc-card__ribbon--green']
                        ];
                        $ribbonLabel = $ribbonMap[$colorIdx][0] ?? 'Package';
                        $ribbonClass = $ribbonMap[$colorIdx][1] ?? 'sc-card__ribbon';
                        // Pick appropriate product image
                        $pkgName = strtolower((string) ($pkg['package_name'] ?? ''));
                        $imgFile = strpos($pkgName, 'wood') !== false ? 'product-1.png' : (strpos($pkgName, 'metal') !== false ? 'product-3.png' : 'product-1.png');
                    ?>
                    <div class="sc-card"
                         data-name="<?= esc(strtolower((string) ($pkg['package_name'] ?? ''))) ?>"
                         data-status="<?= esc($pkgStatus) ?>"
                         data-price="<?= (float) ($pkg['base_price'] ?? 0) ?>">
                        <div class="sc-card__media">
                            <img src="<?= base_url('assets/images/' . $imgFile) ?>" alt="<?= esc((string) ($pkg['package_name'] ?? 'Package')) ?>" loading="lazy">
                            <span class="sc-card__ribbon <?= $ribbonClass ?>"><?= $ribbonLabel ?></span>
                        </div>
                        <div class="sc-card__body">
                            <div class="sc-card__cat">Casket Package</div>
                            <h3 class="sc-card__name"><?= esc((string) ($pkg['package_name'] ?? 'Package')) ?></h3>
                            <div class="sc-card__price">₱<?= esc(number_format((float) ($pkg['base_price'] ?? 0), 2)) ?></div>
                            <p class="sc-card__desc"><?= esc(mb_strimwidth($desc, 0, 140, '…')) ?></p>
                            <ul class="sc-card__features">
                                <li>Multiple variants available</li>
                                <li>8 standard inclusions</li>
                                <li>Optional burial attire add-on</li>
                                <li>Damayan benefit eligible</li>
                            </ul>
                            <div class="sc-card__footer">
                                <a href="<?= site_url('/client/package/' . (int) $pkg['package_id']) ?>" class="sc-btn sc-btn--outline sc-btn--sm">View Details</a>
                                <?php if ($isEligible && $pkgStatus === 'active'): ?>
                                    <a href="<?= site_url('/client/apply-package/' . (int) $pkg['package_id']) ?>" class="sc-btn sc-btn--primary sc-btn--sm">Apply Now</a>
                                <?php endif; ?>
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

            <!-- Filter bar -->
            <div class="sc-filter-bar">
                <div class="sc-search">
                    <i class="mdi mdi-magnify"></i>
                    <input type="text" id="sc-svc-search" placeholder="Search services..." oninput="scFilterServices()">
                </div>
            </div>

            <div class="sc-section-head">
                <div>
                    <h2 class="sc-section-head__title">Additional Services</h2>
                    <p class="sc-section-head__sub">Specialized transport and care services for your loved ones</p>
                </div>
            </div>

            <div class="sc-svc-list" id="sc-svc-list">
                <?php if (empty($services)): ?>
                    <div class="sc-empty">
                        <i class="mdi mdi-wrench-outline"></i>
                        <div class="sc-empty__text">No services found.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($services as $svc):
                        $svcStatus = strtolower((string) ($svc['status'] ?? (($svc['is_available'] ?? 1) == 1 ? 'active' : 'inactive')));
                        $svcAvailable = $svcStatus === 'active';
                        $svcName = strtolower((string) ($svc['service_name'] ?? ''));
                        $icon = strpos($svcName, 'balik') !== false || strpos($svcName, 'probinsya') !== false
                            ? 'mdi-truck-outline' : 'mdi-wrench-outline';
                        $gradient = strpos($svcName, 'balik') !== false || strpos($svcName, 'probinsya') !== false
                            ? 'linear-gradient(135deg, #c2760a 0%, #d4a843 100%)'
                            : 'linear-gradient(135deg, #1e3a5f 0%, #3182ce 100%)';
                    ?>
                    <div class="sc-svc-card"
                         data-name="<?= esc(strtolower((string) ($svc['service_name'] ?? ''))) ?>"
                         data-status="<?= esc($svcStatus) ?>">
                        <div class="sc-svc-card__icon" style="background: <?= $gradient ?>;">
                            <i class="mdi <?= $icon ?>"></i>
                        </div>
                        <div class="sc-svc-card__body">
                            <div class="sc-svc-card__name"><?= esc((string) ($svc['service_name'] ?? 'Service')) ?></div>
                            <div class="sc-svc-card__meta">
                                <span class="sc-badge sc-badge--<?= $svcStatus === 'active' ? 'green' : 'amber' ?>"><?= $svcStatus === 'active' ? 'Active' : 'Inactive' ?></span>
                                <?php if (! empty($svc['description'])): ?>
                                    — <?= esc(mb_strimwidth((string) $svc['description'], 0, 100, '…')) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="sc-svc-card__price">₱<?= esc(number_format((float) ($svc['base_price'] ?? 0), 2)) ?></div>
                        <div class="sc-svc-card__actions">
                            <a href="<?= site_url('/client/service/' . (int) $svc['service_list_id']) ?>" class="sc-btn sc-btn--outline sc-btn--sm">View Details</a>
                            <?php if ($isEligible && $svcAvailable): ?>
                                <a href="<?= site_url('/client/apply-service/' . (int) $svc['service_list_id']) ?>" class="sc-btn sc-btn--primary sc-btn--sm">Apply</a>
                            <?php else: ?>
                                <span class="sc-btn sc-btn--outline sc-btn--sm" style="opacity:.5;cursor:not-allowed;" title="Complete 2 months of contributions to apply">Apply</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <!-- ================================================================ -->
        <!-- My Applications                                                  -->
        <!-- ================================================================ -->
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
                        ->limit(10)
                        ->get()
                        ->getResultArray();
                }
            }
        ?>

        <?php if (! empty($myApplications)): ?>
        <div class="sc-block" style="margin-top: 8px;">
            <div class="sc-block__head">
                <i class="mdi mdi-history"></i>
                <div>
                    <div class="sc-block__title">My Applications</div>
                    <div class="sc-block__sub">Track the status of your recent service and package requests</div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.84rem;">
                    <thead>
                        <tr style="text-align:left;color:var(--sc-ink-faint);border-bottom:1px solid var(--sc-border);">
                            <th style="padding:12px 16px;font-size:0.7rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;">Item</th>
                            <th style="padding:12px 16px;font-size:0.7rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;">Type</th>
                            <th style="padding:12px 16px;font-size:0.7rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;">Date</th>
                            <th style="padding:12px 16px;font-size:0.7rem;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myApplications as $app):
                            $appStatus = strtolower((string) ($app['status'] ?? 'pending'));
                            $appType = ! empty($app['service_name']) ? 'Service' : 'Package';
                            $appItem = (string) ($app['service_name'] ?? $app['package_name'] ?? '-');
                        ?>
                        <tr style="border-top:1px solid var(--sc-border);">
                            <td style="padding:12px 16px;font-weight:600;"><?= esc($appItem) ?></td>
                            <td style="padding:12px 16px;"><?= esc($appType) ?></td>
                            <td style="padding:12px 16px;color:var(--sc-ink-faint);">
                                <?= esc(date('M d, Y', strtotime((string) ($app['created_at'] ?? '')))) ?>
                            </td>
                            <td style="padding:12px 16px;">
                                <?php if ($appStatus === 'rejected'): ?>
                                    <span class="sc-badge" style="background:var(--sc-red-soft);color:var(--sc-red);">
                                        <?= strtoupper(esc($appStatus)) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="sc-badge sc-badge--<?= $appStatus === 'approved' ? 'green' : 'amber' ?>">
                                        <?= strtoupper(esc($appStatus)) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($appStatus === 'rejected' && ! empty($app['rejection_reason'])): ?>
                                    <div style="font-size:0.74rem;color:var(--sc-ink-faint);margin-top:4px;"><?= esc(mb_strimwidth((string) $app['rejection_reason'], 0, 60, '…')) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
(function() {
    /* --- Client-side filters --- */
    window.scFilterPackages = function() {
        var search = (document.getElementById('sc-pkg-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var sort = (document.getElementById('sc-pkg-sort') || {}).value || '';
        var cards = document.querySelectorAll('#sc-pkg-grid .sc-card');
        var visible = [];

        cards.forEach(function(card) {
            var matchSearch = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            if (matchSearch) {
                card.style.display = '';
                visible.push(card);
            } else {
                card.style.display = 'none';
            }
        });

        if (sort) {
            visible.sort(function(a, b) {
                if (sort === 'name') return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
                if (sort === 'price-asc') return Number(a.getAttribute('data-price') || 0) - Number(b.getAttribute('data-price') || 0);
                if (sort === 'price-desc') return Number(b.getAttribute('data-price') || 0) - Number(a.getAttribute('data-price') || 0);
                return 0;
            });
            var grid = document.getElementById('sc-pkg-grid');
            visible.forEach(function(card) { grid.appendChild(card); });
        }
    };

    window.scFilterServices = function() {
        var search = (document.getElementById('sc-svc-search') || {}).value || '';
        search = search.toLowerCase().trim();
        var cards = document.querySelectorAll('#sc-svc-list .sc-svc-card');

        cards.forEach(function(card) {
            var match = !search || (card.getAttribute('data-name') || '').indexOf(search) !== -1;
            card.style.display = match ? '' : 'none';
        });
    };
})();
</script>
<?= $this->endSection() ?>