<?php
/**
 * Payment Page - Responsive Design Example
 * 
 * This file demonstrates responsive design implementation across breakpoints:
 * - Mobile (< 576px): Single column, stacked layout
 * - Tablet (576px - 992px): Two columns, flexible grid
 * - Desktop (> 992px): Three columns, full layout
 */
?>

<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'new');
$user = $access['user'] ?? [];
$plan = $plan ?? [];
$payments = $payments ?? [];
?>

<div class="container-fluid">
    <!-- Page Header: Responsive spacing -->
    <div class="mb-3 mb-md-4">
        <h1 class="h2 mb-1">Payment Management</h1>
        <p class="text-muted mb-0 d-none d-md-block">
            Track and submit membership payments for your plan
        </p>
    </div>

    <!-- Mobile-only breadcrumb (simplified) -->
    <nav aria-label="breadcrumb" class="mb-3 mb-md-4 d-md-none">
        <ol class="breadcrumb breadcrumb-sm mb-0">
            <li class="breadcrumb-item active">Payments</li>
        </ol>
    </nav>

    <!-- Desktop breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3 mb-md-4 d-none d-md-block">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('client/dashboard') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active">Payments</li>
        </ol>
    </nav>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <?= view('components/alert', [
            'type' => 'danger',
            'message' => session()->getFlashdata('error'),
            'dismissible' => true
        ]) ?>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <?= view('components/alert', [
            'type' => 'success',
            'message' => session()->getFlashdata('success'),
            'dismissible' => true
        ]) ?>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="row g-3 g-md-4">
        <!-- Main Column: Full width on mobile, 2/3 on tablet, 8/12 on desktop -->
        <div class="col-12 col-md-8">
            
            <!-- Payment Submission Card -->
            <?php if ($state === 'approved' && $plan): ?>
                <div class="card mb-3 mb-md-4">
                    <div class="card-body">
                        <h5 class="mb-3">Submit Payment</h5>
                        
                        <!-- Payment Info: Responsive grid -->
                        <div class="row g-2 g-md-3 mb-4">
                            <div class="col-12 col-sm-6">
                                <?= view('components/info_card', [
                                    'label' => 'Monthly Fee',
                                    'value' => 'PHP ' . esc(number_format((float) ($plan['monthly_fee'] ?? 240), 2)),
                                    'size' => 'col',
                                    'bg' => 'light'
                                ]) ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?= view('components/info_card', [
                                    'label' => 'Months Paid',
                                    'value' => esc((string) ((int) ($plan['months_paid'] ?? 0))),
                                    'size' => 'col',
                                    'bg' => 'light'
                                ]) ?>
                            </div>
                            <div class="col-12">
                                <?= view('components/info_card', [
                                    'label' => 'Coverage Until',
                                    'value' => esc((string) ($plan['payment_coverage_until'] ?? date('F Y'))),
                                    'size' => 'col'
                                ]) ?>
                            </div>
                        </div>
                        
                        <!-- Payment Form: Responsive columns -->
                        <form method="post" action="<?= base_url('client/payment/submit-gcash') ?>" enctype="multipart/form-data">
                            <div class="row g-3">
                                <!-- Months covered: full mobile, half tablet, third desktop -->
                                <div class="col-12 col-md-6 col-lg-4">
                                    <?= view('components/form_field', [
                                        'name' => 'months_covered',
                                        'label' => 'Months to Cover',
                                        'type' => 'select',
                                        'options' => ['1' => '1 Month', '3' => '3 Months', '6' => '6 Months', '12' => '12 Months'],
                                        'value' => old('months_covered', '1'),
                                        'required' => true,
                                        'errors' => $errors ?? []
                                    ]) ?>
                                </div>
                                
                                <!-- Amount: full mobile, half tablet, third desktop -->
                                <div class="col-12 col-md-6 col-lg-4">
                                    <?= view('components/form_field', [
                                        'name' => 'amount',
                                        'label' => 'Amount',
                                        'type' => 'decimal',
                                        'value' => old('amount'),
                                        'placeholder' => '240.00',
                                        'required' => true,
                                        'help' => 'Minimum: PHP 240.00',
                                        'errors' => $errors ?? []
                                    ]) ?>
                                </div>
                                
                                <!-- Method: full mobile, half tablet, third desktop -->
                                <div class="col-12 col-md-6 col-lg-4">
                                    <?= view('components/form_field', [
                                        'name' => 'payment_method',
                                        'label' => 'Payment Method',
                                        'type' => 'select',
                                        'options' => ['gcash' => 'GCash'],
                                        'value' => old('payment_method', 'gcash'),
                                        'required' => true,
                                        'errors' => $errors ?? []
                                    ]) ?>
                                </div>
                                
                                <!-- Reference Number: full width -->
                                <div class="col-12">
                                    <?= view('components/form_field', [
                                        'name' => 'reference_number',
                                        'label' => 'GCash Reference Number',
                                        'type' => 'text',
                                        'value' => old('reference_number'),
                                        'placeholder' => 'e.g., GCASH123456789',
                                        'required' => true,
                                        'help' => 'Reference number from your GCash transaction',
                                        'errors' => $errors ?? []
                                    ]) ?>
                                </div>
                                
                                <!-- Proof Upload: full width on mobile, half on tablet -->
                                <div class="col-12 col-md-6">
                                    <?= view('components/form_field', [
                                        'name' => 'proof_image',
                                        'label' => 'Proof of Payment (Optional)',
                                        'type' => 'file',
                                        'value' => old('proof_image'),
                                        'help' => 'Upload screenshot of transaction (JPG, PNG)',
                                        'errors' => $errors ?? []
                                    ]) ?>
                                </div>
                                
                                <!-- Buttons: Stack on mobile, inline on tablet up -->
                                <div class="col-12">
                                    <div class="d-flex flex-column flex-md-row gap-2">
                                        <?= view('components/button', [
                                            'label' => 'Submit Payment',
                                            'url' => '#',
                                            'type' => 'primary',
                                            'onclick' => 'this.form.submit();return false;'
                                        ]) ?>
                                        
                                        <a href="<?= base_url('client/dashboard') ?>" class="btn btn-outline-secondary">
                                            Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <?= view('components/alert', [
                    'type' => 'info',
                    'title' => 'Payments Unavailable',
                    'message' => 'Complete your plan registration and approval to submit payments.'
                ]) ?>
            <?php endif; ?>

            <!-- Payment History Card: Responsive table -->
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Payment History</h5>
                    
                    <?php if ($payments): ?>
                        <!-- Desktop: Table view -->
                        <div class="d-none d-md-block">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?= esc((string) ($payment['payment_date'] ?? '-')) ?></td>
                                                <td>PHP <?= esc(number_format((float) ($payment['amount'] ?? 0), 2)) ?></td>
                                                <td><?= esc(ucfirst((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                                <td>
                                                    <?= view('components/status_badge', [
                                                        'status' => $payment['status'] ?? 'pending'
                                                    ]) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Mobile: Card view -->
                        <div class="d-md-none">
                            <div class="row g-2">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="col-12">
                                        <?= view('components/data_card', [
                                            'data' => [
                                                ['label' => 'Date', 'value' => esc((string) ($payment['payment_date'] ?? '-')), 'size' => '6'],
                                                ['label' => 'Amount', 'value' => 'PHP ' . esc(number_format((float) ($payment['amount'] ?? 0), 2)), 'size' => '6'],
                                                ['label' => 'Method', 'value' => esc(ucfirst((string) ($payment['payment_method'] ?? '-'))), 'size' => '6'],
                                                ['label' => 'Status', 'value' => '', 'badges' => [['status' => $payment['status'] ?? 'pending']], 'size' => '6'],
                                            ]
                                        ]) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">No payment history yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar: Hidden on mobile, 1/3 on tablet, 4/12 on desktop -->
        <div class="col-12 col-md-4">
            
            <!-- Summary Card -->
            <?= view('components/data_card', [
                'title' => 'Account Summary',
                'data' => [
                    ['label' => 'Status', 'value' => '', 'badges' => [['status' => $state]], 'size' => '12'],
                    ['label' => 'Plan', 'value' => esc((string) ($plan['plan_name'] ?? 'Damayan Plan')), 'size' => '12'],
                    ['label' => 'Next Due', 'value' => esc((string) ($plan['next_due_date'] ?? date('F d, Y', strtotime('+1 month')))), 'size' => '12'],
                ],
                'actions' => [
                    ['label' => 'Edit', 'url' => base_url('client/profile'), 'class' => 'btn-sm btn-outline-primary'],
                ]
            ]) ?>
            
            <!-- Help Card: Mobile-friendly -->
            <div class="card mt-3 mt-md-4 bg-light">
                <div class="card-body">
                    <h6 class="card-title">Payment Tips</h6>
                    <ul class="small list-unstyled text-muted">
                        <li>✓ Keep GCash reference handy</li>
                        <li>✓ Save transaction screenshot</li>
                        <li>✓ Minimum payment: PHP 240</li>
                        <li>✓ Processing: 1-2 hours</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
