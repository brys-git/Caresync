<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'unregistered');
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$user = $access['user'] ?? [];
$beneficiaries = $beneficiaries ?? [];

// One label/value tile; $html is already-escaped markup (esc()/cs_date()/cs_status()/cs_money()).
$fact = static function (string $label, string $html, string $col = 'col-md-4'): string {
    return '<div class="' . $col . '"><div class="cs-fact"><span class="cs-fact__label">' . esc($label) . '</span><span class="cs-fact__value">' . $html . '</span></div></div>';
};
$val = static fn ($v): string => esc((string) ($v === null || $v === '' ? '—' : $v));
?>

<?php if ($state === 'unregistered'): ?>
    <div class="cs-restricted">
        <div class="cs-restricted__blur">
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Membership record</h2></div>
                <div class="cs-panel__body">
                    <div class="cs-kv"><span class="cs-muted">Package</span><strong><?= esc((string) ($membership_summary['program_name'] ?? $program['name'] ?? 'Damayan Burial Program')) ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Status</span><strong>Restricted</strong></div>
                    <div class="cs-kv"><span class="cs-muted">Monthly fee</span><strong><?= cs_money($program['monthly_fee'] ?? 240) ?></strong></div>
                </div>
            </section>
        </div>
        <div class="cs-restricted__overlay">
            <div class="cs-panel cs-restricted__card">
                <div class="cs-panel__body text-center">
                    <h2 class="cs-panel__title mb-2">Register to access this feature</h2>
                    <p class="cs-muted">Membership details are available after plan registration.</p>
                    <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register now</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php $membershipState = strtolower((string) ($plan['membership_state'] ?? 'active')); ?>

    <div class="row g-3">
        <div class="col-lg-4 order-lg-2">
            <section class="cs-panel mb-3">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Membership status</h2><?= cs_status($membershipState) ?></div>
                <div class="cs-panel__body">
                    <div class="cs-kv"><span class="cs-muted">Package</span><strong class="text-end"><?= esc((string) ($membership_summary['program_name'] ?? $program['name'] ?? 'Damayan Burial Program')) ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Member identifier</span><strong><?= $val($plan_holder['unique_identifier'] ?? 'Not assigned') ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Monthly contribution</span><strong><?= cs_money($plan['monthly_fee'] ?? ($program['monthly_fee'] ?? 240)) ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Months paid</span><strong><?= esc((string) ((int) ($plan['months_paid'] ?? 0))) ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Start date</span><strong><?= cs_date($plan['start_date'] ?? date('Y-m-d')) ?></strong></div>
                </div>
            </section>

            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Coverage &amp; payment schedule</h2></div>
                <div class="cs-panel__body">
                    <div class="cs-kv"><span class="cs-muted">Coverage until</span><strong><?= esc((string) ($plan['payment_coverage_until'] ?? date('F Y'))) ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Next due</span><strong><?= cs_date($plan['next_due_date'] ?? date('Y-m-d', strtotime('+1 month'))) ?></strong></div>
                    <div class="cs-kv"><span class="cs-muted">Overdue months</span><?= cs_status(((int) ($plan['overdue_months'] ?? 0)) > 0 ? 'overdue' : 'paid', (string) ((int) ($plan['overdue_months'] ?? 0))) ?></div>

                    <?php if ($state === 'awaiting_activation'): ?>
                        <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'class' => 'mt-3 mb-0', 'message' => 'Your registration is complete. Please submit your initial payment to activate your membership.']) ?>
                    <?php endif; ?>
                    <?php if (($access['initial_payment_status'] ?? 'none') === 'cancelled'): ?>
                        <?= view('components/alert', ['type' => 'danger', 'dismissible' => false, 'class' => 'mt-3 mb-0', 'message' => 'Payment rejected. Please resubmit.']) ?>
                    <?php endif; ?>
                    <?php if ($membershipState === 'delinquent'): ?>
                        <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'class' => 'mt-3 mb-0', 'title' => 'Your membership is delinquent.', 'message' => 'Please update your monthly contributions. Services are available for the first 2 months of delinquency.']) ?>
                    <?php endif; ?>
                    <?php if ($membershipState === 'suspended'): ?>
                        <?= view('components/alert', ['type' => 'danger', 'dismissible' => false, 'class' => 'mt-3 mb-0', 'title' => 'Your membership is suspended.', 'message' => 'Please contact our office to settle your account and restore your membership.']) ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-8 order-lg-1">
            <section class="cs-panel mb-3">
                <div class="cs-panel__head">
                    <h2 class="cs-panel__title">Registration details</h2>
                    <a href="<?= base_url('plan-registration') ?>" class="btn btn-outline-primary btn-sm">Edit details</a>
                </div>
                <div class="cs-panel__body">
                    <div class="row g-3">
                        <?= $fact('ID Control No.', $val($plan_holder['id_control_no'] ?? null)) ?>
                        <?= $fact('Coordinator', $val($plan_holder['coordinator'] ?? null)) ?>
                        <?= $fact('Date of application', cs_date($plan_holder['application_date'] ?? null)) ?>

                        <?= $fact('Last name', $val($user['last_name'] ?? null)) ?>
                        <?= $fact('Given name', $val($user['first_name'] ?? null)) ?>
                        <?= $fact('Middle name', $val($user['middle_name'] ?? null)) ?>

                        <?= $fact('Address No.', $val($plan_holder['address_no'] ?? null), 'col-md-3') ?>
                        <?= $fact('Street', $val($plan_holder['address_street'] ?? null), 'col-md-3') ?>
                        <?= $fact('Barangay', $val($plan_holder['address_barangay'] ?? null), 'col-md-3') ?>
                        <?= $fact('Town/City', $val($plan_holder['address_city'] ?? null), 'col-md-3') ?>

                        <?= $fact('Date of birth', cs_date($plan_holder['date_of_birth'] ?? null), 'col-md-3') ?>
                        <?= $fact('Place of birth', $val($plan_holder['place_of_birth'] ?? null), 'col-md-3') ?>
                        <?= $fact('Age', $val(cs_age_from_dob($plan_holder['date_of_birth'] ?? null) ?? ($plan_holder['age'] ?? null)), 'col-md-2') ?>
                        <?= $fact('Gender', $val($plan_holder['gender'] ?? null), 'col-md-2') ?>
                        <?= $fact('Civil status', $val($plan_holder['civil_status'] ?? null), 'col-md-2') ?>

                        <?= $fact('Citizenship', $val($plan_holder['citizenship'] ?? null), 'col-md-3') ?>
                        <?= $fact('Height', $val($plan_holder['height'] ?? null), 'col-md-3') ?>
                        <?= $fact('Weight', $val($plan_holder['weight'] ?? null), 'col-md-3') ?>
                        <?= $fact('Branch', $val($branch_info['branch_name'] ?? null), 'col-md-3') ?>

                        <?= $fact('Name of spouse', $val($plan_holder['spouse_name'] ?? null)) ?>
                        <?= $fact('Spouse birthdate', cs_date($plan_holder['spouse_birthdate'] ?? null)) ?>
                        <?= $fact('Spouse occupation', $val($plan_holder['spouse_occupation'] ?? null)) ?>

                        <?= $fact('Cellphone No.', $val($user['contact_number'] ?? null)) ?>
                        <?= $fact('Email', $val($user['email'] ?? null)) ?>
                        <?= $fact('Senior Citizen ID No.', $val($plan_holder['senior_citizen_id'] ?? null)) ?>
                        <?= $fact('Organization affiliation', $val($plan_holder['organization_affiliation'] ?? null), 'col-md-6') ?>
                    </div>
                </div>
            </section>

            <section class="cs-panel mb-3">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Emergency contact</h2></div>
                <div class="cs-panel__body">
                    <div class="row g-3">
                        <?= $fact('Name', $val($plan_holder['emergency_contact_name'] ?? null)) ?>
                        <?= $fact('Contact #', $val($plan_holder['emergency_contact_number'] ?? null)) ?>
                        <?= $fact('Address', $val($plan_holder['emergency_contact_address'] ?? null)) ?>
                    </div>
                </div>
            </section>

            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Beneficiaries</h2></div>
                <div class="cs-panel__body cs-panel__body--flush">
                    <?php if (empty($beneficiaries)): ?>
                        <?= view('components/empty_state', [
                            'icon'  => 'ti-users',
                            'title' => 'No beneficiaries listed',
                            'text'  => 'Beneficiaries added during registration appear here.',
                        ]) ?>
                    <?php else: ?>
                        <div class="cs-tablewrap">
                            <table class="cs-table">
                                <thead><tr><th>Complete name</th><th>Birthday</th><th>Relationship</th></tr></thead>
                                <tbody>
                                    <?php foreach ($beneficiaries as $beneficiary): ?>
                                        <?php
                                        $fullName = trim(implode(' ', array_filter([
                                            (string) ($beneficiary['first_name'] ?? ''),
                                            (string) ($beneficiary['middle_name'] ?? ''),
                                            (string) ($beneficiary['last_name'] ?? ''),
                                            (string) ($beneficiary['name_extension'] ?? ''),
                                        ])));
                                        ?>
                                        <tr>
                                            <td><?= esc($fullName) ?></td>
                                            <td><?= cs_date($beneficiary['date_of_birth'] ?? null) ?></td>
                                            <td><?= esc((string) ($beneficiary['relationship'] ?? '—')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
