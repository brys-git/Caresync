<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h4 mb-3">Branch Management</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Add Branch</h2>
                    <form method="post" action="<?= base_url('branches/create') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="branch_name">Branch Name</label>
                                <input id="branch_name" name="branch_name" type="text" class="form-control" value="<?= old('branch_name') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contact_number">Contact Number</label>
                                <input id="contact_number" name="contact_number" type="text" class="form-control" value="<?= old('contact_number') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="address_street">Street</label>
                                <input id="address_street" name="address_street" type="text" class="form-control" value="<?= old('address_street') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="address_barangay">Barangay</label>
                                <input id="address_barangay" name="address_barangay" type="text" class="form-control" value="<?= old('address_barangay') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="address_city">City</label>
                                <input id="address_city" name="address_city" type="text" class="form-control" value="<?= old('address_city') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="address_province">Province</label>
                                <input id="address_province" name="address_province" type="text" class="form-control" value="<?= old('address_province') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date_established">Date Established</label>
                                <input id="date_established" name="date_established" type="date" class="form-control" value="<?= old('date_established') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="status">Status</label>
                                <select id="status" name="status" class="form-select" required>
                                    <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Add Branch</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Assign User to Branch</h2>
                    <form method="post" action="<?= base_url('branches/assign-user') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="user_id">User</label>
                            <select id="user_id" name="user_id" class="form-select" required>
                                <option value="">Select user</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= esc((string) $user['user_id']) ?>">
                                        <?= esc($user['first_name'] . ' ' . $user['last_name']) ?> (<?= esc($user['username']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="assign_branch_id">Branch</label>
                            <select id="assign_branch_id" name="branch_id" class="form-select" required>
                                <option value="">Select branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?= esc((string) $branch['branch_id']) ?>"><?= esc($branch['branch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-outline-primary" type="submit">Assign</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Branch List</h2>
                <a class="btn btn-sm btn-primary" href="<?= base_url('branches/activity') ?>">View Branch Activity</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><?= esc($branch['branch_name']) ?></td>
                                <td>
                                    <?= esc(trim((string) $branch['address_street'])) ?>
                                    <?= esc(trim((string) $branch['address_barangay'])) ?>,
                                    <?= esc(trim((string) $branch['address_city'])) ?>,
                                    <?= esc(trim((string) $branch['address_province'])) ?>
                                </td>
                                <td><?= esc($branch['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
