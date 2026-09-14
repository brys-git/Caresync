<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'unregistered');
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$user = $access['user'] ?? [];
$beneficiaries = $beneficiaries ?? [];
?>
<?php
/*
 * "Restricted" blur+overlay has no caresync.css equivalent (it's not a
 * status/table/panel pattern, it's a locked-preview upsell) so it's kept
 * as a small scoped style block rather than left un-migrated as raw
 * Bootstrap or invented as a new global class.
 */
?>
<style>
    .restricted-wrap { position: relative; }
    .restricted-blur { filter: blur(4px); pointer-events: none; user-select: none; }
    .restricted-modal {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.55);
    }
</style>

<?php if ($state === 'unregistered'): ?>
    <div class="restricted-wrap">
        <div class="restricted-blur">
            <section class="cs-panel">
                <div class="cs-panel__head">
                    <h2 class="cs-panel__title">Membership Record</h2>
                </div>
                <div class="cs-panel__body">
                    <div class="d-flex justify-content-between align-items-baseline gap-3 mb-2 pb-2" style="border-bottom:1px solid var(--cs-line-soft)">
                        <span class="cs-muted">Package</span>
                        <span class="fw-semibold text-end"><?= esc((string) ($membership_summary['program_name'] ?? $program['name'] ?? 'Damayan Burial Program')) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline gap-3 mb-2 pb-2" style="border-bottom:1px solid var(--cs-line-soft)">
                        <span class="cs-muted">Status</span>
                        <span class="fw-semibold text-end">Restricted</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline gap-3">
                        <span class="cs-muted">Monthly Fee</span>
                        <span class="fw-semibold text-end"><?= cs_money($program['monthly_fee'] ?? 240) ?></span>
                    </div>
                </div>
            </section>
        </div>
        <div class="restricted-modal">
            <div class="cs-panel shadow" style="max-width: 420px;">
                <div class="cs-panel__body text-center">
                    <h5 class="mb-2">Register to access this feature</h5>
                    <p class="cs-muted">Membership details are available after plan registration.</p>
                    <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register Now</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php $membershipState = strtolower((string) ($plan['membership_state'] ?? 'active')); ?>

    <section class="cs-panel">
        <div class="cs-panel__head">
            <h2 class="cs-panel__title">Membership Status Summary</h2>
        </div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-6"><div class="border rounded p-3 bg-light"><small class="text-muted d-block">Package</small><strong><?= esc((string) ($membership_summary['program_name'] ?? $program['name'] ?? 'Damayan Burial Program')) ?></strong></div></div>
                <div class="col-md-6"><div class="border rounded p-3 bg-light"><small class="text-muted d-block">Status</small><strong><?= cs_status($membershipState) ?></strong></div></div>
            </div>
        </div>
    </section>

    <section class="cs-panel mt-3">
        <div class="cs-panel__head">
            <h2 class="cs-panel__title">Coverage &amp; Payment Schedule</h2>
        </div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Payment Coverage Until</small><strong><?= esc((string) ($plan['payment_coverage_until'] ?? date('F Y'))) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Next Due Date</small><strong><?= cs_date($plan['next_due_date'] ?? date('Y-m-d', strtotime('+1 month'))) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Overdue Months</small><strong><span class="badge text-bg-<?= ((int) ($plan['overdue_months'] ?? 0)) > 0 ? 'warning' : 'success' ?>"><?= esc((string) ((int) ($plan['overdue_months'] ?? 0))) ?></span></strong></div></div>
                <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">Monthly Contribution</small><strong><?= cs_money($plan['monthly_fee'] ?? ($program['monthly_fee'] ?? 240)) ?></strong></div></div>
                <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">Member Identifier</small><strong><?= esc((string) ($plan_holder['unique_identifier'] ?? 'Not assigned')) ?></strong></div></div>
                <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">Months Paid</small><strong><?= esc((string) ((int) ($plan['months_paid'] ?? 0))) ?></strong></div></div>
                <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">Start Date</small><strong><?= cs_date($plan['start_date'] ?? date('Y-m-d')) ?></strong></div></div>
            </div>
            <?php if ($state === 'awaiting_activation'): ?>
                <div class="alert alert-warning mt-3 mb-0">Your registration is complete. Please submit your initial payment to activate your membership.</div>
            <?php endif; ?>
            <?php if (($access['initial_payment_status'] ?? 'none') === 'cancelled'): ?>
                <div class="alert alert-danger mt-3 mb-0">Payment rejected. Please resubmit.</div>
            <?php endif; ?>
            <?php if ($membershipState === 'delinquent'): ?>
                <div class="alert alert-warning mt-3 mb-0"><strong>Your membership is delinquent.</strong> Please update your monthly contributions. Services are available for the first 2 months of delinquency.</div>
            <?php endif; ?>
            <?php if ($membershipState === 'suspended'): ?>
                <div class="alert alert-danger mt-3 mb-0"><strong>Your membership is suspended.</strong> Please contact our office to settle your account and restore your membership.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="cs-panel mt-3">
        <div class="cs-panel__head">
            <h2 class="cs-panel__title">Registration Details</h2>
            <a href="<?= base_url('plan-registration') ?>" class="btn btn-outline-primary btn-sm">Edit Details</a>
        </div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">ID Control No.</small><strong><?= esc((string) ($plan_holder['id_control_no'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Coordinator</small><strong><?= esc((string) ($plan_holder['coordinator'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Date of Application</small><strong><?= cs_date($plan_holder['application_date'] ?? null) ?></strong></div></div>

                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Last Name</small><strong><?= esc((string) ($user['last_name'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Given Name</small><strong><?= esc((string) ($user['first_name'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Middle Name</small><strong><?= esc((string) ($user['middle_name'] ?? '-')) ?></strong></div></div>

                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Address No.</small><strong><?= esc((string) ($plan_holder['address_no'] ?? '-')) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Street</small><strong><?= esc((string) ($plan_holder['address_street'] ?? '-')) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Barangay</small><strong><?= esc((string) ($plan_holder['address_barangay'] ?? '-')) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Town/City</small><strong><?= esc((string) ($plan_holder['address_city'] ?? '-')) ?></strong></div></div>

                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Date of Birth</small><strong><?= cs_date($plan_holder['date_of_birth'] ?? null) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Place of Birth</small><strong><?= esc((string) ($plan_holder['place_of_birth'] ?? '-')) ?></strong></div></div>
                <div class="col-md-2"><div class="border rounded p-3"><small class="text-muted d-block">Age</small><strong><?= esc((string) ($plan_holder['age'] ?? '-')) ?></strong></div></div>
                <div class="col-md-2"><div class="border rounded p-3"><small class="text-muted d-block">Gender</small><strong><?= esc((string) ($plan_holder['gender'] ?? '-')) ?></strong></div></div>
                <div class="col-md-2"><div class="border rounded p-3"><small class="text-muted d-block">Civil Status</small><strong><?= esc((string) ($plan_holder['civil_status'] ?? '-')) ?></strong></div></div>

                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Citizenship</small><strong><?= esc((string) ($plan_holder['citizenship'] ?? '-')) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Height</small><strong><?= esc((string) ($plan_holder['height'] ?? '-')) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Weight</small><strong><?= esc((string) ($plan_holder['weight'] ?? '-')) ?></strong></div></div>
                <div class="col-md-3"><div class="border rounded p-3"><small class="text-muted d-block">Branch</small><strong><?= esc((string) ($branch_info['branch_name'] ?? '-')) ?></strong></div></div>

                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Name of Spouse</small><strong><?= esc((string) ($plan_holder['spouse_name'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Spouse Birthdate</small><strong><?= cs_date($plan_holder['spouse_birthdate'] ?? null) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Spouse Occupation</small><strong><?= esc((string) ($plan_holder['spouse_occupation'] ?? '-')) ?></strong></div></div>

                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Cellphone No.</small><strong><?= esc((string) ($user['contact_number'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Email</small><strong><?= esc((string) ($user['email'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Senior Citizen ID No.</small><strong><?= esc((string) ($plan_holder['senior_citizen_id'] ?? '-')) ?></strong></div></div>
                <div class="col-md-6"><div class="border rounded p-3"><small class="text-muted d-block">Organization Affiliation</small><strong><?= esc((string) ($plan_holder['organization_affiliation'] ?? '-')) ?></strong></div></div>
            </div>

            <h6 class="mt-4">Emergency Contact</h6>
            <div class="row g-3">
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Name</small><strong><?= esc((string) ($plan_holder['emergency_contact_name'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Contact #</small><strong><?= esc((string) ($plan_holder['emergency_contact_number'] ?? '-')) ?></strong></div></div>
                <div class="col-md-4"><div class="border rounded p-3"><small class="text-muted d-block">Address</small><strong><?= esc((string) ($plan_holder['emergency_contact_address'] ?? '-')) ?></strong></div></div>
            </div>

            <h6 class="mt-4">Beneficiaries</h6>
            <?php if (empty($beneficiaries)): ?>
                <?= view('components/empty_state', [
                    'icon'  => 'ti-users',
                    'title' => 'No beneficiaries listed',
                    'text'  => 'Beneficiaries added during registration appear here.',
                ]) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Complete Name</th>
                                <th>Birthday</th>
                                <th>Relationship</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beneficiaries as $beneficiary): ?>
                                <?php
                                $nameParts = array_filter([
                                    (string) ($beneficiary['first_name'] ?? ''),
                                    (string) ($beneficiary['middle_name'] ?? ''),
                                    (string) ($beneficiary['last_name'] ?? ''),
                                    (string) ($beneficiary['name_extension'] ?? ''),
                                ]);
                                $fullName = trim(implode(' ', $nameParts));
                                ?>
                                <tr>
                                    <td><?= esc($fullName) ?></td>
                                    <td><?= cs_date($beneficiary['date_of_birth'] ?? null) ?></td>
                                    <td><?= esc((string) ($beneficiary['relationship'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
<?= $this->endSection() ?>
