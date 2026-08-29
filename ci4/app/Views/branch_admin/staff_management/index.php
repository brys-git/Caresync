<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Staff Management</h4>
        <a href="<?= site_url('/branch-admin/staff-monitoring') ?>" class="btn btn-outline-secondary btn-sm">Back to Staff Monitoring</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
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
                        <?php if (empty($staff ?? [])): ?>
                            <tr><td colspan="5" class="text-center py-3">No staff records found.</td></tr>
                        <?php else: ?>
                            <?php foreach (($staff ?? []) as $member): ?>
                                <tr>
                                    <td><?= esc(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></td>
                                    <td><?= esc($member['email'] ?? '-') ?></td>
                                    <td><?= esc($member['contact_number'] ?? '-') ?></td>
                                    <td><?= esc(ucfirst((string) ($member['status'] ?? '-'))) ?></td>
                                    <td>
                                        <a href="<?= site_url('/branch-admin/staff-management/edit/' . (int) $member['user_id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
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
