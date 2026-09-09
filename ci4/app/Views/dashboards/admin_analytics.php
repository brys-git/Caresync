<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h1 class="h4 mb-3">System Analytics</h1>

    <?php $a = $analytics ?? []; ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Members</div><div class="h4 mb-0"><?= esc((string) ($a['total_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Active Members</div><div class="h4 mb-0"><?= esc((string) ($a['active_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Delinquent Members</div><div class="h4 mb-0"><?= esc((string) ($a['delinquent_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Suspended Members</div><div class="h4 mb-0"><?= esc((string) ($a['suspended_members'] ?? 0)) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Collections (All Time)</div><div class="h4 mb-0">P<?= esc(number_format((float) ($a['total_collections'] ?? 0), 2)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Payment Approvals</div><div class="h4 mb-0"><?= esc((string) ($a['pending_approvals'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Pending Service Requests</div><div class="h4 mb-0"><?= esc((string) ($a['pending_service_requests'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Branches / Staff / Collectors</div><div class="h4 mb-0"><?= esc((string) ($a['total_branches'] ?? 0)) ?> / <?= esc((string) ($a['total_staff'] ?? 0)) ?> / <?= esc((string) ($a['total_collectors'] ?? 0)) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Monthly Collections (Last 12 Months)</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Month</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <?php if (empty($a['monthly_collections'])): ?>
                                    <tr><td colspan="2" class="text-center py-3">No collections recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($a['monthly_collections'] as $row): ?>
                                        <tr>
                                            <td><?= esc((string) ($row['month'] ?? '-')) ?></td>
                                            <td class="text-end">P<?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Top Branches by Membership</h2>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Branch</th><th class="text-end">Members</th></tr></thead>
                            <tbody>
                                <?php if (empty($a['top_branches'])): ?>
                                    <tr><td colspan="2" class="text-center py-3">No branch data yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($a['top_branches'] as $row): ?>
                                        <tr>
                                            <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                            <td class="text-end"><?= esc((string) ($row['member_count'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
