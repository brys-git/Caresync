<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Reports Module</h1>
            <small class="text-muted">Scope: <?= esc($report_scope) ?></small>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Payments</div><div class="h4 mb-0"><?= esc((string) count($payments)) ?></div></div></div>
        </div>
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Branches</div><div class="h4 mb-0"><?= esc((string) count($branches)) ?></div></div></div>
        </div>
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Services</div><div class="h4 mb-0"><?= esc((string) count($services)) ?></div></div></div>
        </div>
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Staff Activity Rows</div><div class="h4 mb-0"><?= esc((string) count($staff_activity)) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Total Members</div><div class="h4 mb-0"><?= esc((string) ($summary['member_status']['total_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Active Members</div><div class="h4 mb-0"><?= esc((string) ($summary['member_status']['active_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Inactive Members</div><div class="h4 mb-0"><?= esc((string) ($summary['member_status']['inactive_members'] ?? 0)) ?></div></div></div>
        </div>
        <div class="col-lg-3">
            <div class="card"><div class="card-body"><div class="text-muted small">Monthly Collections</div><div class="h4 mb-0"><?= esc(number_format((float) ($summary['payment_breakdown']['total_amount'] ?? 0), 2)) ?></div></div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Payments Report</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Date</th><th>Branch</th><th>Receiver</th><th>Months</th><th>Amount</th><th>Method</th><th>Reference / OR</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= esc($payment['payment_date']) ?></td>
                                <td><?= esc($payment['branch_name']) ?></td>
                                <td><?= esc($payment['client_first'] . ' ' . $payment['client_last']) ?></td>
                                <td><?= esc((string) ((int) ($payment['months_covered'] ?? 1))) ?></td>
                                <td><?= esc((string) $payment['amount']) ?></td>
                                <td><?= esc(strtoupper((string) $payment['payment_method'])) ?></td>
                                <td><?= esc((string) ($payment['reference_number'] ?? ($payment['official_receipt_number'] ?? '-'))) ?></td>
                                <td><?= esc((string) $payment['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Monthly Collections</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Month</th><th>Transactions</th><th>Total Amount</th></tr></thead>
                    <tbody>
                        <?php foreach (($summary['monthly_collections'] ?? []) as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['month_label'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['total_transactions'] ?? 0)) ?></td>
                                <td><?= esc(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Delinquent Accounts</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Client</th><th>Branch</th><th>Remaining Balance</th><th>Months Paid</th><th>Next Due</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach (($summary['delinquent_accounts'] ?? []) as $row): ?>
                            <tr>
                                <td><?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?></td>
                                <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                <td><?= esc(number_format((float) ($row['remaining_balance'] ?? 0), 2)) ?></td>
                                <td><?= esc((string) ($row['months_paid'] ?? 0)) ?></td>
                                <td><?= esc((string) ($row['next_due_date'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['plan_status'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Service Usage Statistics</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Service</th><th>Total</th><th>Completed</th><th>Cancelled</th></tr></thead>
                    <tbody>
                        <?php foreach (($summary['service_usage'] ?? []) as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['service_name'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['total_services'] ?? 0)) ?></td>
                                <td><?= esc((string) ($row['completed_services'] ?? 0)) ?></td>
                                <td><?= esc((string) ($row['cancelled_services'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Payment Trends</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Month</th><th>Cash</th><th>GCash</th></tr></thead>
                    <tbody>
                        <?php foreach (($summary['payment_trends'] ?? []) as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['month_label'] ?? '-')) ?></td>
                                <td><?= esc(number_format((float) ($row['cash_total'] ?? 0), 2)) ?></td>
                                <td><?= esc(number_format((float) ($row['gcash_total'] ?? 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Branch Performance</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Branch</th><th>Revenue</th><th>Staff</th><th>Clients</th><th>Services</th></tr></thead>
                    <tbody>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><?= esc($branch['branch_name']) ?></td>
                                <td><?= esc((string) $branch['revenue']) ?></td>
                                <td><?= esc((string) $branch['staff_count']) ?></td>
                                <td><?= esc((string) $branch['client_count']) ?></td>
                                <td><?= esc((string) $branch['service_count']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Services Report</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>ID</th><th>Client</th><th>Branch</th><th>Type</th><th>Status</th><th>Date</th><th>Total Cost</th></tr></thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>#<?= esc((string) $service['service_id']) ?></td>
                                <td><?= esc($service['first_name'] . ' ' . $service['last_name']) ?></td>
                                <td><?= esc($service['branch_name']) ?></td>
                                <td><?= esc((string) $service['service_type']) ?></td>
                                <td><?= esc((string) $service['status']) ?></td>
                                <td><?= esc((string) $service['service_date']) ?></td>
                                <td><?= esc((string) $service['total_cost']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Clients Report</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Client</th><th>Identifier</th><th>Branch</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?= esc($client['first_name'] . ' ' . $client['last_name']) ?></td>
                                <td><?= esc((string) $client['unique_identifier']) ?></td>
                                <td><?= esc($client['branch_name']) ?></td>
                                <td><?= esc((string) $client['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-3">Staff Activity Report</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Staff</th><th>Tasks</th><th>Payments Handled</th><th>Services Handled</th></tr></thead>
                    <tbody>
                        <?php foreach ($staff_activity as $staff): ?>
                            <tr>
                                <td><?= esc($staff['first_name'] . ' ' . $staff['last_name']) ?></td>
                                <td><?= esc((string) $staff['tasks_assigned']) ?></td>
                                <td><?= esc((string) $staff['payments_handled']) ?></td>
                                <td><?= esc((string) $staff['services_handled']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
