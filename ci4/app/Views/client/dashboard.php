<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$state = (string) ($access['state'] ?? 'unregistered');
$program = $program ?? ['name' => 'Damayan Burial Program', 'monthly_fee' => 240.0];
$userFullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
$membership_since = (string) ($membership_since ?? '');
$branch_name = (string) ($branch_name ?? '');
$statusLabel = (string) ($access['badge_label'] ?? 'Unregistered');
$statusClass = (string) ($access['badge_class'] ?? 'danger');
$monthlyFee = (float) ($plan['monthly_fee'] ?? ($program['monthly_fee'] ?? 240));
$planAmount = (float) ($plan['total_plan_amount'] ?? \App\Services\MembershipService::TOTAL_CONTRIBUTION);
$paidAmount = 0.0;
if (! empty($plan['plan_id'])) {
    $paidAmount = (float) ($plan['paid_amount'] ?? $plan['total_paid'] ?? 0);
}
$remainingBalance = max(0, (float) ($plan['remaining_balance'] ?? ($planAmount - $paidAmount)));
$nextDueDate = (string) ($plan['next_due_date'] ?? '-');
$latestPayment = $recent_payments[0] ?? null;
$latestPaymentLabel = $latestPayment ? ('#' . (string) ($latestPayment['payment_id'] ?? '')) : 'None yet';
$eligibilityText = $state === 'active'
    ? 'You are eligible to apply for services.'
    : 'Service applications are unavailable until membership activation.';
