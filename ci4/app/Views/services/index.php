<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h4 mb-3">Service Management</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Create Service</h2>
                    <form method="post" action="<?= base_url('services/create') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="plan_holder_id">Plan Holder</label>
                                <select id="plan_holder_id" name="plan_holder_id" class="form-select" required>
                                    <option value="">Select plan holder</option>
                                    <?php foreach ($plan_holders as $holder): ?>
                                        <option value="<?= esc((string) $holder['plan_holder_id']) ?>">
                                            <?= esc($holder['first_name'] . ' ' . $holder['last_name']) ?> (<?= esc($holder['unique_identifier']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="package_id">Package</label>
                                <select id="package_id" name="package_id" class="form-select" required>
                                    <option value="">Select package</option>
                                    <?php foreach ($packages as $package): ?>
                                        <option value="<?= esc((string) $package['package_id']) ?>"><?= esc($package['package_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="service_list_id">Service</label>
                                <select id="service_list_id" name="service_list_id" class="form-select">
                                    <option value="">No specific service</option>
                                    <?php foreach (($service_list ?? []) as $serviceDef): ?>
                                        <option value="<?= esc((string) $serviceDef['service_list_id']) ?>"><?= esc((string) $serviceDef['service_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="service_date">Service Date</label>
                                <input id="service_date" name="service_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="service_time">Service Time</label>
                                <input id="service_time" name="service_time" type="time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="burial_location">Burial Location</label>
                                <input id="burial_location" name="burial_location" type="text" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="service_status">Status</label>
                                <select id="service_status" name="status" class="form-select" required>
                                    <option value="pending">Pending</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="service_notes">Notes</label>
                                <textarea id="service_notes" name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Create Service</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Assign Staff</h2>
                    <form method="post" action="<?= base_url('services/assign-staff') ?>" class="mb-3">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="assign_service_id">Service</label>
                                <select id="assign_service_id" name="service_id" class="form-select" required>
                                    <option value="">Select service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= esc((string) $service['service_id']) ?>">#<?= esc((string) $service['service_id']) ?> - <?= esc($service['first_name'] . ' ' . $service['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="staff_id">Staff</label>
                                <select id="staff_id" name="staff_id" class="form-select" required>
                                    <option value="">Select staff</option>
                                    <?php foreach ($staff as $member): ?>
                                        <option value="<?= esc((string) $member['user_id']) ?>"><?= esc($member['first_name'] . ' ' . $member['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary mt-3" type="submit">Assign Staff</button>
                    </form>

                    <h2 class="h6">Add Cost</h2>
                    <form method="post" action="<?= base_url('services/add-cost') ?>" class="mb-3">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="cost_service_id">Service</label>
                                <select id="cost_service_id" name="service_id" class="form-select" required>
                                    <option value="">Select service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= esc((string) $service['service_id']) ?>">#<?= esc((string) $service['service_id']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="cost_description">Description</label>
                                <input id="cost_description" name="description" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="cost_amount">Amount</label>
                                <input id="cost_amount" name="amount" type="number" step="0.01" class="form-control" required>
                            </div>
                        </div>
                        <button class="btn btn-outline-success mt-3" type="submit">Add Cost</button>
                    </form>

                    <h2 class="h6">Add Add-On</h2>
                    <form method="post" action="<?= base_url('services/add-addon') ?>" class="mb-3">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="addon_service_id">Service</label>
                                <select id="addon_service_id" name="service_id" class="form-select" required>
                                    <option value="">Select service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= esc((string) $service['service_id']) ?>">#<?= esc((string) $service['service_id']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="addon_item_name">Item Name</label>
                                <input id="addon_item_name" name="item_name" type="text" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="addon_price">Price</label>
                                <input id="addon_price" name="price" type="number" step="0.01" class="form-control" required>
                            </div>
                        </div>
                        <button class="btn btn-outline-success mt-3" type="submit">Add Add-On</button>
                    </form>

                    <h2 class="h6">Update Progress</h2>
                    <form method="post" action="<?= base_url('services/update-status') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="status_service_id">Service</label>
                                <select id="status_service_id" name="service_id" class="form-select" required>
                                    <option value="">Select service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= esc((string) $service['service_id']) ?>">#<?= esc((string) $service['service_id']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="service_progress_status">Status</label>
                                <select id="service_progress_status" name="status" class="form-select" required>
                                    <option value="pending">Pending</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-3">Service List</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Package</th>
                            <th>Assigned Staff</th>
                            <th>Total Cost</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>#<?= esc((string) $service['service_id']) ?></td>
                                <td><?= esc($service['first_name'] . ' ' . $service['last_name']) ?> <br><small class="text-muted"><?= esc((string) $service['unique_identifier']) ?></small></td>
                                <td><?= esc((string) $service['package_name']) ?></td>
                                <td><?= esc(trim((string) (($service['staff_first_name'] ?? '') . ' ' . ($service['staff_last_name'] ?? '')))) ?></td>
                                <td><?= esc((string) $service['total_cost']) ?></td>
                                <td><?= esc((string) $service['service_date']) ?></td>
                                <td><?= esc((string) $service['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
