<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$activeTab = (string) ($active_tab ?? '');
if ($activeTab === '') {
    $activeTab = ($selected_status ?? '') !== '' ? 'monitoring' : 'record';
}
?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Payment Tracking</h1>
        <p class="text-muted mb-0">Record advance payments and verify pending GCash/cash submissions.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'record' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#record-tab" type="button">Record Payment</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'initial' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#initial-tab" type="button">Initial Payments</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link <?= $activeTab === 'monitoring' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#monitoring-tab" type="button">Monitoring</button></li>
        <li class="nav-item" role="presentation"><a class="nav-link" href="<?= base_url('branch-admin/payment-tracking?status=pending') ?>">Pending Verification</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade <?= $activeTab === 'record' ? 'show active' : '' ?>" id="record-tab">
            <?php $this->setData([
                'ap_action' => base_url('branch-admin/payment-tracking/record-cash'),
                'ap_prefix' => 'ba_record',
                'ap_plan_options' => $plan_options ?? [],
                'ap_heading' => 'Advance Payment Entry',
                'ap_button_label' => 'Record Advance Payment',
            ]) ?>
            <?= $this->include('partials/advance_payment_form') ?>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'initial' ? 'show active' : '' ?>" id="initial-tab">
            <?php $this->setData([
                'ap_action' => base_url('branch-admin/payment-tracking/record-cash'),
                'ap_prefix' => 'ba_initial',
                'ap_plan_options' => $initial_plan_options ?? [],
                'ap_heading' => 'Record Initial Payment',
                'ap_button_label' => 'Record Initial Payment',
            ]) ?>
            <?= $this->include('partials/advance_payment_form') ?>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Initial Payments</h5>
                    <?php $this->setData([
                        'ap_rows' => $initial_rows ?? [],
                        'ap_can_approve' => (bool) ($can_approve ?? false),
                        'ap_action_base' => base_url('branch-admin/payment-tracking'),
                        'ap_show_proof' => (bool) ($supports_proof_upload ?? false),
                        'ap_empty_message' => 'No initial payment records found.',
                    ]) ?>
                    <?= $this->include('partials/advance_payment_table') ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'monitoring' ? 'show active' : '' ?>" id="monitoring-tab">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Payment Monitoring</h5>
                        <form method="get" action="<?= base_url('branch-admin/payment-tracking') ?>">
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
                        'ap_can_approve' => (bool) ($can_approve ?? false),
                        'ap_action_base' => base_url('branch-admin/payment-tracking'),
                        'ap_show_proof' => (bool) ($supports_proof_upload ?? false),
                        'ap_empty_message' => 'No payment records found.',
                    ]) ?>
                    <?= $this->include('partials/advance_payment_table') ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
