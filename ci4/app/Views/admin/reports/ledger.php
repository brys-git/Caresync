<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <h1 class="h4 mb-1">Ledger of Plan Holder</h1>
    <p class="text-muted mb-3">Full payment/transaction history for a single plan holder.</p>

    <?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
    <?= $this->include('partials/reports_nav') ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= site_url(($reports_base_path ?? '/admin/reports') . '/ledger') ?>" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Search by name or unique ID</label>
                    <input type="text" name="q" class="form-control" value="<?= esc((string) ($query ?? '')) ?>" placeholder="e.g. Dela Cruz or PH-00015-20260418">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (! empty($query) && empty($ledger)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <?php if (empty($matches)): ?>
                    <p class="text-muted mb-0">No plan holders matched "<?= esc((string) $query) ?>".</p>
                <?php else: ?>
                    <p class="fw-semibold mb-2">Select a plan holder:</p>
                    <div class="list-group">
                        <?php foreach ($matches as $match): ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                               href="<?= site_url(($reports_base_path ?? '/admin/reports') . '/ledger?q=' . urlencode((string) $query) . '&plan_holder_id=' . (int) $match['plan_holder_id']) ?>">
                                <span><?= esc(trim((string) ($match['first_name'] ?? '') . ' ' . (string) ($match['last_name'] ?? ''))) ?> <small class="text-muted"><?= esc((string) ($match['unique_identifier'] ?? '-')) ?></small></span>
                                <span class="text-muted small"><?= esc((string) ($match['branch_name'] ?? '-')) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! empty($ledger)): ?>
        <?php $holder = $ledger['holder']; $plan = $ledger['plan']; $payments = $ledger['payments']; ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row gy-2">
                    <div class="col-md-4"><strong>Plan Holder:</strong> <?= esc(trim((string) ($holder['first_name'] ?? '') . ' ' . (string) ($holder['last_name'] ?? ''))) ?></div>
                    <div class="col-md-4"><strong>Unique ID:</strong> <?= esc((string) ($holder['unique_identifier'] ?? '-')) ?></div>
                    <div class="col-md-4"><strong>Branch:</strong> <?= esc((string) ($holder['branch_name'] ?? '-')) ?></div>
                    <div class="col-md-4"><strong>Contact:</strong> <?= esc((string) ($holder['contact_number'] ?? '-')) ?></div>
                    <div class="col-md-4"><strong>Monthly Fee:</strong> P<?= esc(number_format((float) ($plan['monthly_fee'] ?? 0), 2)) ?></div>
                    <div class="col-md-4"><strong>Months Paid:</strong> <?= esc((string) ($plan['months_paid'] ?? 0)) ?></div>
                    <div class="col-md-4"><strong>Remaining Balance:</strong> P<?= esc(number_format((float) ($plan['remaining_balance'] ?? 0), 2)) ?></div>
                    <div class="col-md-4"><strong>Next Due Date:</strong> <?= esc((string) ($plan['next_due_date'] ?? '-')) ?></div>
                    <div class="col-md-4"><strong>Plan Status:</strong> <?= esc(ucfirst((string) ($plan['status'] ?? '-'))) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference / OR</th>
                                <th>Staff Account</th>
                                <th>Status</th>
                                <th class="text-end">Running Total Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="7" class="text-center py-3">No payments recorded on this plan yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <?php $status = (string) ($payment['status'] ?? 'pending'); ?>
                                    <tr>
                                        <td><?= esc((string) $payment['payment_date']) ?></td>
                                        <td>P<?= esc(number_format((float) $payment['amount'], 2)) ?></td>
                                        <td><?= esc(strtoupper((string) $payment['payment_method'])) ?></td>
                                        <td><?= esc((string) ($payment['reference_number'] ?: ($payment['official_receipt_number'] ?: '-'))) ?></td>
                                        <td><?= esc(trim((string) ($payment['staff_first'] ?? '') . ' ' . (string) ($payment['staff_last'] ?? '')) ?: '-') ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>"><?= esc(ucfirst($status)) ?></span>
                                        </td>
                                        <td class="text-end">P<?= esc(number_format((float) ($payment['running_total'] ?? 0), 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
