<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h4 class="mb-3">Services</h4>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (! empty($branch_issue)): ?>
        <div class="alert alert-warning"><?= esc($branch_issue) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= ($active_tab ?? '') === 'services' ? 'active' : '' ?>" href="<?= site_url('/staff/services?tab=services') ?>">Services</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active_tab ?? '') === 'packages' ? 'active' : '' ?>" href="<?= site_url('/staff/services?tab=packages') ?>">Packages</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services/requests') ?>">Service Requests</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services/ongoing') ?>">Ongoing Services</a></li>
    </ul>

    <?php if (($active_tab ?? '') === 'services'): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Plan Holder</th>
                                <th>Service</th>
                                <th>Package</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rows = $services ?? []; ?>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="6" class="text-center py-3">No services found for your branch.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?></td>
                                        <td><?= esc((string) ($row['service_name'] ?? '-')) ?></td>
                                        <td><?= esc((string) ($row['package_name'] ?? '-')) ?></td>
                                        <td><?= esc((string) ($row['service_date'] ?? '-')) ?></td>
                                        <td>
                                            <?php $status = strtolower((string) ($row['status'] ?? '')); ?>
                                            <?php if ($status === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($status === 'ongoing'): ?>
                                                <span class="badge bg-primary">Ongoing</span>
                                            <?php elseif ($status === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= esc(ucfirst($status)) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= site_url('/staff/services?tab=services&service_id=' . (int) ($row['service_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (! empty($selected_service)): ?>
            <div class="card mt-3">
                <div class="card-header">Service Details</div>
                <div class="card-body">
                    <div><strong>Plan Holder:</strong> <?= esc(trim((string) (($selected_service['first_name'] ?? '') . ' ' . ($selected_service['last_name'] ?? '')))) ?></div>
                    <div><strong>Service:</strong> <?= esc((string) ($selected_service['service_name'] ?? '-')) ?></div>
                    <div><strong>Package:</strong> <?= esc((string) ($selected_service['package_name'] ?? '-')) ?></div>
                    <div><strong>Date:</strong> <?= esc((string) ($selected_service['service_date'] ?? '-')) ?></div>
                    <div><strong>Status:</strong> <?= esc(ucfirst((string) ($selected_service['status'] ?? '-'))) ?></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($active_tab ?? '') === 'packages'): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Description</th>
                                <th>Base Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rows = $packages ?? []; ?>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="4" class="text-center py-3">No packages found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= esc((string) ($row['package_name'] ?? '-')) ?></td>
                                        <td><?= esc((string) ($row['description'] ?? '-')) ?></td>
                                        <td><?= number_format((float) ($row['base_price'] ?? 0), 2) ?></td>
                                        <td>
                                            <a href="<?= site_url('/staff/services?tab=packages&package_id=' . (int) ($row['package_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (! empty($selected_package)): ?>
            <div class="card mt-3">
                <div class="card-header">Package Details</div>
                <div class="card-body">
                    <div><strong>Package:</strong> <?= esc((string) ($selected_package['package_name'] ?? '-')) ?></div>
                    <div><strong>Description:</strong> <?= esc((string) ($selected_package['description'] ?? '-')) ?></div>
                    <div><strong>Base Price:</strong> <?= number_format((float) ($selected_package['base_price'] ?? 0), 2) ?></div>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Services Included</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead><tr><th>Service Name</th><th>Description</th></tr></thead>
                                    <tbody>
                                        <?php $serviceRows = $selected_package_services ?? []; ?>
                                        <?php if (empty($serviceRows)): ?>
                                            <tr><td colspan="2" class="text-center py-3">No services found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($serviceRows as $service): ?>
                                                <tr>
                                                    <td><?= esc((string) ($service['service_name'] ?? '-')) ?></td>
                                                    <td><?= esc((string) ($service['description'] ?? '-')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Price Versions</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead><tr><th>Price</th><th>Effective Date</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php $versionRows = $selected_package_versions ?? []; ?>
                                        <?php if (empty($versionRows)): ?>
                                            <tr><td colspan="3" class="text-center py-3">No versions found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($versionRows as $version): ?>
                                                <tr>
                                                    <td><?= number_format((float) ($version['price'] ?? 0), 2) ?></td>
                                                    <td><?= esc((string) ($version['effective_date'] ?? '-')) ?></td>
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
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
