<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">

<div class="so">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Package Details</h4>
            <p class="text-muted mb-0"><?= esc($package['package_name'] ?? '') ?></p>
        </div>
        <div>
            <a href="<?= site_url('/branch-admin/packages/edit/' . (int) $package['package_id']) ?>" class="so-btn so-btn--purple so-btn--sm">Edit</a>
            <a href="<?= site_url('/branch-admin/service-package/packages') ?>" class="so-btn so-btn--outline so-btn--sm">Back</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <div class="so-card mb-3">
        <div class="so-card__body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Package Name:</strong> <?= esc($package['package_name'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Base Price:</strong> ₱<?= number_format((float) ($package['base_price'] ?? 0), 2) ?></div>
                <div class="col-md-3"><strong>Customizable:</strong> <?= ((int) ($package['is_customizable'] ?? 0) === 1) ? 'Yes' : 'No' ?></div>
                <div class="col-md-2"><strong>Status:</strong> <?= esc(ucfirst((string) ($package['status'] ?? 'Active'))) ?></div>
                <div class="col-12"><strong>Description:</strong> <?= esc($package['description'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Variants -->
        <div class="col-lg-6">
            <div class="so-card h-100">
                <div class="so-card__header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Variants</h5>
                    <button type="button" class="so-btn so-btn--purple so-btn--sm" onclick="soOpenVariantModal('add')">
                        <i class="mdi mdi-plus"></i> Add Variant
                    </button>
                </div>
                <div class="so-card__body p-0">
                    <div class="table-responsive">
                        <table class="so-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Default</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($package['variants'])): ?>
                                    <tr><td colspan="5" class="text-center py-3">No variants found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($package['variants'] as $variant): ?>
                                        <tr>
                                            <td><?= esc($variant['variant_name']) ?></td>
                                            <td>₱<?= number_format((float) ($variant['base_price'] ?? 0), 2) ?></td>
                                            <td>
                                                <span class="so-badge so-badge--<?= strtolower($variant['status'] ?? 'active') === 'active' ? 'green' : 'amber' ?>">
                                                    <?= esc(ucfirst((string) ($variant['status'] ?? 'Active'))) ?>
                                                </span>
                                            </td>
                                            <td><?= ((int) ($variant['is_default'] ?? 0) === 1) ? '<i class="mdi mdi-check text-success"></i>' : '' ?></td>
                                            <td>
                                                <button type="button" class="so-btn so-btn--outline so-btn--sm" onclick="soOpenVariantModal('edit', <?= (int) $variant['variant_id'] ?>, '<?= esc(addslashes($variant['variant_name'])) ?>', '<?= esc(addslashes((string) ($variant['description'] ?? ''))) ?>', '<?= (float) ($variant['base_price'] ?? 0) ?>', <?= (int) ($variant['is_default'] ?? 0) ?>, '<?= esc($variant['status'] ?? 'active') ?>')">
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </button>
                                                <form method="post" action="<?= site_url('/branch-admin/packages/' . (int) $package['package_id'] . '/variants/delete/' . (int) $variant['variant_id']) ?>" class="d-inline" onsubmit="return confirm('Delete this variant?');">
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
            </div>
        </div>

        <!-- Inclusions -->
        <div class="col-lg-6">
            <div class="so-card h-100">
                <div class="so-card__header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Inclusions</h5>
                    <button type="button" class="so-btn so-btn--purple so-btn--sm" onclick="soOpenInclusionModal('add')">
                        <i class="mdi mdi-plus"></i> Add Inclusion
                    </button>
                </div>
                <div class="so-card__body p-0">
                    <div class="table-responsive">
                        <table class="so-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($package['inclusions'])): ?>
                                    <tr><td colspan="4" class="text-center py-3">No inclusions found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($package['inclusions'] as $inclusion): ?>
                                        <tr>
                                            <td>
                                                <strong><?= esc($inclusion['item_name']) ?></strong>
                                                <?php if (! empty($inclusion['description'])): ?>
                                                    <br><small class="text-muted"><?= esc($inclusion['description']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc(ucfirst((string) ($inclusion['category'] ?? '-'))) ?></td>
                                            <td>
                                                <span class="so-badge so-badge--<?= strtolower($inclusion['status'] ?? 'active') === 'active' ? 'green' : 'amber' ?>">
                                                    <?= esc(ucfirst((string) ($inclusion['status'] ?? 'Active'))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="so-btn so-btn--outline so-btn--sm" onclick="soOpenInclusionModal('edit', <?= (int) $inclusion['inclusion_id'] ?>, '<?= esc(addslashes($inclusion['item_name'])) ?>', '<?= esc(addslashes((string) ($inclusion['description'] ?? ''))) ?>', '<?= esc($inclusion['category'] ?? '') ?>', '<?= esc($inclusion['status'] ?? 'active') ?>')">
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </button>
                                                <form method="post" action="<?= site_url('/branch-admin/packages/' . (int) $package['package_id'] . '/inclusions/delete/' . (int) $inclusion['inclusion_id']) ?>" class="d-inline" onsubmit="return confirm('Delete this inclusion?');">
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
            </div>
        </div>
    </div>

    <!-- Package Services -->
    <div class="so-card mt-3">
        <div class="so-card__header">Services Included</div>
        <div class="so-card__body p-0">
            <div class="table-responsive">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($package['items'])): ?>
                            <tr><td colspan="2" class="text-center py-3">No services found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($package['items'] as $item): ?>
                                <tr>
                                    <td><?= esc($item['item_name'] ?? '-') ?></td>
                                    <td><?= esc($item['description'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="so-card__footer">
            <form method="post" action="<?= site_url('/branch-admin/packages/add-item/' . (int) $package['package_id']) ?>" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?>
                <select name="service_list_id" class="so-form-select" style="max-width:300px;" required>
                    <option value="">Select service</option>
                    <?php foreach (($service_list ?? []) as $service): ?>
                        <option value="<?= (int) $service['service_list_id'] ?>"><?= esc($service['service_name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="so-btn so-btn--purple so-btn--sm">Add Service</button>
            </form>
        </div>
    </div>

    <!-- Price Versions -->
    <div class="so-card mt-3">
        <div class="so-card__header">Price Versions</div>
        <div class="so-card__body p-0">
            <div class="table-responsive">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>Price</th>
                            <th>Effective Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($package['versions'])): ?>
                            <tr><td colspan="3" class="text-center py-3">No versions found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($package['versions'] as $version): ?>
                                <tr>
                                    <td>₱<?= number_format((float) ($version['price'] ?? 0), 2) ?></td>
                                    <td><?= esc($version['effective_date'] ?? '-') ?></td>
                                    <td><?= esc(ucfirst((string) ($version['status'] ?? '-'))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============ Variant Modal ============ -->
<div class="so-modal-overlay" id="variantModalOverlay" onclick="soCloseVariantModal()"></div>
<div class="so-modal" id="variantModal">
    <div class="so-modal__header">
        <h3 class="so-modal__title" id="variantModalTitle">Add Variant</h3>
        <button class="so-modal__close" onclick="soCloseVariantModal()"><i class="mdi mdi-close"></i></button>
    </div>
    <form method="post" action="<?= site_url('/branch-admin/packages/' . (int) $package['package_id'] . '/variants/add') ?>" id="variantForm">
        <?= csrf_field() ?>
        <input type="hidden" name="variant_id" id="variantId">
        <div class="so-modal__body">
            <div class="so-form-group">
                <label class="so-form-label">Variant Name *</label>
                <input class="so-form-input" name="variant_name" id="variantName" type="text" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description" id="variantDesc"></textarea>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Base Price *</label>
                <input class="so-form-input" name="base_price" id="variantPrice" type="number" step="0.01" min="0.01" required>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="so-form-label">Status</label>
                    <select class="so-form-select" name="status" id="variantStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="so-form-label">Set as Default</label>
                    <select class="so-form-select" name="is_default" id="variantDefault">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="so-modal__footer">
            <button type="button" class="so-btn so-btn--outline" onclick="soCloseVariantModal()">Cancel</button>
            <button type="submit" class="so-btn so-btn--purple">Save Variant</button>
        </div>
    </form>
</div>

<!-- ============ Inclusion Modal ============ -->
<div class="so-modal-overlay" id="inclusionModalOverlay" onclick="soCloseInclusionModal()"></div>
<div class="so-modal" id="inclusionModal">
    <div class="so-modal__header">
        <h3 class="so-modal__title" id="inclusionModalTitle">Add Inclusion</h3>
        <button class="so-modal__close" onclick="soCloseInclusionModal()"><i class="mdi mdi-close"></i></button>
    </div>
    <form method="post" action="<?= site_url('/branch-admin/packages/' . (int) $package['package_id'] . '/inclusions/add') ?>" id="inclusionForm">
        <?= csrf_field() ?>
        <input type="hidden" name="inclusion_id" id="inclusionId">
        <div class="so-modal__body">
            <div class="so-form-group">
                <label class="so-form-label">Item Name *</label>
                <input class="so-form-input" name="item_name" id="inclusionName" type="text" required>
            </div>
            <div class="so-form-group">
                <label class="so-form-label">Description</label>
                <textarea class="so-form-textarea" name="description" id="inclusionDesc"></textarea>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="so-form-label">Category *</label>
                    <select class="so-form-select" name="category" id="inclusionCategory" required>
                        <option value="casket">Casket</option>
                        <option value="facility">Facility</option>
                        <option value="preparation">Preparation</option>
                        <option value="transport">Transport</option>
                        <option value="flowers">Flowers</option>
                        <option value="stationery">Stationery</option>
                        <option value="documents">Documents</option>
                        <option value="staffing">Staffing</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="so-form-label">Status</label>
                    <select class="so-form-select" name="status" id="inclusionStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="so-modal__footer">
            <button type="button" class="so-btn so-btn--outline" onclick="soCloseInclusionModal()">Cancel</button>
            <button type="submit" class="so-btn so-btn--purple">Save Inclusion</button>
        </div>
    </form>
</div>

<script>
(function () {
    'use strict';

    /* --- Variant Modal --- */
    window.soOpenVariantModal = function (mode, id, name, desc, price, isDefault, status) {
        const overlay = document.getElementById('variantModalOverlay');
        const modal = document.getElementById('variantModal');
        const form = document.getElementById('variantForm');
        const packageId = <?= (int) $package['package_id'] ?>;

        if (mode === 'edit') {
            document.getElementById('variantModalTitle').textContent = 'Edit Variant';
            form.action = '<?= site_url('/branch-admin/packages/') ?>' + packageId + '/variants/update/' + id;
            document.getElementById('variantId').value = id;
            document.getElementById('variantName').value = name || '';
            document.getElementById('variantDesc').value = desc || '';
            document.getElementById('variantPrice').value = price || '';
            document.getElementById('variantDefault').value = isDefault ? '1' : '0';
            document.getElementById('variantStatus').value = status || 'active';
        } else {
            document.getElementById('variantModalTitle').textContent = 'Add Variant';
            form.action = '<?= site_url('/branch-admin/packages/') ?>' + packageId + '/variants/add';
            form.reset();
            document.getElementById('variantId').value = '';
        }

        overlay.classList.add('show');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.soCloseVariantModal = function () {
        document.getElementById('variantModalOverlay').classList.remove('show');
        document.getElementById('variantModal').classList.remove('show');
        document.body.style.overflow = '';
    };

    /* --- Inclusion Modal --- */
    window.soOpenInclusionModal = function (mode, id, name, desc, category, status) {
        const overlay = document.getElementById('inclusionModalOverlay');
        const modal = document.getElementById('inclusionModal');
        const form = document.getElementById('inclusionForm');
        const packageId = <?= (int) $package['package_id'] ?>;

        if (mode === 'edit') {
            document.getElementById('inclusionModalTitle').textContent = 'Edit Inclusion';
            form.action = '<?= site_url('/branch-admin/packages/') ?>' + packageId + '/inclusions/update/' + id;
            document.getElementById('inclusionId').value = id;
            document.getElementById('inclusionName').value = name || '';
            document.getElementById('inclusionDesc').value = desc || '';
            document.getElementById('inclusionCategory').value = category || 'other';
            document.getElementById('inclusionStatus').value = status || 'active';
        } else {
            document.getElementById('inclusionModalTitle').textContent = 'Add Inclusion';
            form.action = '<?= site_url('/branch-admin/packages/') ?>' + packageId + '/inclusions/add';
            form.reset();
            document.getElementById('inclusionId').value = '';
        }

        overlay.classList.add('show');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.soCloseInclusionModal = function () {
        document.getElementById('inclusionModalOverlay').classList.remove('show');
        document.getElementById('inclusionModal').classList.remove('show');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.soCloseVariantModal();
            window.soCloseInclusionModal();
        }
    });
})();
</script>
<?= $this->endSection() ?>
