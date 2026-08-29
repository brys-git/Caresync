<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h4 mb-3">Service and Package Module</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">1. Create Package</h2>
                    <form method="post" action="<?= base_url('packages/create') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="package_name">Package Name</label>
                                <input id="package_name" name="package_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="base_price">Base Price</label>
                                <input id="base_price" name="base_price" type="number" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="initial_effective_date">Initial Effective Date</label>
                                <input id="initial_effective_date" name="initial_effective_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="initial_version_status">Initial Version Status</label>
                                <select id="initial_version_status" name="initial_version_status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="is_customizable">Customizable</label>
                                <select id="is_customizable" name="is_customizable" class="form-select" required>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Create Package</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">2. Add Package Item</h2>
                    <form method="post" action="<?= base_url('packages/add-item') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="item_package_id">Package</label>
                                <select id="item_package_id" name="package_id" class="form-select" required>
                                    <option value="">Select package</option>
                                    <?php foreach ($packages as $package): ?>
                                        <option value="<?= esc((string) $package['package_id']) ?>"><?= esc($package['package_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="item_name">Item Name</label>
                                <input id="item_name" name="item_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="item_description">Item Description</label>
                                <textarea id="item_description" name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary mt-3" type="submit">Add Item</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">3. Set Price Version</h2>
                    <form method="post" action="<?= base_url('packages/add-version') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="version_package_id">Package</label>
                                <select id="version_package_id" name="package_id" class="form-select" required>
                                    <option value="">Select package</option>
                                    <?php foreach ($packages as $package): ?>
                                        <option value="<?= esc((string) $package['package_id']) ?>"><?= esc($package['package_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="version_price">Price</label>
                                <input id="version_price" name="price" type="number" step="0.01" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="effective_date">Effective Date</label>
                                <input id="effective_date" name="effective_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="version_status">Status</label>
                                <select id="version_status" name="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary mt-3" type="submit">Create Version</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">4. Assign Package to Plan</h2>
                    <form method="post" action="<?= base_url('packages/assign-plan') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="plan_holder_id">Plan Holder</label>
                                <select id="plan_holder_id" name="plan_holder_id" class="form-select" required>
                                    <option value="">Select plan holder</option>
                                    <?php foreach ($plan_holders as $holder): ?>
                                        <option value="<?= esc((string) $holder['plan_holder_id']) ?>">
                                            <?= esc($holder['first_name'] . ' ' . $holder['last_name']) ?> (<?= esc($holder['unique_identifier']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="plan_package_id">Package</label>
                                <select id="plan_package_id" name="package_id" class="form-select" required>
                                    <option value="">Select package</option>
                                    <?php foreach ($packages as $package): ?>
                                        <option value="<?= esc((string) $package['package_id']) ?>"><?= esc($package['package_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="version_id">Version</label>
                                <select id="version_id" name="version_id" class="form-select" required>
                                    <option value="">Select version</option>
                                    <?php foreach ($package_versions as $version): ?>
                                        <option value="<?= esc((string) $version['version_id']) ?>" data-package-id="<?= esc((string) $version['package_id']) ?>">
                                            <?= esc($version['package_name']) ?> - <?= esc((string) $version['price']) ?> (<?= esc($version['effective_date']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">The selected version price is locked in plans.monthly_fee.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="start_date">Start Date</label>
                                <input id="start_date" name="start_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="plan_status">Plan Status</label>
                                <select id="plan_status" name="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="passbook_fee">Passbook Fee</label>
                                <input id="passbook_fee" name="passbook_fee" type="number" step="0.01" class="form-control" value="50.00">
                            </div>
                        </div>
                        <button class="btn btn-success mt-3" type="submit">Assign to Plan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Packages</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($packages as $package): ?>
                            <li class="list-group-item px-0">
                                <strong><?= esc($package['package_name']) ?></strong><br>
                                <small>Base Price: <?= esc((string) $package['base_price']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Package Items</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($package_items as $item): ?>
                            <li class="list-group-item px-0">
                                <strong><?= esc($item['package_name']) ?></strong> - <?= esc($item['item_name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Price Versions</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($package_versions as $version): ?>
                            <li class="list-group-item px-0">
                                <strong><?= esc($version['package_name']) ?></strong><br>
                                <small>Version #<?= esc((string) $version['version_id']) ?> | <?= esc((string) $version['price']) ?> | <?= esc($version['effective_date']) ?> | <?= esc($version['status']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const packageSelect = document.getElementById('plan_package_id');
        const versionSelect = document.getElementById('version_id');

        function filterVersions() {
            const selectedPackageId = packageSelect.value;
            const options = Array.from(versionSelect.options);

            options.forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const optionPackageId = option.getAttribute('data-package-id');
                option.hidden = selectedPackageId !== '' && optionPackageId !== selectedPackageId;
            });

            versionSelect.value = '';
        }

        packageSelect.addEventListener('change', filterVersions);
        filterVersions();
    })();
</script>
<?= $this->endSection() ?>
