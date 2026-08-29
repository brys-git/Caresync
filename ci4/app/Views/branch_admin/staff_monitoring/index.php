<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h4 class="mb-3">Staff Monitoring</h4>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= ($active_tab ?? '') === 'staff-list' ? 'active' : '' ?>" href="<?= site_url('/branch-admin/staff-monitoring') ?>">Staff List</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active_tab ?? '') === 'assign' ? 'active' : '' ?>" href="<?= site_url('/branch-admin/staff-monitoring/assign') ?>">Assign Tasks</a></li>
        <li class="nav-item"><a class="nav-link <?= ($active_tab ?? '') === 'activities' ? 'active' : '' ?>" href="<?= site_url('/branch-admin/staff-monitoring/activities') ?>">Staff Activities</a></li>
    </ul>

    <?php if (($active_tab ?? '') === 'staff-list'): ?>
        <div class="card mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rows = $staff ?? []; ?>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center py-3">No staff found in your branch.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <?php $statusValue = (string) ($row['status'] ?? ($row['account_status'] ?? 'unknown')); ?>
                                    <tr>
                                        <td><?= esc(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                                        <td><?= esc($row['email'] ?? '-') ?></td>
                                        <td><?= esc($row['contact_number'] ?? '-') ?></td>
                                        <td>
                                            <?php if (strtolower($statusValue) === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif (strtolower($statusValue) === 'inactive'): ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?= esc(ucfirst($statusValue)) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= site_url('/branch-admin/staff-monitoring?staff_id=' . (int) $row['user_id']) ?>" class="btn btn-sm btn-outline-secondary">View Profile</a>
                                            <a href="<?= site_url('/branch-admin/staff-management/edit/' . (int) $row['user_id']) ?>" class="btn btn-sm btn-outline-primary">Edit Staff</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (! empty($selected_staff)): ?>
            <div class="card">
                <div class="card-header">Staff Profile</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Name:</strong> <?= esc(($selected_staff['first_name'] ?? '') . ' ' . ($selected_staff['last_name'] ?? '')) ?></div>
                        <div class="col-md-4"><strong>Email:</strong> <?= esc($selected_staff['email'] ?? '-') ?></div>
                        <div class="col-md-4"><strong>Contact:</strong> <?= esc($selected_staff['contact_number'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($active_tab ?? '') === 'assign'): ?>
        <div class="card mb-3">
            <div class="card-header">Assign Staff to Service</div>
            <div class="card-body">
                <form method="post" action="<?= site_url('/branch-admin/staff-monitoring/store') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <select name="service_id" class="form-select" required>
                                <option value="">Select service</option>
                                <?php foreach (($services ?? []) as $service): ?>
                                    <option value="<?= (int) $service['service_id'] ?>" <?= (int) old('service_id') === (int) $service['service_id'] ? 'selected' : '' ?>>
                                        #<?= (int) $service['service_id'] ?> - <?= esc($service['service_type'] ?? '-') ?> (<?= esc($service['service_date'] ?? '-') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Staff</label>
                            <select name="staff_id" class="form-select" required>
                                <option value="">Select staff</option>
                                <?php foreach (($staff ?? []) as $member): ?>
                                    <option value="<?= (int) $member['user_id'] ?>" <?= (int) old('staff_id') === (int) $member['user_id'] ? 'selected' : '' ?>>
                                        <?= esc(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary">Assign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Assignment History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Staff</th>
                                <th>Date Assigned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rows = $assignments ?? []; ?>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="3" class="text-center py-3">No assignments yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>#<?= (int) ($row['service_id'] ?? 0) ?> - <?= esc($row['service_type'] ?? '-') ?></td>
                                        <td><?= esc(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                                        <td><?= esc($row['assigned_date'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($active_tab ?? '') === 'activities'): ?>
        <div class="row g-3 mb-3">
            <?php foreach (($staff ?? []) as $member): ?>
                <?php $staffId = (int) $member['user_id']; ?>
                <?php $p = $performance[$staffId] ?? ['total_assigned' => 0, 'completed' => 0, 'ongoing' => 0, 'pending' => 0, 'cancelled' => 0]; ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-2"><?= esc(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></h6>
                            <div class="small text-muted">Total Assigned: <?= (int) $p['total_assigned'] ?></div>
                            <div class="small text-success">Completed: <?= (int) $p['completed'] ?></div>
                            <div class="small text-primary">Ongoing: <?= (int) $p['ongoing'] ?></div>
                            <div class="small text-warning">Pending: <?= (int) $p['pending'] ?></div>
                            <div class="small text-secondary">Cancelled: <?= (int) $p['cancelled'] ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header">Staff Activities</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Assigned Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rows = $activities ?? []; ?>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="5" class="text-center py-3">No activity records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= esc(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                                        <td><?= esc($row['service_type'] ?? '-') ?></td>
                                        <td><?= esc($row['service_date'] ?? '-') ?></td>
                                        <td>
                                            <?php $status = strtolower((string) ($row['status'] ?? '')); ?>
                                            <?php if ($status === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($status === 'ongoing'): ?>
                                                <span class="badge bg-primary">Ongoing</span>
                                            <?php elseif ($status === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <span class="badge bg-secondary">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark"><?= esc(ucfirst($status)) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($row['assigned_date'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
