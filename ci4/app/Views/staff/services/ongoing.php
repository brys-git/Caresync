<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h4 class="mb-3">Ongoing Services</h4>

    <?php if (! empty($branch_issue)): ?>
        <div class="alert alert-warning"><?= esc($branch_issue) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services?tab=services') ?>">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services?tab=packages') ?>">Packages</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services/requests') ?>">Service Requests</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= site_url('/staff/services/ongoing') ?>">Ongoing Services</a></li>
    </ul>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Plan Holder</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rows = $services ?? []; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="text-center py-3">No ongoing services found for your branch.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php $status = strtolower((string) ($row['status'] ?? '')); ?>
                                <tr>
                                    <td><?= esc(trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?></td>
                                    <td><?= esc((string) ($row['service_name'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($row['service_date'] ?? '-')) ?></td>
                                    <td>
                                        <?php if ($status === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($status === 'ongoing'): ?>
                                            <span class="badge bg-primary">Ongoing</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Completed</span>
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
</div>
<?= $this->endSection() ?>
