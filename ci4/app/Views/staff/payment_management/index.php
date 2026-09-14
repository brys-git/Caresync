<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<?php $this->setData([
    'ap_action' => base_url('staff/payment-management/record-cash'),
    'ap_prefix' => 'staff_ap',
    'ap_plan_options' => $plan_options ?? [],
    'ap_heading' => 'Advance Payment Entry',
    'ap_button_label' => 'Record Advance Payment',
]) ?>
<?= $this->include('partials/advance_payment_form') ?>

<section class="cs-panel">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Payment Records</h2>
        <form method="get" action="<?= base_url('staff/payment-management') ?>">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="" <?= ($selected_status ?? '') === '' ? 'selected' : '' ?>>All</option>
                <option value="pending" <?= ($selected_status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="paid" <?= ($selected_status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="cancelled" <?= ($selected_status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </form>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php $this->setData([
            'ap_rows' => $rows ?? [],
            'ap_can_approve' => false,
            'ap_action_base' => '',
            'ap_show_proof' => false,
            'ap_empty_message' => 'No payment records found.',
        ]) ?>
        <?= $this->include('partials/advance_payment_table') ?>
    </div>
</section>
<?= $this->endSection() ?>
