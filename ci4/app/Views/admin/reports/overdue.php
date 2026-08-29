<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="h4 mb-0">Overdue Report</h1>
        <a class="btn btn-outline-secondary" href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/overdue?mode=csv') ?>">Export CSV</a>
    </div>
    <p class="text-muted mb-3">Plan holders behind on their contribution, oldest due date first.</p>

    <?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
    <?= $this->include('partials/reports_nav') ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Plan Holder</th>
                            <th>Branch</th>
                            <th>Contact</th>
                            <th>Remaining Balance</th>
                            <th>Months Paid</th>
                            <th>Overdue Months</th>
                            <th>Days Overdue</th>
                            <th>Next Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="text-center py-3">No overdue accounts found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?><br><small class="text-muted"><?= esc((string) ($row['unique_identifier'] ?? '-')) ?></small></td>
                                    <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($row['contact_number'] ?? '-')) ?></td>
                                    <td>P<?= esc(number_format((float) ($row['remaining_balance'] ?? 0), 2)) ?></td>
                                    <td><?= esc((string) ($row['months_paid'] ?? 0)) ?></td>
                                    <td><?= esc((string) ($row['overdue_months'] ?? 0)) ?></td>
                                    <td><span class="badge text-bg-danger"><?= esc((string) ($row['days_overdue'] ?? 0)) ?> days</span></td>
                                    <td><?= esc((string) ($row['next_due_date'] ?? '-')) ?></td>
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
