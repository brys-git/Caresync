<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Payment Monitoring</h1>
        <p class="text-muted mb-0">System-wide transaction visibility across all branches.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= base_url('admin/payment-monitoring') ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="" <?= ($filters['status'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                            <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="payment_method">Method</label>
                        <select id="payment_method" name="payment_method" class="form-select">
                            <option value="" <?= ($filters['payment_method'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                            <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="gcash" <?= ($filters['payment_method'] ?? '') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" class="form-select">
                            <option value="0">All Branches</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                    <?= esc((string) $branch['branch_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="date_from">Date From</label>
                        <input id="date_from" name="date_from" type="date" class="form-control" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="date_to">Date To</label>
                        <input id="date_to" name="date_to" type="date" class="form-control" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-2">
        <a class="btn btn-outline-secondary" href="<?= base_url('admin/payment-monitoring/export?' . http_build_query($filters ?? [])) ?>">Export CSV</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plan Holder</th>
                            <th>Branch</th>
                            <th>Months</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference / OR</th>
                            <th>Status</th>
                            <th>Proof</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php $status = strtolower((string) ($row['status'] ?? 'pending')); ?>
                            <tr>
                                <td>#<?= esc((string) $row['payment_id']) ?></td>
                                <td>
                                    <?= esc((string) ($row['first_name'] . ' ' . $row['last_name'])) ?><br>
                                    <small class="text-muted"><?= esc((string) ($row['unique_identifier'] ?: 'No ID')) ?></small>
                                </td>
                                <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                <td><?= esc((string) ((int) ($row['months_covered'] ?? 1))) ?></td>
                                <td>P<?= esc(number_format((float) $row['amount'], 2)) ?></td>
                                <td><?= esc((string) $row['payment_date']) ?></td>
                                <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                                <td><?= esc((string) ($row['reference_number'] ?: ($row['official_receipt_number'] ?: '-'))) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>">
                                        <?= esc(ucfirst($status)) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (! empty($supports_proof_upload) && ! empty($row['proof_image'] ?? null)): ?>
                                        <a href="<?= base_url('uploads/payment-proofs/' . $row['proof_image']) ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string) ($row['remarks'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">No payment records found for selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
