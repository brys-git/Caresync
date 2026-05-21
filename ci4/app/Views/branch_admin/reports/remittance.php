<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

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
            <form method="post" action="<?= site_url('/branch-admin/reports/remittance/generate') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="date_from" value="<?= esc($filters['date_from']) ?>">
                <input type="hidden" name="date_to" value="<?= esc($filters['date_to']) ?>">
                <input type="hidden" name="payment_method" value="<?= esc($filters['payment_method']) ?>">
                <input type="hidden" name="received_by" value="<?= esc((string) $filters['received_by']) ?>">
                <input type="hidden" name="action" value="print">
                <button type="submit" class="btn btn-outline-secondary">Print</button>
            </form>
            <form method="post" action="<?= site_url('/branch-admin/reports/remittance/generate') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="date_from" value="<?= esc($filters['date_from']) ?>">
                <input type="hidden" name="date_to" value="<?= esc($filters['date_to']) ?>">
                <input type="hidden" name="payment_method" value="<?= esc($filters['payment_method']) ?>">
                <input type="hidden" name="received_by" value="<?= esc((string) $filters['received_by']) ?>">
                <input type="hidden" name="action" value="pdf">
                <button type="submit" class="btn btn-outline-primary">Export PDF</button>
            </form>
            <form method="post" action="<?= site_url('/branch-admin/reports/remittance/generate') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="date_from" value="<?= esc($filters['date_from']) ?>">
                <input type="hidden" name="date_to" value="<?= esc($filters['date_to']) ?>">
                <input type="hidden" name="payment_method" value="<?= esc($filters['payment_method']) ?>">
                <input type="hidden" name="received_by" value="<?= esc((string) $filters['received_by']) ?>">
                <input type="hidden" name="action" value="csv">
                <button type="submit" class="btn btn-outline-success">Export Excel</button>
            </form>
        </div>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="post" action="<?= site_url('/branch-admin/reports/remittance/generate') ?>">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?= esc($filters['date_from']) ?>" required>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label class="form-label">Staff</label>
                        <select class="form-select" name="received_by">
                            <option value="0">All</option>
                            <?php foreach (($staff_options ?? []) as $staff): ?>
                                <option value="<?= (int) $staff['user_id'] ?>" <?= (int) $filters['received_by'] === (int) $staff['user_id'] ? 'selected' : '' ?>>
                                    <?= esc(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">Apply</button>
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
            <div class="card"><div class="card-body"><div class="text-muted small">Total Amount Collected</div><div class="h5 mb-0">PHP <?= number_format((float) ($summary['total_amount'] ?? 0), 2) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Cash Total</div><div class="h5 mb-0">PHP <?= number_format((float) ($summary['cash_total'] ?? 0), 2) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">GCash Total</div><div class="h5 mb-0">PHP <?= number_format((float) ($summary['gcash_total'] ?? 0), 2) ?></div></div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Remittance Transactions</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client Name</th>
                            <th>Months</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference / OR</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_rows ?? [])): ?>
                            <tr><td colspan="7" class="text-center py-3">No remittance transactions for selected filters.</td></tr>
                        <?php else: ?>
                            <?php foreach (($report_rows ?? []) as $row): ?>
                                <tr>
                                    <td><?= esc($row['payment_date'] ?? '-') ?></td>
                                    <td><?= esc(trim(((string) ($row['client_first'] ?? '')) . ' ' . ((string) ($row['client_last'] ?? '')))) ?></td>
                                    <td><?= esc((string) ((int) ($row['months_covered'] ?? 1))) ?></td>
                                    <td>PHP <?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                                    <td><?= esc(strtoupper((string) ($row['payment_method'] ?? ''))) ?></td>
                                    <td><?= esc($row['reference_number'] ?? ($row['official_receipt_number'] ?? '-')) ?></td>
                                    <td><?= esc(trim(((string) ($row['staff_first'] ?? '')) . ' ' . ((string) ($row['staff_last'] ?? '')))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-0">Total Remittance: PHP <?= number_format((float) ($total_remittance ?? 0), 2) ?></h5>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
