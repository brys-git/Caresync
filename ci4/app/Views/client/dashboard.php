<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/branch-dashboard.css') ?>">

<?php
    $state = (string) ($access['state'] ?? 'unregistered');
    $userFullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
    $statusLabel = (string) ($access['badge_label'] ?? 'Unregistered');
    $planAmount = (float) ($plan['total_plan_amount'] ?? \App\Services\MembershipService::TOTAL_CONTRIBUTION);
    $paidAmount = 0.0;
    if (! empty($plan['plan_id'])) {
        $paidAmount = (float) ($plan['paid_amount'] ?? $plan['total_paid'] ?? 0);
    }
    $remainingBalance = max(0, (float) ($plan['remaining_balance'] ?? ($planAmount - $paidAmount)));
    $nextDueDate = (string) ($plan['next_due_date'] ?? '-');
    $progress = $planAmount > 0 ? min(100, max(0, (($paidAmount / max($planAmount, 1)) * 100))) : 0;
?>

<div class="bd">

    <?php if (session()->getFlashdata('error')): ?>
        <div style="background:#fff5f5;border:1px solid #fed7d7;color:#e53e3e;padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:600;">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div style="background:#f0fff4;border:1px solid #c6f6d5;color:#38a169;padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:600;">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== KPI Cards ====== -->
    <div class="bd-kpis">
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--teal"><i class="mdi mdi-shield-check"></i></div>
            <div>
                <div class="bd-kpi__label">Membership</div>
                <div class="bd-kpi__value" style="font-size:1.1rem;"><?= esc(strtoupper($statusLabel)) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--blue"><i class="mdi mdi-cash-multiple"></i></div>
            <div>
                <div class="bd-kpi__label">Remaining Balance</div>
                <div class="bd-kpi__value">₱<?= number_format($remainingBalance, 2) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--green"><i class="mdi mdi-check-circle-outline"></i></div>
            <div>
                <div class="bd-kpi__label">Paid</div>
                <div class="bd-kpi__value">₱<?= number_format($paidAmount, 2) ?></div>
            </div>
        </div>
        <div class="bd-kpi">
            <div class="bd-kpi__icon bd-kpi__icon--orange"><i class="mdi mdi-calendar-clock"></i></div>
            <div>
                <div class="bd-kpi__label">Next Due</div>
                <div class="bd-kpi__value" style="font-size:1.1rem;"><?= esc($nextDueDate !== '' ? $nextDueDate : 'N/A') ?></div>
            </div>
        </div>
    </div>

    <!-- ====== Quick Actions + Account Info ====== -->
    <div class="bd-quick-bar">
        <div class="bd-quick-actions">
            <h3 class="bd-quick-actions__title">Quick Actions</h3>
            <div class="bd-quick-actions__list">
                <a href="<?= base_url('client/payment/advance') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-credit-card-check"></i> Make Payment
                </a>
                <a href="<?= base_url('client/payment') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-receipt-text-outline"></i> View Payments
                </a>
                <a href="<?= base_url('client/service') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-tools"></i> Apply for Service
                </a>
                <a href="<?= base_url('client/profile') ?>" class="bd-qa-btn">
                    <i class="mdi mdi-account-cog"></i> Update Profile
                </a>
            </div>
        </div>
        <div class="bd-alert-feed">
            <h3 class="bd-alert-feed__title">Account Info</h3>
            <div class="bd-alert-item">
                <i class="mdi mdi-identifier"></i>
                <span><strong><?= esc((string) ($plan_holder['unique_identifier'] ?? 'N/A')) ?></strong></span>
            </div>
            <div class="bd-alert-item" style="margin-top:6px;">
                <i class="mdi mdi-office-building"></i>
                <span><?= esc($branch_name !== '' ? $branch_name : 'N/A') ?></span>
            </div>
        </div>
    </div>

    <!-- ====== Tabs ====== -->
    <div class="bd-tabs" id="bd-tabs">
        <button class="bd-tab bd-tab--active" data-tab="overview" onclick="bdSwitchTab(this)">Overview</button>
        <button class="bd-tab" data-tab="payments" onclick="bdSwitchTab(this)">Payment History</button>
        <button class="bd-tab" data-tab="services" onclick="bdSwitchTab(this)">Services</button>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Overview                                                    -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-overview">
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Membership Summary</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row"><span class="bd-stat-row__label">Plan Holder ID</span><span class="bd-stat-row__value"><?= esc((string) ($plan_holder['unique_identifier'] ?? 'N/A')) ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Branch</span><span class="bd-stat-row__value"><?= esc($branch_name !== '' ? $branch_name : 'N/A') ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Plan</span><span class="bd-stat-row__value"><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Plan Status</span><span class="bd-stat-row__value"><?= esc(ucfirst((string) ($plan['status'] ?? 'inactive'))) ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Member Since</span><span class="bd-stat-row__value"><?= esc($membership_since !== '' ? $membership_since : 'N/A') ?></span></div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Payment Progress</h3></div>
            <div class="bd-card__body">
                <div class="bd-stat-row"><span class="bd-stat-row__label">Plan Amount</span><span class="bd-stat-row__value">₱<?= number_format($planAmount, 2) ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Paid</span><span class="bd-stat-row__value" style="color:var(--bd-green);">₱<?= number_format($paidAmount, 2) ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Remaining</span><span class="bd-stat-row__value" style="color:var(--bd-orange);">₱<?= number_format($remainingBalance, 2) ?></span></div>
                <div style="margin-top:12px;">
                    <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--bd-ink-faint);margin-bottom:6px;">
                        <span>Progress</span><span><?= number_format($progress, 0) ?>%</span>
                    </div>
                    <div style="height:10px;border-radius:999px;background:#e2e8f0;overflow:hidden;">
                        <div style="height:100%;width:<?= number_format($progress, 0) ?>%;border-radius:999px;background:linear-gradient(90deg,#1e3a5f,#d4a843);transition:width 0.6s;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bd-card">
            <div class="bd-card__header"><h3 class="bd-card__title">Service Eligibility</h3></div>
            <div class="bd-card__body">
                <?php if ($state === 'active'): ?>
                    <div class="bd-alert-card bd-alert-card--success" style="margin-bottom:12px;">
                        <i class="mdi mdi-check-circle-outline"></i>
                        <span>You are eligible to apply for services.</span>
                    </div>
                <?php else: ?>
                    <div class="bd-alert-card bd-alert-card--warning" style="margin-bottom:12px;">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        <span>Service applications are unavailable until membership activation.</span>
                    </div>
                <?php endif; ?>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Membership</span><span class="bd-stat-row__value"><?= esc(strtoupper($statusLabel)) ?></span></div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Payment History                                             -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-payments" style="display:none;">
        <div class="bd-card" style="grid-column:1/-1;">
            <div class="bd-card__header"><h3 class="bd-card__title">Payment History</h3></div>
            <div class="bd-card__body" style="padding-top:4px;">
                <?php if (empty($recent_payments ?? [])): ?>
                    <div class="bd-empty">No payment history yet.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="bd-table">
                            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td><?= esc((string) ($payment['payment_date'] ?? '-')) ?></td>
                                        <td><strong>₱<?= number_format((float) ($payment['amount'] ?? 0), 2) ?></strong></td>
                                        <td><?= esc(strtoupper((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                        <td><span class="bd-badge <?= ($payment['status'] ?? '') === 'paid' ? 'bd-badge--green' : 'bd-badge--amber' ?>"><?= esc(ucfirst((string) ($payment['status'] ?? '-'))) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Services                                                    -->
    <!-- ================================================================ -->
    <div class="bd-panel" id="bd-tab-services" style="display:none;">
        <div class="bd-card" style="grid-column:1/-1;">
            <div class="bd-card__header">
                <h3 class="bd-card__title">Service Applications</h3>
                <a href="<?= base_url('client/service') ?>" class="bd-card__link">Apply now</a>
            </div>
            <div class="bd-card__body" style="padding-top:4px;">
                <?php if ($state === 'active'): ?>
                    <div class="bd-alert-card bd-alert-card--info" style="margin-bottom:16px;">
                        <i class="mdi mdi-information-outline"></i>
                        <span>You are eligible to apply for services. Visit the Services page to submit a request.</span>
                    </div>
                <?php else: ?>
                    <div class="bd-alert-card bd-alert-card--warning" style="margin-bottom:16px;">
                        <i class="mdi mdi-lock-outline"></i>
                        <span>Service applications are locked until your membership becomes active.</span>
                    </div>
                <?php endif; ?>

                <div class="bd-stat-row"><span class="bd-stat-row__label">Available Packages</span><span class="bd-stat-row__value"><?= count($packages ?? []) ?></span></div>
                <div class="bd-stat-row"><span class="bd-stat-row__label">Monthly Fee</span><span class="bd-stat-row__value">₱<?= number_format((float) ($plan['monthly_fee'] ?? 240), 2) ?></span></div>
            </div>
        </div>
    </div>

</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    window.bdSwitchTab = function (btn) {
        var tabId = btn.getAttribute('data-tab');
        document.querySelectorAll('#bd-tabs .bd-tab').forEach(function (t) { t.classList.remove('bd-tab--active'); });
        btn.classList.add('bd-tab--active');

        document.querySelectorAll('.bd-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('bd-tab-' + tabId);
        if (panel) panel.style.display = '';
    };
})();
</script>
<?= $this->endSection() ?>
