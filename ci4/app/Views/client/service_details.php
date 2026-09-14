<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'new');
$canApply = (bool) ($can_apply ?? false);
$routes = $routes ?? [];
?>
<?php /* Product-hero layout - no cs-panel equivalent, same as client/package_details.php. Icons converted bi-* -> ti-*. */ ?>
<style>
    .svc-hero { border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb; background: #fff; }
    .svc-hero-image { height: 260px; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); display: flex; align-items: center; justify-content: center; }
    .svc-hero-image img { width: 100%; height: 100%; object-fit: cover; }
    .svc-hero-image .placeholder-icon { font-size: 4.5rem; color: #94a3b8; }
    .route-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.25rem; height: 100%; }
    .route-price { font-size: 1.6rem; font-weight: 700; color: #1e293b; }
    .badge-covered { background-color: #dcfce7; color: #166534; }
</style>

<div style="max-width: 960px;">
    <a class="text-decoration-none text-muted mb-3 d-inline-block" href="<?= site_url('/client/service?tab=services') ?>">
        <i class="ti ti-arrow-left me-1"></i> Back to Services
    </a>

    <?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

    <div class="svc-hero mb-4">
        <div class="svc-hero-image">
            <?php if (! empty($service['image_path'])): ?>
                <img src="<?= esc((string) $service['image_path']) ?>" alt="<?= esc((string) ($service['service_name'] ?? 'Service')) ?>" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'ti ti-truck placeholder-icon\'></i>';">
            <?php else: ?>
                <i class="ti ti-truck placeholder-icon"></i>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <h2 class="h4 mb-2"><?= esc((string) ($service['service_name'] ?? '-')) ?></h2>
            <p class="text-muted mb-0"><?= esc((string) ($service['description'] ?? 'No description available.')) ?></p>
        </div>
    </div>

    <?php if (! empty($routes)): ?>
        <h5 class="mb-3">Available Routes</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($routes as $route): ?>
                <div class="col-md-6">
                    <div class="route-card">
                        <div class="fw-semibold mb-1"><?= esc((string) $route['route_name']) ?></div>
                        <div class="route-price mb-2"><?= cs_money($route['price']) ?></div>
                        <?php if (! empty($route['casket_benefit_covered'])): ?>
                            <div class="small mb-1"><span class="badge badge-covered">Casket benefit: Covered</span></div>
                            <div class="small text-muted">Separate casket payment: <?= cs_money(0) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-muted small">The route price above is the full transport service fee. Your Damayan casket entitlement is a separate benefit and does not reduce this fee.</p>
    <?php else: ?>
        <div class="fw-semibold mb-4">Price: <?= cs_money($service['base_price'] ?? 0) ?></div>
    <?php endif; ?>

    <div class="mt-3">
        <?php if ($canApply): ?>
            <a class="btn btn-primary" href="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">Apply for Service</a>
        <?php elseif ($state === 'pending'): ?>
            <div class="alert alert-warning">Approval required before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
