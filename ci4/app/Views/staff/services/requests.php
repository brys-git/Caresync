<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h4 class="mb-3">Service Requests</h4>

    <?php if (! empty($branch_issue)): ?>
        <div class="alert alert-warning"><?= esc($branch_issue) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services?tab=services') ?>">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services?tab=packages') ?>">Packages</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= site_url('/staff/services/requests') ?>">Service Requests</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services/ongoing') ?>">Ongoing Services</a></li>
    </ul>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Plan Holder Name</th>
                            <th>Package Requested</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rows = $requests ?? []; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="4" class="text-center py-3">No service requests found for your branch.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $request): ?>
                                <tr>
                                    <td><?= esc(trim((string) (($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')))) ?></td>
                                    <td><?= esc((string) ($request['package_name'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($request['created_at'] ?? '-')) ?></td>
                                    <td>
                                        <?php if (($request['status'] ?? '') === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif (($request['status'] ?? '') === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
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

    <small class="text-muted">Approval is handled by Branch Admin.</small>
</div>
<?= $this->endSection() ?>
