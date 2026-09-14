<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $state = (string) ($access['state'] ?? 'new');
    $canApply = (bool) ($can_apply ?? false);
    $routes = $routes ?? [];
?>
<div style="max-width: 760px;">
    <div class="mb-3 text-end">
        <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)) ?>">Back</a>
    </div>

    <?php if (! $canApply): ?>
        <?php if ($state === 'pending'): ?>
            <div class="alert alert-warning">Approval required before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    <?php endif; ?>

    <section class="cs-panel mb-3">
        <div class="cs-panel__body">
            <h5 class="mb-2"><?= esc((string) ($service['service_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($service['description'] ?? 'No description available.')) ?></p>
            <?php if (empty($routes)): ?>
                <div class="fw-semibold">Price: <?= cs_money($service['base_price'] ?? 0) ?></div>
            <?php endif; ?>
        </div>
    </section>

    <form class="mt-3" method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
        <?= csrf_field() ?>

        <?php if (! empty($routes)): ?>
            <div class="mb-3">
                <label class="form-label">Select route</label>
                <?php foreach ($routes as $i => $route): ?>
                    <div class="form-check border rounded p-2 mb-2">
                        <input class="form-check-input route-option" type="radio" name="route_id" id="route-<?= (int) $route['route_id'] ?>" value="<?= (int) $route['route_id'] ?>" data-price="<?= (float) $route['price'] ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                        <label class="form-check-label d-flex justify-content-between" for="route-<?= (int) $route['route_id'] ?>">
                            <span><?= esc((string) $route['route_name']) ?></span>
                            <?= cs_money($route['price']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="alert alert-secondary d-flex justify-content-between">
                <span>Total Amount</span>
                <span class="fw-bold cs-money" id="route-total"><?= esc(number_format((float) ($routes[0]['price'] ?? 0), 2)) ?></span>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Deceased full name</label>
            <input type="text" name="deceased_name" class="form-control" value="<?= old('deceased_name') ?>" required />
        </div>
        <div class="mb-3 row">
            <div class="col-md-6">
                <label class="form-label">Date of death</label>
                <input type="date" name="deceased_date_of_death" class="form-control" value="<?= old('deceased_date_of_death') ?>" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Relationship to deceased</label>
                <input type="text" name="relationship_to_deceased" class="form-control" value="<?= old('relationship_to_deceased') ?>" />
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Deceased address</label>
            <input type="text" name="deceased_address" class="form-control" value="<?= old('deceased_address') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Beneficiary name</label>
            <input type="text" name="beneficiary_name" class="form-control" value="<?= old('beneficiary_name') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Beneficiary contact number</label>
            <input type="text" name="beneficiary_contact" class="form-control" value="<?= old('beneficiary_contact') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Upload supporting documents (IDs, death certificate)</label>
            <input type="file" name="documents[]" multiple class="form-control" accept="image/*,application/pdf" />
        </div>
        <button class="btn btn-primary" type="submit" <?= $canApply ? '' : 'disabled' ?>>Submit Application</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=services') ?>">Cancel</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var total = document.getElementById('route-total');
    if (! total) { return; }
    document.querySelectorAll('.route-option').forEach(function (input) {
        input.addEventListener('change', function () {
            var price = parseFloat(this.dataset.price || '0');
            total.textContent = price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
    });
});
</script>
<?= $this->endSection() ?>
