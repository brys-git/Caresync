<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$errors  = session('errors') ?? [];
$isEdit  = ! empty($item);
$val     = static fn (string $k, $d = '') => old($k, $item[$k] ?? $d);
$err     = static fn (string $k): string => isset($errors[$k]) ? '<div class="text-danger small mt-1">' . esc($errors[$k]) . '</div>' : '';
$bad     = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$action  = site_url('/admin/catalog/save/packages' . ($isEdit ? '/' . (int) $item['package_id'] : ''));
$oldIncl = old('inclusions');
$status  = $val('availability_status', 'available');
?>
<div class="mb-3 text-end"><a href="<?= site_url('/admin/catalog?tab=packages') ?>" class="btn btn-ghost btn-sm">Back to packages</a></div>

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
                    <label class="form-label" for="package_name">Package Name *</label>
                    <input type="text" id="package_name" name="package_name" maxlength="100" class="form-control<?= $bad('package_name') ?>" placeholder="e.g. Regular Wood Casket" value="<?= esc($val('package_name')) ?>">
                    <?= $err('package_name') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="category">Package Category *</label>
                    <select id="category" name="category" class="form-select<?= $bad('category') ?>">
                        <option value="">Select category</option>
                        <option value="wood" <?= $val('category') === 'wood' ? 'selected' : '' ?>>Wood Casket</option>
                        <option value="metal" <?= $val('category') === 'metal' ? 'selected' : '' ?>>Metal Casket</option>
                    </select>
                    <?= $err('category') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="variant">Package Variant *</label>
                    <select id="variant" name="variant" class="form-select<?= $bad('variant') ?>">
                        <option value="">Select variant</option>
                        <?php foreach (['regular' => 'Regular', 'oversized' => 'Oversized', 'half_glass' => 'Half-Glass', 'full_glass' => 'Full-Glass'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $val('variant') === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('variant') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="base_price">Price (&#8369;) *</label>
                    <input type="text" inputmode="decimal" id="base_price" name="base_price" class="form-control<?= $bad('base_price') ?>" placeholder="20,000.00" value="<?= esc($val('base_price') !== '' ? number_format((float) $val('base_price'), 2) : '') ?>">
                    <?= $err('base_price') ?>
                    <div class="form-text">Changing the price never changes past transactions.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="short_description">Short Description</label>
                    <input type="text" id="short_description" name="short_description" maxlength="255" class="form-control" placeholder="Shown on listing cards" value="<?= esc($val('short_description')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Package Description *</label>
                    <textarea id="description" name="description" rows="4" class="form-control<?= $bad('description') ?>" placeholder="Shown on the details page"><?= esc($val('description')) ?></textarea>
                    <?= $err('description') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="image">Package Image <?= $isEdit && ! empty($item['image_path']) ? '' : '*' ?></label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.jfif,.png,.webp" class="form-control<?= $bad('image') ?>">
                    <?= $err('image') ?>
                    <div class="form-text">JPG, JFIF, PNG or WebP, up to 2 MB.<?= $isEdit ? ' Leave empty to keep the current image.' : '' ?></div>
                    <?php if ($isEdit): ?>
                        <img src="<?= esc(cs_image_url($item['image_path'] ?? null)) ?>" alt="<?= esc((string) ($item['package_name'] ?? 'Current image')) ?>" class="mt-2" style="width:160px;aspect-ratio:4/3;object-fit:cover;border-radius:6px;">
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="availability_status">Availability *</label>
                    <select id="availability_status" name="availability_status" class="form-select">
                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="unavailable" <?= in_array($status, ['unavailable', 'archived'], true) ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                    <div class="form-text">Archive from the list.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="stock_quantity">Stock Quantity</label>
                    <input type="number" min="0" id="stock_quantity" name="stock_quantity" class="form-control<?= $bad('stock_quantity') ?>" placeholder="Not tracked" value="<?= esc($val('stock_quantity')) ?>">
                    <?= $err('stock_quantity') ?>
                    <div class="form-text">Optional. Leave blank if casket stock is not tracked. Does not affect availability.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Included items</h2></div>
        <div class="cs-panel__body">
            <p class="cs-muted">Everything checked here is covered by the package price and is never charged separately.</p>
            <div class="row g-2">
                <?php foreach ($inclusions as $inc): ?>
                    <?php
                    $iid = (int) $inc['inclusion_item_id'];
                    $on  = is_array($oldIncl) ? in_array((string) $iid, array_map('strval', $oldIncl), true) : in_array($iid, $checked, true);
                    ?>
                    <div class="col-md-6">
                        <label class="d-flex gap-2 align-items-start">
                            <input type="checkbox" class="form-check-input mt-1" name="inclusions[]" value="<?= $iid ?>" <?= $on ? 'checked' : '' ?>>
                            <span><strong><?= esc($inc['item_name']) ?></strong><br><span class="cs-muted small"><?= esc($inc['description']) ?></span></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= site_url('/admin/catalog?tab=packages') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Package</button>
    </div>
</form>
<?= $this->endSection() ?>
