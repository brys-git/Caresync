<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$beneficiaries = $beneficiaries ?? [];
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$planId = (int) ($plan_id ?? 0);
$idTypes = $id_types ?? [];
$latestVerification = $latest_verification ?? null;
$errors = session()->getFlashdata('errors') ?? [];

$this->setData(['wizard' => [
    'action' => base_url('plan-registration'),
    'mode' => 'self',
    'errors' => $errors,
    'show_company_header' => true,
    'show_branch_select' => true,
    'branches' => $branches ?? [],
    'hidden_plan_id' => $planId,
    'program' => $program,
    'plan_holder' => $plan_holder ?? [],
    'user' => $user ?? [],
    'beneficiaries' => $beneficiaries,
    'id_types' => $idTypes,
    'id_result_field_id' => 'government_id_verification_id',
    'id_result_field_key' => 'verification_id',
    'id_result_field_value' => (string) ($latestVerification['verification_id'] ?? ''),
    'selected_id_type' => (string) ($latestVerification['id_type'] ?? ''),
    'certify_label' => 'I certify that the information I provided is true and correct to the best of my knowledge.',
    'submit_label' => 'Submit Registration',
]]) ?>
<div style="max-width: 1000px;">
    <?php // Plain error/success flash already handled by layouts/_shell.php's toast. The "errors" list is a validation-array flash, a different shape the shell's toast loop doesn't cover, so _wizard.php renders it separately. ?>
    <?= $this->include('partials/registration/_wizard') ?>
</div>

<script src="<?= base_url('assets/js/id-verification-widget.js') ?>"></script>
<script src="<?= base_url('assets/js/caresync-wizard.js') ?>"></script>
<script src="<?= base_url('assets/js/psgc-address.js') ?>"></script>
<script>
(function () {
    window.CareSyncWizard.init({
        idVerify: {
            endpoint: '<?= base_url('api/id-verification/verify') ?>',
            resultFieldId: 'government_id_verification_id',
            resultFieldKey: 'verification_id',
            initialStatus: <?= $latestVerification ? json_encode((string) $latestVerification['verification_status']) : 'null' ?>,
            csrfName: '<?= csrf_token() ?>',
            csrfValue: '<?= csrf_hash() ?>',
        },
    });

    window.CareSyncPsgc.init({
        addressApiBase: '<?= base_url('api/address') ?>',
    });
})();
</script>
<?= $this->endSection() ?>
