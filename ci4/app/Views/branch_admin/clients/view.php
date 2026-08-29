<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Client Details</h1>
            <small class="text-muted">Plan Holder #<?= esc((string) $client['plan_holder_id']) ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('branch-admin/client') ?>" class="btn btn-outline-secondary btn-sm">Back to List</a>
            <a href="<?= base_url('branch-admin/client/edit/' . $client['plan_holder_id']) ?>" class="btn btn-primary btn-sm">Edit Client</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs" id="clientViewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-panel" type="button" role="tab" aria-controls="details-panel" aria-selected="true">
                        Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab" aria-controls="history-panel" aria-selected="false">
                        Client History
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-3" id="clientViewTabsContent">
                <div class="tab-pane fade show active" id="details-panel" role="tabpanel" aria-labelledby="details-tab" tabindex="0">
                    <div class="row g-3">
                        <div class="col-xl-6">
                            <div class="border rounded p-3 h-100">
                                <h2 class="h6 mb-3">Personal Information</h2>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                            <tr><th class="w-50">Full Name</th><td><?= esc(trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''))) ?></td></tr>
                                            <tr>
                                                <th>Address</th>
                                                <td>
                                                    <?php
                                                        $address = trim(implode(', ', array_filter([
                                                            $client['address_no'] ?? '',
                                                            $client['address_street'] ?? '',
                                                            $client['address_barangay'] ?? '',
                                                            $client['address_city'] ?? '',
                                                        ])));
                                                    ?>
                                                    <?= esc($address !== '' ? $address : '-') ?>
                                                </td>
                                            </tr>
                                            <tr><th>Birthdate</th><td><?= esc((string) ($client['date_of_birth'] ?? '-')) ?></td></tr>
                                            <tr><th>Gender</th><td><?= esc((string) ($client['gender'] ?? '-')) ?></td></tr>
                                            <tr><th>Civil Status</th><td><?= esc((string) ($client['civil_status'] ?? '-')) ?></td></tr>
                                            <tr><th>Contact Number</th><td><?= esc((string) ($client['contact_number'] ?? '-')) ?></td></tr>
                                            <tr><th>Email</th><td><?= esc((string) ($client['email'] ?? '-')) ?></td></tr>
                                            <tr><th>Branch</th><td><?= esc((string) ($client['branch_name'] ?? '-')) ?></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="border rounded p-3 h-100">
                                <h2 class="h6 mb-3">Membership Details</h2>
                                <?php $plan = $client['plan'] ?? null; ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <tbody>
                                            <tr><th class="w-50">Program</th><td><?= esc((string) ($plan['program_name'] ?? $plan['package_name'] ?? \App\Services\MembershipService::PROGRAM_NAME)) ?></td></tr>
                                            <tr><th>Monthly Fee</th><td><?= esc((string) ($plan['monthly_fee'] ?? '-')) ?></td></tr>
                                            <tr><th>Start Date</th><td><?= esc((string) ($plan['start_date'] ?? '-')) ?></td></tr>
                                            <tr><th>Status</th><td><?= esc((string) ($plan['plan_status'] ?? '-')) ?></td></tr>
                                            <tr><th>Remaining Balance</th><td><?= esc((string) ($plan['remaining_balance'] ?? '-')) ?></td></tr>
                                            <tr><th>Months Paid</th><td><?= esc((string) ($plan['months_paid'] ?? '-')) ?></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h2 class="h6 mb-3">Beneficiaries</h2>
                                <?php $beneficiaries = $client['beneficiaries'] ?? []; ?>
                                <?php if (empty($beneficiaries)): ?>
                                    <p class="text-muted mb-0">No beneficiaries found.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <?php foreach (array_keys($beneficiaries[0]) as $column): ?>
                                                        <th><?= esc(ucwords(str_replace('_', ' ', (string) $column))) ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($beneficiaries as $beneficiary): ?>
                                                    <tr>
                                                        <?php foreach ($beneficiary as $value): ?>
                                                            <td><?= esc((string) ($value ?? '-')) ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="history-panel" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h2 class="h6 mb-3">Payment History</h2>
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Received By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($payments)): ?>
                                                <tr><td colspan="5" class="text-center text-muted">No payment records found.</td></tr>
                                            <?php endif; ?>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?= esc((string) ($payment['payment_date'] ?? '-')) ?></td>
                                                    <td><?= esc((string) ($payment['amount'] ?? '-')) ?></td>
                                                    <td><?= esc(strtoupper((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                                    <td><?= esc((string) ($payment['status'] ?? '-')) ?></td>
                                                    <td><?= esc(trim((string) (($payment['receiver_first_name'] ?? '') . ' ' . ($payment['receiver_last_name'] ?? '')))) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h2 class="h6 mb-3">Service History</h2>
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Service Type</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Total Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($services)): ?>
                                                <tr><td colspan="4" class="text-center text-muted">No service transactions found.</td></tr>
                                            <?php endif; ?>
                                            <?php foreach ($services as $service): ?>
                                                <tr>
                                                    <td><?= esc((string) ($service['service_type'] ?? '-')) ?></td>
                                                    <td><?= esc((string) ($service['service_date'] ?? '-')) ?></td>
                                                    <td><?= esc((string) ($service['status'] ?? '-')) ?></td>
                                                    <td><?= esc((string) ($service['total_cost'] ?? '-')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
