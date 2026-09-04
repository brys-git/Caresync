<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h1 class="h4 mb-3">Branch Analytics</h1>

    <?php $a = $analytics ?? []; ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Branch Members</div><div class="h4 mb-0"><?= esc((string) ($a['branch_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Active Members</div><div class="h4 mb-0"><?= esc((string) ($a['active_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Overdue Accounts</div><div class="h4 mb-0"><?= esc((string) ($a['overdue_accounts'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Staff Count</div><div class="h4 mb-0"><?= esc((string) ($a['staff_count'] ?? 0)) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">This Month's Collections</div><div class="h4 mb-0">P<?= esc(number_format((float) ($a['monthly_total'] ?? 0), 2)) ?></div></div></div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Initial Payments</div><div class="h4 mb-0"><?= esc((string) ($a['pending_initial_payments'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Service Requests</div><div class="h4 mb-0"><?= esc((string) ($a['pending_service_requests'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Ongoing Services</div><div class="h4 mb-0"><?= esc((string) ($a['ongoing_services'] ?? 0)) ?></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-3">Daily Collections This Month</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Date</th><th>Transactions</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php if (empty($a['daily_collections'])): ?>
                            <tr><td colspan="3" class="text-center py-3">No collections recorded yet this month.</td></tr>
                        <?php else: ?>
                            <?php foreach ($a['daily_collections'] as $row): ?>
                                <tr>
                                    <td><?= esc((string) ($row['date'] ?? '-')) ?></td>
                                    <td><?= esc((string) ($row['count'] ?? 0)) ?></td>
                                    <td>P<?= esc(number_format((float) ($row['total'] ?? 0), 2)) ?></td>
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
