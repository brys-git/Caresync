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
                            <th>Assigned To</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rows = $services ?? []; ?>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="6" class="text-center py-3">No ongoing services found for your branch.</td></tr>
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
                                        <?php $assignedToMe = (int) ($row['assigned_staff'] ?? 0) === (int) session()->get('user_id'); ?>
                                        <?php if ($assignedToMe): ?>
                                            <span class="badge bg-info text-dark">You</span>
                                        <?php else: ?>
                                            <?= esc(trim((string) (($row['staff_first_name'] ?? '') . ' ' . ($row['staff_last_name'] ?? '')))) ?: '-' ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($assignedToMe && $status !== 'completed'): ?>
                                            <form action="<?= site_url('/staff/services/update-status/' . (int) ($row['service_id'] ?? 0)) ?>" method="post" class="d-flex gap-1 align-items-center">
                                                <?= csrf_field() ?>
                                                <select name="status" class="form-select form-select-sm">
                                                    <?php foreach (['pending', 'ongoing', 'completed', 'cancelled'] as $statusOption): ?>
                                                        <option value="<?= esc($statusOption) ?>" <?= $status === $statusOption ? 'selected' : '' ?>><?= esc(ucfirst($statusOption)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= site_url('/staff/services?tab=services&service_id=' . (int) ($row['service_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">View</a>
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
