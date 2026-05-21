<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $filters = $filters ?? [];
    $selectedBranch = (int) ($filters['branch_id'] ?? 0);
    $selectedStatus = (string) ($filters['status'] ?? '');
    $search = (string) ($filters['search'] ?? '');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Client Management</h1>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= base_url('admin/client-management') ?>" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= $selectedBranch === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc((string) $branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="" <?= $selectedStatus === '' ? 'selected' : '' ?>>All Status</option>
                        <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= esc($search) ?>" placeholder="Name, email, or identifier">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Package</th>
                            <th>Plan Status</th>
                            <th>Holder Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No plan holders found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (($clients ?? []) as $client): ?>
                            <?php $planStatus = strtolower((string) ($client['plan_status'] ?? 'inactive')); ?>
                            <?php $holderStatus = strtolower((string) ($client['plan_holder_status'] ?? 'inactive')); ?>
                            <tr>
                                <td>
                                    <a href="<?= base_url('admin/client-management/view/' . $client['plan_holder_id']) ?>" class="text-decoration-none fw-semibold">
                                        <?= esc(trim((string) ($client['first_name'] ?? '') . ' ' . (string) ($client['last_name'] ?? ''))) ?>
                                    </a>
                                </td>
                                <td><?= esc((string) ($client['branch_name'] ?? '-')) ?></td>
                                <td><?= esc((string) ($client['email'] ?? '-')) ?></td>
                                <td><?= esc((string) ($client['contact_number'] ?? '-')) ?></td>
                                <td><?= esc((string) ($client['package_name'] ?? '-')) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $planStatus === 'active' ? 'success' : ($planStatus === 'pending' ? 'warning' : 'secondary') ?>">
                                        <?= esc(ucfirst($planStatus)) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= $holderStatus === 'active' ? 'success' : 'secondary' ?>">
                                        <?= esc(ucfirst($holderStatus)) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('admin/client-management/edit/' . $client['plan_holder_id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="<?= base_url('admin/client-management/view/' . $client['plan_holder_id']) ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
