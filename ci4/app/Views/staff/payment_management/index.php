<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Payment Management</h1>
        <p class="text-muted mb-0">Record advance payments and monitor transaction statuses.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php $this->setData([
        'ap_action' => base_url('staff/payment-management/record-cash'),
        'ap_prefix' => 'staff_ap',
        'ap_plan_options' => $plan_options ?? [],
        'ap_heading' => 'Advance Payment Entry',
        'ap_button_label' => 'Record Advance Payment',
    ]) ?>
    <?= $this->include('partials/advance_payment_form') ?>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Payment Records</h5>
                <form method="get" action="<?= base_url('staff/payment-management') ?>">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="" <?= ($selected_status ?? '') === '' ? 'selected' : '' ?>>All</option>
                        <option value="pending" <?= ($selected_status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= ($selected_status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="cancelled" <?= ($selected_status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </form>
            </div>

            <?php $this->setData([
                'ap_rows' => $rows ?? [],
                'ap_can_approve' => false,
                'ap_action_base' => '',
                'ap_show_proof' => false,
                'ap_empty_message' => 'No payment records found.',
            ]) ?>
            <?= $this->include('partials/advance_payment_table') ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
