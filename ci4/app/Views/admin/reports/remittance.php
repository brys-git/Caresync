<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="h4 mb-0">Remittance Report</h1>
        <a class="btn btn-outline-secondary" href="<?= site_url('/admin/reports/remittance?mode=csv&' . http_build_query($filters ?? [])) ?>">Export CSV</a>
    </div>
    <p class="text-muted mb-3">System-wide payment remittance across all branches. Branch Admin and Staff have their own branch-scoped version with print/PDF export for cash reconciliation.</p>

    <?php $this->setData(['reports_base_path' => '/admin/reports']) ?>
    <?= $this->include('partials/reports_nav') ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= site_url('/admin/reports/remittance') ?>" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Method</label>
                    <select class="form-select" name="payment_method">
                        <option value="" <?= ($filters['payment_method'] ?? '') === '' ? 'selected' : '' ?>>All</option>
                        <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="gcash" <?= ($filters['payment_method'] ?? '') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select class="form-select" name="branch_id">
                        <option value="0">All Branches</option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>>
                                <?= esc((string) $branch['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-0">Total Remittance: P<?= esc(number_format((float) ($total_remittance ?? 0), 2)) ?></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client Name</th>
                            <th>Unique ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference / OR</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="text-center py-3">No remittance transactions for selected filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= esc((string) ($row['payment_date'] ?? '-')) ?></td>
                                    <td><?= esc(trim((string) ($row['client_first'] ?? '') . ' ' . (string) ($row['client_last'] ?? ''))) ?></td>
                                    <td><?= esc((string) ($row['unique_identifier'] ?? '-')) ?></td>
                                    <td>P<?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                                    <td><?= esc(strtoupper((string) ($row['payment_method'] ?? ''))) ?></td>
                                    <td><?= esc((string) ($row['reference_number'] ?? ($row['official_receipt_number'] ?? '-'))) ?></td>
                                    <td><?= esc(trim((string) ($row['staff_first'] ?? '') . ' ' . (string) ($row['staff_last'] ?? ''))) ?></td>
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
