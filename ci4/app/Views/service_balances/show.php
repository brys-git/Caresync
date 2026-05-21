<?= $this->extend($role_layout ?? 'layouts/plan_holder') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Service Balance</h1>
            <p class="text-muted mb-0">Separate balance continuation for the approved funeral service.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url($route_prefix) ?>">Back</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Member:</strong> <?= esc(trim((string) ($balance['first_name'] ?? '') . ' ' . (string) ($balance['last_name'] ?? ''))) ?></div>
                <div class="col-md-4"><strong>Service:</strong> <?= esc((string) ($balance['service_name'] ?? $balance['package_name'] ?? '-')) ?></div>
                <div class="col-md-4"><strong>Status:</strong> <?= esc((string) ($balance['status'] ?? '-')) ?></div>
                <div class="col-md-4"><strong>Package Cost:</strong> P<?= esc(number_format((float) ($balance['package_cost'] ?? 0), 2)) ?></div>
                <div class="col-md-4"><strong>Assistance:</strong> P<?= esc(number_format((float) ($balance['assistance_amount'] ?? 0), 2)) ?></div>
                <div class="col-md-4"><strong>Remaining:</strong> P<?= esc(number_format((float) ($balance['remaining_balance'] ?? 0), 2)) ?></div>
                <div class="col-md-4"><strong>Beneficiary:</strong> <?= esc((string) ($balance['beneficiary_name'] ?? '-')) ?></div>
                <div class="col-md-4"><strong>Relationship:</strong> <?= esc((string) ($balance['beneficiary_relationship'] ?? '-')) ?></div>
                <div class="col-md-4"><strong>Next Due:</strong> <?= esc((string) ($balance['next_due_date'] ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <?php if (! empty($application)): ?>
        <div class="card mb-3">
            <div class="card-header">Application Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Deceased name:</strong> <?= esc((string) ($application['deceased_name'] ?? '-')) ?></div>
                    <div class="col-md-4"><strong>Date of death:</strong> <?= esc((string) ($application['deceased_date_of_death'] ?? '-')) ?></div>
                    <div class="col-md-4"><strong>Relationship:</strong> <?= esc((string) ($application['relationship_to_deceased'] ?? '-')) ?></div>
                    <div class="col-md-6"><strong>Deceased address:</strong> <?= esc((string) ($application['deceased_address'] ?? '-')) ?></div>
                    <div class="col-md-6"><strong>Beneficiary contact:</strong> <?= esc((string) ($application['beneficiary_contact'] ?? '-')) ?></div>
                    <div class="col-12"><strong>Application notes:</strong> <?= esc((string) ($application['application_notes'] ?? '-')) ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! empty($documents)): ?>
        <div class="card mb-3">
            <div class="card-header">Submitted Documents</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($documents as $document): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= esc((string) ($document['original_name'] ?? 'Unnamed document')) ?></span>
                            <?php if ((int) session('role_id') === 2): ?>
                                <a href="<?= site_url('/branch-admin/service-package/requests/document/' . (int) $document['document_id']) ?>" class="btn btn-sm btn-outline-primary">Download</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int) session('role_id') === 4 && (string) ($balance['status'] ?? '') === 'pending_acknowledgment'): ?>
        <div class="card mb-3">
            <div class="card-header">Beneficiary Acknowledgment</div>
            <div class="card-body">
                <form method="post" action="<?= site_url('client/service-balances/acknowledge/' . (int) ($balance['service_balance_id'] ?? 0)) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Acknowledgment Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Accept remaining balance and installment terms."></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Acknowledge Remaining Balance</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Recorded Payments</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="5" class="text-center">No payments recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= esc((string) ($payment['paid_at'] ?? '-')) ?></td>
                                <td>P<?= esc(number_format((float) ($payment['amount'] ?? 0), 2)) ?></td>
                                <td><?= esc((string) ($payment['payment_method'] ?? '-')) ?></td>
                                <td><?= esc((string) ($payment['reference_number'] ?? '-')) ?></td>
                                <td><?= esc((string) ($payment['status'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (in_array((int) session('role_id'), [2, 4], true)): ?>
        <div class="card mt-3">
            <div class="card-header">Record Payment</div>
            <div class="card-body">
                <form method="post" action="<?= (int) session('role_id') === 2 ? site_url('branch-admin/service-balances/pay/' . (int) ($balance['service_balance_id'] ?? 0)) : site_url('client/service-balances/pay/' . (int) ($balance['service_balance_id'] ?? 0)) ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <input class="form-control" type="number" step="0.01" min="0.01" name="amount" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <input class="form-control" type="text" name="payment_method" placeholder="cash / gcash / bank">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference Number</label>
                            <input class="form-control" type="text" name="reference_number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">Save Payment</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>