<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $s = $summary ?? []; ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= esc((string) ($page_title ?? 'Collector Dashboard')) ?></h1>
        <p class="text-muted mb-0">Payments you've personally recorded.</p>
    </div>

    <div class="alert alert-info" role="alert">
        Dedicated collection-entry, remittance, and commission-tracking screens for this role are still being built.
        This overview reflects payments already on record where you're the one who received them.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Collected Today</div><div class="h4 mb-0">P<?= esc(number_format((float) ($s['today_collected'] ?? 0), 2)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Collected This Month</div><div class="h4 mb-0">P<?= esc(number_format((float) ($s['month_collected'] ?? 0), 2)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Estimated Commission (This Month)</div><div class="h4 mb-0 text-success">P<?= esc(number_format((float) ($s['estimated_commission'] ?? 0), 2)) ?></div></div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Transactions</div><div class="h4 mb-0"><?= esc((string) ($s['transaction_count'] ?? 0)) ?></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Recent Payments You Recorded</div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Plan Holder</th><th>Method</th><th>Date</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <?php $rows = $s['recent_payments'] ?? []; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No payments recorded under your account yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= esc(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: '-') ?></td>
                                <td><?= esc(strtoupper((string) ($row['payment_method'] ?? '-'))) ?></td>
                                <td><?= esc((string) ($row['payment_date'] ?? '-')) ?></td>
                                <td class="text-end">P<?= esc(number_format((float) ($row['amount'] ?? 0), 2)) ?></td>
                                <td><span class="badge bg-<?= ($row['status'] ?? '') === 'paid' ? 'success' : 'secondary' ?>"><?= esc(ucfirst((string) ($row['status'] ?? '-'))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
