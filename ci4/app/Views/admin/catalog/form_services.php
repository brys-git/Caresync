<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$errors = session('errors') ?? [];
$isEdit = ! empty($item);
$val    = static fn (string $k, $d = '') => old($k, $item[$k] ?? $d);
$err    = static fn (string $k): string => isset($errors[$k]) ? '<div class="text-danger small mt-1">' . esc($errors[$k]) . '</div>' : '';
$bad    = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$action = site_url('/admin/catalog/save/services' . ($isEdit ? '/' . (int) $item['service_list_id'] : ''));
$st     = $val('availability_status', 'available');

// Route rows: re-show what was typed after a validation error, else the saved routes, else one blank row.
if (old('origin') !== null) {
    $rows = [];
    foreach ((array) old('origin') as $i => $o) {
        $rows[] = ['route_id' => (old('route_id')[$i] ?? 0), 'origin' => $o, 'destination' => (old('destination')[$i] ?? ''), 'price' => (old('fee')[$i] ?? '')];
    }
} else {
    $rows = array_map(static fn ($r) => ['route_id' => $r['route_id'], 'origin' => $r['origin'], 'destination' => $r['destination'], 'price' => number_format((float) $r['price'], 2)], $routes);
}
if ($rows === []) {
    $rows[] = ['route_id' => 0, 'origin' => '', 'destination' => '', 'price' => ''];
}
?>
<div class="mb-3 text-end"><a href="<?= site_url('/admin/catalog?tab=services') ?>" class="btn btn-ghost btn-sm">Back to services</a></div>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <section class="cs-panel mb-4">
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Item Code *</label>
                    <input type="text" class="form-control" value="<?= esc($next_code) ?>" readonly>
                    <div class="form-text">Auto-generated. Cannot be edited.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="service_name">Service Name *</label>
                    <input type="text" id="service_name" name="service_name" maxlength="100" class="form-control<?= $bad('service_name') ?>" placeholder="e.g. Balik Probinsya Program" value="<?= esc($val('service_name')) ?>">
                    <?= $err('service_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="category">Service Category *</label>
                    <input type="text" id="category" name="category" maxlength="100" class="form-control<?= $bad('category') ?>" placeholder="e.g. Provincial Transport" value="<?= esc($val('category')) ?>">
                    <?= $err('category') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="short_description">Short Description</label>
                    <input type="text" id="short_description" name="short_description" maxlength="255" class="form-control" placeholder="Shown on listing cards" value="<?= esc($val('short_description')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Service Description *</label>
                    <textarea id="description" name="description" rows="3" class="form-control<?= $bad('description') ?>" placeholder="e.g. Transportation service for bringing deceased individuals from the designated origin to Mindoro."><?= esc($val('description')) ?></textarea>
                    <?= $err('description') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="image">Service Image</label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.jfif,.png,.webp" class="form-control<?= $bad('image') ?>">
                    <?= $err('image') ?>
                    <div class="form-text">JPG, JFIF, PNG or WebP, up to 2 MB.<?= $isEdit ? ' Leave empty to keep the current image.' : '' ?></div>
                    <?php if ($isEdit): ?>
                        <img src="<?= esc(cs_image_url($item['image_path'] ?? null)) ?>" alt="<?= esc((string) ($item['service_name'] ?? 'Current image')) ?>" class="mt-2" style="width:160px;aspect-ratio:4/3;object-fit:cover;border-radius:6px;">
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="availability_status">Availability Status *</label>
                    <select id="availability_status" name="availability_status" class="form-select">
                        <option value="available" <?= $st === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="unavailable" <?= $st === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                        <option value="archived" <?= $st === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Route and pricing *</h2></div>
        <div class="cs-panel__body">
            <p class="cs-muted">The fee depends on the route. Each origin-destination pair can appear once.</p>
            <?= $err('routes') ?>
            <div id="routeRows" class="mt-2">
                <?php foreach ($rows as $r): ?>
                    <div class="row g-2 align-items-end mb-2 js-route">
                        <input type="hidden" name="route_id[]" value="<?= (int) $r['route_id'] ?>">
                        <div class="col-md-4"><label class="form-label">Origin *</label><input type="text" name="origin[]" maxlength="100" class="form-control" placeholder="Manila" value="<?= esc($r['origin']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Destination *</label><input type="text" name="destination[]" maxlength="100" class="form-control" placeholder="Mindoro" value="<?= esc($r['destination']) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Service Fee (&#8369;) *</label><input type="text" inputmode="decimal" name="fee[]" class="form-control" placeholder="35,000.00" value="<?= esc($r['price']) ?>"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-secondary js-remove" title="Remove route" aria-label="Remove route"><i class="ti ti-trash" aria-hidden="true"></i></button></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="addRoute" class="btn btn-outline-primary btn-sm mt-2"><i class="ti ti-plus" aria-hidden="true"></i> Add Route</button>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="eligibility_rules">Plan Holder Eligibility &amp; Benefit Rules</label>
                    <textarea id="eligibility_rules" name="eligibility_rules" rows="3" class="form-control" placeholder="e.g. Eligible plan holders receive casket coverage according to the applicable KAAGAPAY policy. The service fee stays the same."><?= esc($val('eligibility_rules')) ?></textarea>
                    <div class="form-text">The casket value is never subtracted from the service fee.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="terms">Terms and Conditions</label>
                    <textarea id="terms" name="terms" rows="3" class="form-control"><?= esc($val('terms')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes / Operational Requirements</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control"><?= esc($val('notes')) ?></textarea>
                </div>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= site_url('/admin/catalog?tab=services') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Service</button>
    </div>
</form>

<script>
(function () {
    var wrap = document.getElementById('routeRows');
    document.getElementById('addRoute').addEventListener('click', function () {
        var row = wrap.querySelector('.js-route').cloneNode(true);
        row.querySelectorAll('input').forEach(function (i) { i.value = i.name === 'route_id[]' ? '0' : ''; });
        wrap.appendChild(row);
    });
    wrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-remove');
        if (!btn) { return; }
        var rows = wrap.querySelectorAll('.js-route');
        if (rows.length > 1) { btn.closest('.js-route').remove(); }
        else { rows[0].querySelectorAll('input').forEach(function (i) { if (i.name !== 'route_id[]') { i.value = ''; } }); }
    });
})();
</script>
<?= $this->endSection() ?>
