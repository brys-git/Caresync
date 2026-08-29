<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Service Offer</h1>
            <p class="text-muted mb-0">Create live packages and services, then review branch-admin requests in one place.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?= ($tab ?? '') === 'packages' ? 'active' : '' ?>" href="<?= site_url('/admin/service-offer?tab=packages') ?>">Create Packages</a></li>
        <li class="nav-item"><a class="nav-link <?= ($tab ?? '') === 'services' ? 'active' : '' ?>" href="<?= site_url('/admin/service-offer?tab=services') ?>">Create Services</a></li>
        <li class="nav-item"><a class="nav-link <?= ($tab ?? '') === 'approval' ? 'active' : '' ?>" href="<?= site_url('/admin/service-offer?tab=approval') ?>">Approval Queue</a></li>
    </ul>

    <?php if (($tab ?? '') === 'packages'): ?>
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Create Package</h2>
                        <form method="post" action="<?= base_url('admin/service-offer/package/store') ?>">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="package_name">Package Name</label>
                                    <input id="package_name" name="package_name" type="text" class="form-control" value="<?= esc(old('package_name')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="base_price">Base Price</label>
                                    <input id="base_price" name="base_price" type="number" step="0.01" min="0" class="form-control" value="<?= esc(old('base_price')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="is_customizable">Customizable</label>
                                    <select id="is_customizable" name="is_customizable" class="form-select" required>
                                        <option value="1" <?= old('is_customizable', '1') === '1' ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= old('is_customizable') === '0' ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="initial_effective_date">Initial Effective Date</label>
                                    <input id="initial_effective_date" name="initial_effective_date" type="date" class="form-control" value="<?= esc(old('initial_effective_date', date('Y-m-d'))) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="initial_version_status">Initial Version Status</label>
                                    <select id="initial_version_status" name="initial_version_status" class="form-select" required>
                                        <option value="active" <?= old('initial_version_status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= old('initial_version_status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea id="description" name="description" class="form-control" rows="3"><?= esc(old('description')) ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Create Package</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Existing Packages</h2>
                        <div class="list-group">
                            <?php foreach (($packages ?? []) as $package): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong><?= esc((string) ($package['package_name'] ?? '-')) ?></strong>
                                        <span class="badge bg-secondary"><?= esc((string) ($package['base_price'] ?? '0.00')) ?></span>
                                    </div>
                                    <small class="text-muted"><?= esc((string) ($package['description'] ?? '')) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($tab ?? '') === 'services'): ?>
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Create Service</h2>
                        <form method="post" action="<?= base_url('admin/service-offer/service/store') ?>">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="service_name">Service Name</label>
                                    <input id="service_name" name="service_name" type="text" class="form-control" value="<?= esc(old('service_name')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="service_price">Base Price</label>
                                    <input id="service_price" name="base_price" type="number" step="0.01" min="0" class="form-control" value="<?= esc(old('base_price')) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="service_status">Status</label>
                                    <select id="service_status" name="status" class="form-select" required>
                                        <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="service_description">Description</label>
                                    <textarea id="service_description" name="description" class="form-control" rows="3"><?= esc(old('description')) ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Create Service</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Existing Services</h2>
                        <div class="list-group">
                            <?php foreach (($services ?? []) as $service): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong><?= esc((string) ($service['service_name'] ?? '-')) ?></strong>
                                        <span class="badge bg-secondary"><?= esc((string) ($service['base_price'] ?? '0.00')) ?></span>
                                    </div>
                                    <small class="text-muted"><?= esc((string) ($service['description'] ?? '')) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($tab ?? '') === 'approval'): ?>
        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link <?= ($approval_tab ?? 'services') === 'services' ? 'active' : '' ?>" href="<?= site_url('/admin/service-offer?tab=approval&approval_tab=services') ?>">Pending Services</a></li>
            <li class="nav-item"><a class="nav-link <?= ($approval_tab ?? 'services') === 'packages' ? 'active' : '' ?>" href="<?= site_url('/admin/service-offer?tab=approval&approval_tab=packages') ?>">Pending Packages</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade <?= ($approval_tab ?? 'services') === 'services' ? 'show active' : '' ?>" id="services-approval">
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead><tr><th>Name</th><th>Description</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($pending_services ?? [])): ?>
                                    <tr><td colspan="5" class="text-center py-4">No pending services found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pending_services as $row): ?>
                                        <tr>
                                            <td><?= esc((string) ($row['service_name'] ?? '-')) ?></td>
                                            <td><?= esc((string) ($row['description'] ?? '-')) ?></td>
                                            <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?: '-' ?></td>
                                            <td><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                                            <td>
                                                <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                    <form method="post" action="<?= site_url('/admin/service-offer/approval/service/approve/' . (int) $row['pending_service_id']) ?>" class="d-inline">
                                                        <?= csrf_field() ?><button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                    </form>
                                                    <form method="post" action="<?= site_url('/admin/service-offer/approval/service/reject/' . (int) $row['pending_service_id']) ?>" class="d-inline">
                                                        <?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade <?= ($approval_tab ?? 'services') === 'packages' ? 'show active' : '' ?>" id="packages-approval">
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead><tr><th>Name</th><th>Description</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (empty($pending_packages ?? [])): ?>
                                    <tr><td colspan="5" class="text-center py-4">No pending packages found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pending_packages as $row): ?>
                                        <tr>
                                            <td><?= esc((string) ($row['package_name'] ?? '-')) ?></td>
                                            <td><?= esc((string) ($row['description'] ?? '-')) ?></td>
                                            <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?: '-' ?></td>
                                            <td><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                                            <td>
                                                <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                    <form method="post" action="<?= site_url('/admin/service-offer/approval/package/approve/' . (int) $row['pending_package_id']) ?>" class="d-inline">
                                                        <?= csrf_field() ?><button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                    </form>
                                                    <form method="post" action="<?= site_url('/admin/service-offer/approval/package/reject/' . (int) $row['pending_package_id']) ?>" class="d-inline">
                                                        <?= csrf_field() ?><button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Processed</span>
                                                <?php endif; ?>
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
    <?php endif; ?>
</div>
<?= $this->endSection() ?>