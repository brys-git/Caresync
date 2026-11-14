<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$errors = session('errors') ?? [];
$isEdit = ! empty($item);
$val    = static fn (string $k, $d = '') => old($k, $item[$k] ?? $d);
$money  = static fn (string $k): string => $val($k) !== '' ? number_format((float) $val($k), 2) : '';
$err    = static fn (string $k): string => isset($errors[$k]) ? '<div class="text-danger small mt-1">' . esc($errors[$k]) . '</div>' : '';
$bad    = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$action = site_url('/admin/catalog/save/addons' . ($isEdit ? '/' . (int) $item['addon_id'] : ''));
$method = $val('pricing_method', 'fixed');
?>
<div class="mb-3 text-end"><a href="<?= site_url('/admin/catalog?tab=addons') ?>" class="btn btn-ghost btn-sm">Back to add-ons</a></div>

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
                    <label class="form-label" for="addon_name">Add-on Name *</label>
                    <input type="text" id="addon_name" name="addon_name" maxlength="100" class="form-control<?= $bad('addon_name') ?>" placeholder="e.g. Burial Attire" value="<?= esc($val('addon_name')) ?>">
                    <?= $err('addon_name') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="short_description">Short Description</label>
                    <input type="text" id="short_description" name="short_description" maxlength="255" class="form-control" placeholder="Shown on listing cards" value="<?= esc($val('short_description')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-control" placeholder="e.g. Optional burial clothing for the deceased."><?= esc($val('description')) ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="pricing_method">Pricing Method *</label>
                    <select id="pricing_method" name="pricing_method" class="form-select<?= $bad('pricing_method') ?>">
                        <option value="fixed" <?= $method === 'fixed' ? 'selected' : '' ?>>Fixed Price</option>
                        <option value="range" <?= $method === 'range' ? 'selected' : '' ?>>Price Range</option>
                    </select>
                    <?= $err('pricing_method') ?>
                </div>
                <div class="col-md-4 js-fixed">
                    <label class="form-label" for="price">Price (&#8369;) *</label>
                    <input type="text" inputmode="decimal" id="price" name="price" class="form-control<?= $bad('price') ?>" placeholder="1,500.00" value="<?= esc($money('price')) ?>">
                    <?= $err('price') ?>
                </div>
                <div class="col-md-4 js-range">
                    <label class="form-label" for="min_price">Minimum Price (&#8369;) *</label>
                    <input type="text" inputmode="decimal" id="min_price" name="min_price" class="form-control<?= $bad('min_price') ?>" placeholder="1,500.00" value="<?= esc($money('min_price')) ?>">
                    <?= $err('min_price') ?>
                </div>
                <div class="col-md-4 js-range">
                    <label class="form-label" for="max_price">Maximum Price (&#8369;) *</label>
                    <input type="text" inputmode="decimal" id="max_price" name="max_price" class="form-control<?= $bad('max_price') ?>" placeholder="2,000.00" value="<?= esc($money('max_price')) ?>">
                    <?= $err('max_price') ?>
                </div>
                <div class="col-12 js-range">
                    <div class="form-text">For a price range, staff must confirm the actual price before the add-on is added to a client's total.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="image">Image</label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.jfif,.png,.webp" class="form-control<?= $bad('image') ?>">
                    <?= $err('image') ?>
                    <div class="form-text">Optional. JPG, JFIF, PNG or WebP, up to 2 MB.<?= $isEdit ? ' Leave empty to keep the current image.' : '' ?></div>
                    <?php if ($isEdit): ?>
                        <img src="<?= esc(cs_image_url($item['image_path'] ?? null)) ?>" alt="<?= esc((string) ($item['addon_name'] ?? 'Current image')) ?>" class="mt-2" style="width:160px;aspect-ratio:4/3;object-fit:cover;border-radius:6px;">
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="availability_status">Availability *</label>
                    <?php $st = $val('availability_status', 'available'); ?>
                    <select id="availability_status" name="availability_status" class="form-select">
                        <option value="available" <?= $st === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="unavailable" <?= $st === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                        <option value="archived" <?= $st === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= site_url('/admin/catalog?tab=addons') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Add-on</button>
    </div>
</form>

<script>
(function () {
    var sel = document.getElementById('pricing_method');
    function sync() {
        var range = sel.value === 'range';
        document.querySelectorAll('.js-fixed').forEach(function (el) { el.hidden = range; });
        document.querySelectorAll('.js-range').forEach(function (el) { el.hidden = !range; });
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
<?= $this->endSection() ?>
