<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Branch Activity Monitor</h1>
        <?php if ((int) session('role_id') === 1): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('branches') ?>">Back to Branch Management</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Users</th>
                            <th>Plan Holders</th>
                            <th>Total Services</th>
                            <th>Pending</th>
                            <th>Ongoing</th>
                            <th>Completed</th>
                            <th>Cancelled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity_rows as $row): ?>
                            <tr>
                                <td><?= esc($row['branch_name']) ?></td>
                                <td><?= esc($row['branch_status']) ?></td>
                                <td><?= esc((string) $row['user_count']) ?></td>
                                <td><?= esc((string) $row['plan_holder_count']) ?></td>
                                <td><?= esc((string) $row['service_total']) ?></td>
                                <td><?= esc((string) $row['service_pending']) ?></td>
                                <td><?= esc((string) $row['service_ongoing']) ?></td>
                                <td><?= esc((string) $row['service_completed']) ?></td>
                                <td><?= esc((string) $row['service_cancelled']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
