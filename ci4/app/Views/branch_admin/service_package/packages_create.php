<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Create Package</h4>
        <a href="<?= site_url('/branch-admin/service-package/packages') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= site_url('/branch-admin/packages/store') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Package Name</label>
                        <input type="text" name="package_name" class="form-control" value="<?= esc(old('package_name')) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Base Price</label>
                        <input type="number" name="base_price" step="0.01" min="0.01" class="form-control" value="<?= esc(old('base_price')) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customizable</label>
                        <select name="is_customizable" class="form-select" required>
                            <option value="1" <?= old('is_customizable', '1') === '1' ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= old('is_customizable') === '0' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Initial Price Effective Date</label>
                        <input type="date" name="initial_effective_date" class="form-control" value="<?= esc(old('initial_effective_date', date('Y-m-d'))) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= esc(old('description')) ?></textarea>
                    </div>
                </div>

                <hr>
                <h6>Package Services</h6>
                <p class="text-muted small mb-2">Add one or more included services.</p>
                <div id="itemRows">
                    <div class="row g-2 mb-2 item-row">
                        <div class="col-md-5">
                            <select name="service_list_id[]" class="form-select service-select" required>
                                <option value="">Select service</option>
                                <?php foreach (($service_list ?? []) as $service): ?>
                                    <option value="<?= (int) $service['service_list_id'] ?>" data-description="<?= esc($service['description'] ?? '') ?>"><?= esc($service['service_name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><input type="text" name="item_description[]" class="form-control service-description" placeholder="Description" readonly></div>
                        <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger remove-item">-</button></div>
                    </div>
                </div>
                <button type="button" id="addItem" class="btn btn-sm btn-outline-primary">Add Service</button>

                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const container = document.getElementById('itemRows');
        const addBtn = document.getElementById('addItem');

        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 item-row';
            row.innerHTML = '<div class="col-md-5"><select name="service_list_id[]" class="form-select service-select" required><option value="">Select service</option><?php foreach (($service_list ?? []) as $service): ?><option value="<?= (int) $service['service_list_id'] ?>" data-description="<?= esc($service['description'] ?? '') ?>"><?= esc($service['service_name'] ?? '') ?></option><?php endforeach; ?></select></div>' +
                '<div class="col-md-6"><input type="text" name="item_description[]" class="form-control service-description" placeholder="Description" readonly></div>' +
                '<div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger remove-item">-</button></div>';
            container.appendChild(row);
        });

        container.addEventListener('change', function (event) {
            if (!event.target.classList.contains('service-select')) {
                return;
            }

            const selectedOption = event.target.options[event.target.selectedIndex];
            const row = event.target.closest('.item-row');
            row.querySelector('.service-description').value = selectedOption ? (selectedOption.dataset.description || '') : '';
        });

        container.addEventListener('click', function (event) {
            if (!event.target.classList.contains('remove-item')) {
                return;
            }

            const rows = container.querySelectorAll('.item-row');
            if (rows.length === 1) {
                rows[0].querySelector('select[name="service_list_id[]"]').value = '';
                rows[0].querySelector('input[name="item_description[]"]').value = '';
                return;
            }

            event.target.closest('.item-row').remove();
        });
    })();
</script>

<?= $this->endSection() ?>