$eligibilityClass = $state === 'active' ? 'success' : 'warning';
?>
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #14b8a6 100%);
        color: #fff;
        border-radius: 24px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }
    .dashboard-hero::after {
        content: '';
        position: absolute;
        inset: auto -40px -70px auto;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: rgba(255,255,255,.08);
    }
    .status-badge {
        letter-spacing: .14em;
        font-size: .75rem;
        text-transform: uppercase;
    }
    .info-card, .action-card, .activity-card, .assist-card {
        border: 1px solid rgba(148,163,184,.2);
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15,23,42,.06);
    }
    .metric-value {
        font-size: 1.55rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .progress-track {
        height: 12px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .progress-bar-custom {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #1d4ed8, #14b8a6);
    }
    .quick-action {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: .95rem 1rem;
        text-decoration: none;
        color: inherit;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        background: #fff;
    }
    .quick-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(15,23,42,.08);
        border-color: #cbd5e1;
    }
</style>
<div class="container-fluid">
    <div class="dashboard-hero mb-4">
        <div class="row align-items-center position-relative" style="z-index:1;">
            <div class="col-lg-8">
                <p class="status-badge mb-2 opacity-75">Client Dashboard</p>
                <h1 class="display-6 fw-semibold mb-2">Welcome back, <?= esc($userFullName ?: 'Member') ?></h1>
                <p class="mb-3 opacity-75">Kaagapay Member Since: <?= esc($membership_since !== '' ? $membership_since : 'Not available') ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-light text-dark">Membership ID: <?= esc((string) ($plan_holder['unique_identifier'] ?? 'N/A')) ?></span>
                    <span class="badge rounded-pill text-bg-light text-dark">Branch: <?= esc($branch_name !== '' ? $branch_name : 'N/A') ?></span>
                    <span class="badge rounded-pill text-bg-light text-dark">Plan: <?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10 d-inline-block">
                    <div class="text-uppercase small opacity-75 mb-1">Membership Status</div>
                    <div class="fs-3 fw-bold"><?= esc(strtoupper($statusLabel)) ?></div>
                    <div class="opacity-75 small"><?= esc($state === 'active' ? 'Your account is fully verified and eligible for services.' : ($state === 'awaiting_activation' ? 'Please complete your initial payment.' : 'Please register to unlock access.')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card info-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Membership Status</h5>
                        <span class="badge text-bg-<?= esc($statusClass) ?>"><?= esc(strtoupper($statusLabel)) ?></span>
                    </div>
                    <p class="mb-3"><?= esc($state === 'active' ? 'Your account is fully verified and eligible for services.' : ($state === 'awaiting_activation' ? 'Please complete your initial payment.' : 'Please complete registration to unlock access.')) ?></p>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><strong>Membership ID:</strong> <?= esc((string) ($plan_holder['unique_identifier'] ?? 'N/A')) ?></li>
                        <li class="mb-2"><strong>Branch:</strong> <?= esc($branch_name !== '' ? $branch_name : 'N/A') ?></li>
                        <li class="mb-2"><strong>Plan:</strong> <?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></li>
                        <li><strong>Plan Status:</strong> <?= esc(ucfirst((string) ($plan['status'] ?? 'inactive'))) ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card info-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Payment Summary</h5>
                        <span class="badge text-bg-light">Latest Payment: <?= esc($latestPaymentLabel) ?></span>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><small class="text-muted d-block">Plan Amount</small><div class="metric-value">₱<?= esc(number_format($planAmount, 2)) ?></div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><small class="text-muted d-block">Paid</small><div class="metric-value">₱<?= esc(number_format($paidAmount, 2)) ?></div></div></div>
                        <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><small class="text-muted d-block">Remaining Balance</small><div class="metric-value">₱<?= esc(number_format($remainingBalance, 2)) ?></div></div></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted d-block">Next Due Date</small><strong><?= esc($nextDueDate !== '' ? $nextDueDate : '-') ?></strong></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted d-block">Payment Status</small><strong><?= esc((string) ($latestPayment['status'] ?? 'No payment yet')) ?></strong></div></div>
                    </div>
                    <div class="mt-3">
                        <?php $progress = $planAmount > 0 ? min(100, max(0, (($paidAmount / max($planAmount, 1)) * 100))) : 0; ?>
                        <div class="d-flex justify-content-between small text-muted mb-2"><span>Membership Completion</span><span><?= esc(number_format($progress, 0)) ?>%</span></div>
                        <div class="progress-track"><div class="progress-bar-custom" style="width: <?= esc(number_format($progress, 0)) ?>%;"></div></div>
                        <div class="small text-muted mt-2"><?= esc((int) floor($paidAmount / max($monthlyFee ?: 1, 1))) ?> of <?= esc((int) ceil($planAmount / max($monthlyFee ?: 1, 1))) ?> payments completed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card action-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><a class="quick-action" href="<?= base_url('client/membership') ?>"><span>View Membership</span><span>›</span></a></div>
                        <div class="col-md-6"><a class="quick-action" href="<?= base_url('initial-payment') ?>"><span>Make Payment</span><span>›</span></a></div>
                        <div class="col-md-6"><a class="quick-action" href="<?= base_url('client/payment') ?>"><span>View Payment History</span><span>›</span></a></div>
                        <div class="col-md-6"><a class="quick-action" href="<?= base_url('client/service') ?>"><span>Apply for Service</span><span>›</span></a></div>
                        <div class="col-md-6"><a class="quick-action" href="<?= base_url('client/profile') ?>"><span>Update Profile</span><span>›</span></a></div>
                        <div class="col-md-6"><a class="quick-action" href="<?= base_url('client/payment/download-receipt') ?>"><span>Download Receipt</span><span>›</span></a></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card info-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Service Eligibility</h5>
                    <div class="alert alert-<?= esc($eligibilityClass) ?> mb-0">
                        <?= esc($eligibilityText) ?>
                    </div>
                    <div class="mt-3 small text-muted">
                        <?php if ($state === 'active'): ?>
                            You can proceed to service requests from the Services page.
                        <?php else: ?>
                            Service requests stay locked until your membership becomes active.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card activity-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Recent Activity</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr><th>Date</th><th>Activity</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach (($recent_payments ?? []) as $payment): ?>
                                    <tr>
                                        <td><?= esc((string) ($payment['payment_date'] ?? ($payment['created_at'] ?? '-'))) ?></td>
                                        <td>Payment recorded via <?= esc(strtoupper((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                        <td><span class="badge text-bg-<?= strtolower((string) ($payment['status'] ?? 'pending')) === 'paid' ? 'success' : 'warning' ?>"><?= esc(ucfirst((string) ($payment['status'] ?? 'pending'))) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_payments ?? [])): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">No recent activity yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card assist-card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Need Assistance?</h5>
                    <p class="mb-2"><strong>Branch:</strong> <?= esc($branch_name !== '' ? $branch_name : 'Kaagapay Branch') ?></p>
                    <p class="mb-2"><strong>Hotline:</strong> 0912-345-6789</p>
                    <p class="mb-2"><strong>Email:</strong> support@kaagapay.local</p>
                    <p class="mb-0"><strong>Office Hours:</strong> Mon-Sat, 8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
