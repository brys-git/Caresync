<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<style>
    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Remittance Report</h4>
        <div class="no-print d-flex gap-2">
            <a
                href="<?= site_url('/staff/reports?' . http_build_query(array_merge($filters, ['mode' => 'print']))) ?>"
                class="btn btn-outline-secondary"
                target="_blank"
                rel="noopener"
            >Print</a>
            <a
                href="<?= site_url('/staff/reports?' . http_build_query(array_merge($filters, ['mode' => 'pdf']))) ?>"
                class="btn btn-outline-primary"
            >Export PDF</a>
            <a
                href="<?= site_url('/staff/reports?' . http_build_query(array_merge($filters, ['mode' => 'csv']))) ?>"
                class="btn btn-outline-success"
            >Export Excel</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="get" action="<?= site_url('/staff/reports') ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?= esc($filters['date_from']) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="<?= esc($filters['date_to']) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="" <?= $filters['payment_method'] === '' ? 'selected' : '' ?>>All</option>
                            <option value="cash" <?= $filters['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="gcash" <?= $filters['payment_method'] === 'gcash' ? 'selected' : '' ?>>GCash</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>All</option>
                            <option value="paid" <?= $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Received By</label>
                        <select class="form-select" name="received_by">
                            <option value="0">All</option>
                            <?php foreach (($staff_options ?? []) as $staff): ?>
                                <option value="<?= (int) $staff['user_id'] ?>" <?= (int) $filters['received_by'] === (int) $staff['user_id'] ? 'selected' : '' ?>>
                                    <?= esc(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?= esc($filters['search']) ?>" placeholder="Plan holder / ref #">
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="<?= site_url('/staff/reports') ?>" class="btn btn-outline-secondary">Reset</a>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Transactions</div><div class="h5 mb-0"><?= (int) ($summary['total_transactions'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Collected</div><div class="h5 mb-0">PHP <?= number_format((float) ($summary['total_collected'] ?? 0), 2) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Pending</div><div class="h5 mb-0">PHP <?= number_format((float) ($summary['total_pending'] ?? 0), 2) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Cancelled</div><div class="h5 mb-0">PHP <?= number_format((float) ($summary['total_cancelled'] ?? 0), 2) ?></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Remittance Transactions (View Only)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Plan Holder Name</th>
                            <th>Months</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Payment Method</th>
                            <th>Reference / OR</th>
                            <th>Received By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_rows ?? [])): ?>
                            <tr><td colspan="8" class="text-center py-3">No payment records found for selected filters.</td></tr>
                        <?php else: ?>
                            <?php foreach (($report_rows ?? []) as $row): ?>
                                <?php $status = strtolower((string) ($row['status'] ?? '')); ?>
                                <tr>
                                    <td><?= esc(trim(((string) ($row['client_first'] ?? '')) . ' ' . ((string) ($row['client_last'] ?? '')))) ?></td>
                                    <td><?= esc((string) ((int) ($row['months_covered'] ?? 1))) ?></td>
                                    <td>PHP <?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                                    <td><?= esc((string) ($row['payment_date'] ?? '-')) ?></td>
                                    <td><?= esc(strtoupper((string) ($row['payment_method'] ?? '-'))) ?></td>
                                    <td><?= esc((string) ($row['reference_number'] ?? ($row['official_receipt_number'] ?? '-'))) ?></td>
                                    <td><?= esc(trim(((string) ($row['staff_first'] ?? '')) . ' ' . ((string) ($row['staff_last'] ?? '')))) ?></td>
                                    <td>
                                        <?php if ($status === 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php elseif ($status === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelled</span>
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
