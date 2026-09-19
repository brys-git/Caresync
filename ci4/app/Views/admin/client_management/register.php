<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>
<?php
$this->setData(['wizard' => [
    'action' => base_url('admin/client/store'),
    'mode' => 'staff',
    'show_company_header' => false,
    'page_heading' => 'Register Plan Holder',
    'page_subheading' => 'Add a new plan holder to any branch.',
    'show_branch_select' => true,
    'branches' => $branches ?? [],
    'show_account_section' => true,
    'existing_users' => $existing_users ?? [],
    'program' => $program ?? [],
    'id_types' => $id_types ?? [],
    'id_result_field_id' => 'government_id_pending_token',
    'id_result_field_key' => 'pending_token',
    'certify_label' => "I certify that the applicant's information above is true and correct as provided to me.",
    'submit_label' => 'Register Plan Holder',
    'cancel_url' => base_url('admin/client-management'),
]]) ?>
<div style="max-width: 1000px;">
    <?= $this->include('partials/registration/_wizard') ?>
</div>

<script src="<?= base_url('assets/js/id-verification-widget.js') ?>"></script>
<script src="<?= base_url('assets/js/caresync-wizard.js') ?>"></script>
<script src="<?= base_url('assets/js/psgc-address.js') ?>"></script>
<script>
(function () {
    window.CareSyncWizard.init({
        idVerify: {
            endpoint: '<?= base_url('api/id-verification/verify-pending') ?>',
            resultFieldId: 'government_id_pending_token',
            resultFieldKey: 'pending_token',
            initialStatus: null,
            csrfName: '<?= csrf_token() ?>',
            csrfValue: '<?= csrf_hash() ?>',
        },
        existingUsers: <?= json_encode(array_values(array_map(static function ($user) {
            return [
                'email' => strtolower(trim((string) ($user['email'] ?? ''))),
                'first_name' => (string) ($user['first_name'] ?? ''),
                'middle_name' => (string) ($user['middle_name'] ?? ''),
                'last_name' => (string) ($user['last_name'] ?? ''),
                'contact_number' => (string) ($user['contact_number'] ?? ''),
            ];
        }, $existing_users ?? []))) ?>,
    });

    window.CareSyncPsgc.init({
        addressApiBase: '<?= base_url('api/address') ?>',
    });
})();
</script>
<?= $this->endSection() ?>
