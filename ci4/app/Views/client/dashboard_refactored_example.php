<?php
/**
 * Dashboard Example - Using Standardized Components
 * 
 * This file demonstrates how to use the new standardized UI/UX components
 * from app/Views/components/ directory
 */
?>

<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'new');
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$user = $access['user'] ?? [];
$planHolder = $access['plan_holder'] ?? [];
$plan = $plan ?? [];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h2 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome, <?= esc((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
        </p>
    </div>

    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
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

    <!-- Account Summary Card -->
    <?= view('components/data_card', [
        'title' => 'Account Summary',
        'data' => [
            ['label' => 'Username', 'value' => esc((string) ($user['username'] ?? '-')), 'size' => 'md-3'],
            ['label' => 'Email', 'value' => esc((string) ($user['email'] ?? '-')), 'size' => 'md-3'],
            ['label' => 'Plan', 'value' => esc((string) ($program['name'] ?? 'Damayan Burial Program')), 'size' => 'md-3'],
            ['label' => 'Membership', 'value' => '', 'badges' => [['status' => $access['state'] ?? 'new']], 'size' => 'md-3'],
            ['label' => 'Plan Status', 'value' => esc(ucfirst((string) ($plan['status'] ?? 'inactive'))), 'size' => 'md-3'],
            ['label' => 'Monthly Fee', 'value' => 'PHP ' . esc(number_format((float) ($plan['monthly_fee'] ?? ($program['monthly_fee'] ?? 240)), 2)), 'size' => 'md-3'],
            ['label' => 'Months Paid', 'value' => esc((string) ((int) ($plan['months_paid'] ?? 0))), 'size' => 'md-3'],
            ['label' => 'Membership State', 'value' => '', 'badges' => [['status' => strtolower((string) ($plan['membership_state'] ?? 'active'))]], 'size' => 'md-3'],
        ]
    ]) ?>

    <!-- Membership Coverage (Approved Users Only) -->
    <?php if ($state === 'approved' && $plan): ?>
        <div class="card mt-4" style="border-left: 4px solid #28a745;">
            <div class="card-body">
                <h5 class="mb-4">Membership Coverage</h5>
                
                <div class="row g-3">
                    <?= view('components/info_card', [
                        'label' => 'Coverage Until',
                        'value' => esc((string) ($plan['payment_coverage_until'] ?? '-')),
                        'size' => 'md-4',
                        'bg' => 'light'
                    ]) ?>
                    
                    <?= view('components/info_card', [
                        'label' => 'Next Due Date',
                        'value' => esc((string) ($plan['next_due_date'] ?? '-')),
                        'size' => 'md-4',
                        'bg' => 'light'
                    ]) ?>
                    
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <small class="text-muted d-block">Overdue Months</small>
                            <strong><?= view('components/status_badge', ['status' => ((int) ($plan['overdue_months'] ?? 0)) === 0 ? 'active' : 'delinquent', 'label' => (string) ((int) ($plan['overdue_months'] ?? 0))]) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Overdue Alert (if applicable) -->
                <?php if (((int) ($plan['overdue_months'] ?? 0)) > 0): ?>
                    <?= view('components/alert', [
                        'type' => ((int) ($plan['overdue_months'] ?? 0)) > 5 ? 'danger' : 'warning',
                        'title' => 'Overdue Alert:',
                        'message' => 'Your membership has ' . esc((string) ((int) ($plan['overdue_months'] ?? 0))) . ' overdue months. Please update your contributions soon.'
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- State-Specific Alerts & Messages -->
    <?php if ($state === 'new'): ?>
        <?= view('components/alert', [
            'type' => 'info',
            'title' => 'Get Started',
            'message' => 'Your account has limited access. Complete plan registration to unlock payment and membership features. ' .
                        '<a href="' . base_url('plan-info') . '" class="alert-link">Register now</a>.'
        ]) ?>

    <?php elseif ($state === 'pending'): ?>
        <?= view('components/alert', [
            'type' => 'warning',
            'title' => 'Pending Review',
            'message' => 'Your registration is still under review. Full access will unlock after approval.'
        ]) ?>

    <?php else: ?>
        <div class="card mt-4 bg-light">
            <div class="card-body text-center">
                <h5>✓ Membership Active</h5>
                <p class="text-muted mb-0">You can now access payments, membership details, and service applications.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Action Buttons -->
    <div class="mt-4 d-flex flex-wrap gap-2">
        <?php if ($state === 'new'): ?>
            <?= view('components/button', [
                'label' => 'Start Registration',
                'url' => base_url('plan-info'),
                'type' => 'primary',
                'size' => 'md'
            ]) ?>
        <?php else: ?>
            <?= view('components/button', [
                'label' => 'View Membership',
                'url' => base_url('client/membership'),
                'type' => 'primary'
            ]) ?>
            
            <?= view('components/button', [
                'label' => 'Payment History',
                'url' => base_url('client/payment'),
                'type' => 'secondary'
            ]) ?>
            
            <?php if ($state === 'approved'): ?>
                <?= view('components/button', [
                    'label' => 'Browse Services',
                    'url' => base_url('client/service'),
                    'type' => 'success'
                ]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
