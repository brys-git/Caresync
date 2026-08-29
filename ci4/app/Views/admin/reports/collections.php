<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="h4 mb-0">Collections Report</h1>
        <a class="btn btn-outline-secondary" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/collections?mode=csv&date_from=' . urlencode((string) ($filters['date_from'] ?? '')) . '&date_to=' . urlencode((string) ($filters['date_to'] ?? ''))) ?>">Export CSV</a>
    </div>
    <p class="text-muted mb-3">Amounts collected per collector (whoever recorded the payment - Staff, Branch Admin, or Collector).</p>

    <?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
    <?= $this->include('partials/reports_nav') ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= site_url(($reports_base_path ?? '/admin/reports') . '/collections') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= esc((string) ($filters['date_from'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= esc((string) ($filters['date_to'] ?? '')) ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Collector</th>
                            <th>Role</th>
                            <th>Transactions</th>
                            <th class="text-end">Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="4" class="text-center py-3">No collections found for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?></td>
                                    <td><?= esc((string) ($row['role_name'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($row['transaction_count'] ?? 0)) ?></td>
                                    <td class="text-end">P<?= esc(number_format((float) ($row['total_collected'] ?? 0), 2)) ?></td>
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
