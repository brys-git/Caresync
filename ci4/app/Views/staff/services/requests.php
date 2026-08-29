<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h4 class="mb-1">Claims</h4>
    <p class="text-muted mb-3">Plan holders claiming their package's or a service's benefit. Review the details and supporting documents, then approve or reject.</p>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (! empty($branch_issue)): ?>
        <div class="alert alert-warning"><?= esc($branch_issue) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services?tab=services') ?>">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services?tab=packages') ?>">Packages</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= site_url('/staff/services/requests') ?>">Claims</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/staff/services/ongoing') ?>">Ongoing Services</a></li>
    </ul>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Plan Holder</th>
                            <th>Claiming</th>
                            <th>Date Requested</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rows = $requests ?? []; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="text-center py-3">No claims found for your branch.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $request): ?>
                                <?php $status = (string) ($request['status'] ?? 'pending'); ?>
                                <tr>
                                    <td><?= esc(trim((string) (($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')))) ?></td>
                                    <td><?= esc((string) ($request['claim_label'] ?? $request['package_name'] ?? $request['service_name'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($request['created_at'] ?? '-')) ?></td>
                                    <td>
                                        <?php if ($status === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($status === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= site_url('/staff/services/requests/' . (int) $request['application_id']) ?>" class="btn btn-sm btn-primary me-1">View</a>
                                        <?php if ($status === 'pending'): ?>
                                            <form action="<?= site_url('/staff/services/requests/approve/' . (int) $request['application_id']) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                            </form>
                                            <form action="<?= site_url('/staff/services/requests/reject/' . (int) $request['application_id']) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="text" name="rejection_reason" class="form-control form-control-sm d-inline-block" style="max-width: 160px;" placeholder="Reason (optional)">
                                                <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                            </form>
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
<?= $this->endSection() ?>
