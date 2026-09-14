<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="mb-3 text-end">
    <div class="d-flex gap-2 justify-content-end">
        <a href="<?= base_url('admin/client-management') ?>" class="btn btn-outline-secondary btn-sm">Back to List</a>
        <a href="<?= base_url('admin/client-management/edit/' . $client['plan_holder_id']) ?>" class="btn btn-primary btn-sm">Edit Client</a>
    </div>
</div>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<section class="cs-panel">
    <div class="cs-panel__body">
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
                            <div class="cs-tablewrap">
                                <table class="cs-table cs-table--compact mb-0">
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
                                        <tr><th>Birthdate</th><td><?= cs_date($client['date_of_birth'] ?? null) ?></td></tr>
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
                            <div class="cs-tablewrap">
                                <table class="cs-table cs-table--compact mb-0">
                                    <tbody>
                                        <tr><th class="w-50">Package</th><td><?= esc((string) ($plan['package_name'] ?? '-')) ?></td></tr>
                                        <tr><th>Monthly Fee</th><td><?= isset($plan['monthly_fee']) ? cs_money($plan['monthly_fee']) : '-' ?></td></tr>
                                        <tr><th>Start Date</th><td><?= cs_date($plan['start_date'] ?? null) ?></td></tr>
                                        <tr><th>Status</th><td><?= isset($plan['plan_status']) ? cs_status((string) $plan['plan_status']) : '-' ?></td></tr>
                                        <tr><th>Remaining Balance</th><td><?= isset($plan['remaining_balance']) ? cs_money($plan['remaining_balance']) : '-' ?></td></tr>
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
                                <div class="cs-tablewrap">
                                    <table class="cs-table cs-table--compact mb-0">
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
                            <?php if (empty($payments)): ?>
                                <?= view('components/empty_state', ['icon' => 'ti-receipt', 'title' => 'No payment records found']) ?>
                            <?php else: ?>
                                <div class="cs-tablewrap">
                                    <table class="cs-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th class="cs-num">Amount</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Received By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td class="text-nowrap"><?= cs_date($payment['payment_date'] ?? null) ?></td>
                                                    <td class="cs-num"><?= cs_money($payment['amount'] ?? 0) ?></td>
                                                    <td><?= esc(strtoupper((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                                    <td><?= cs_status((string) ($payment['status'] ?? 'pending')) ?></td>
                                                    <td><?= esc(trim((string) (($payment['receiver_first_name'] ?? '') . ' ' . ($payment['receiver_last_name'] ?? '')))) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h2 class="h6 mb-3">Service History</h2>
                            <?php if (empty($services)): ?>
                                <?= view('components/empty_state', ['icon' => 'ti-truck', 'title' => 'No service transactions found']) ?>
                            <?php else: ?>
                                <div class="cs-tablewrap">
                                    <table class="cs-table">
                                        <thead>
                                            <tr>
                                                <th>Service Type</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th class="cs-num">Total Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($services as $service): ?>
                                                <tr>
                                                    <td><?= esc((string) ($service['service_type'] ?? '-')) ?></td>
                                                    <td class="text-nowrap"><?= cs_date($service['service_date'] ?? null) ?></td>
                                                    <td><?= cs_status((string) ($service['status'] ?? 'pending')) ?></td>
                                                    <td class="cs-num"><?= cs_money($service['total_cost'] ?? 0) ?></td>
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
        </div>
    </div>
</section>
<?= $this->endSection() ?>
