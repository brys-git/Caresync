<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/branch-dashboard.css') ?>">

<?php
    $isPH = (bool) ($is_plan_holder ?? false);
    $userName = esc(session()->get('first_name') ?? session()->get('user_name') ?? 'Client');
?>

<div class="bd">

    <?php if (! $isPH): ?>
        <!-- ====== NOT A PLAN HOLDER: Registration Prompt ====== -->
        <div class="bd-kpis">
            <div class="bd-kpi" style="grid-column:1/-1;background:linear-gradient(135deg,#1e3a5f 0%,#2a4f7a 100%);color:#fff;border:none;">
                <div class="bd-kpi__icon" style="background:rgba(255,255,255,0.15);color:#fff;"><i class="mdi mdi-lock-outline"></i></div>
                <div>
                    <div class="bd-kpi__label" style="color:rgba(255,255,255,0.75);">Limited Access</div>
                    <div class="bd-kpi__value" style="color:#fff;">Complete your plan holder registration first.</div>
                    <div style="font-size:0.86rem;color:rgba(255,255,255,0.75);margin-top:6px;">Your account is active, but services, membership, and payment features are locked until you register as a plan holder.</div>
                </div>
            </div>
        </div>

        <?php if (! empty($pendingRegistration) && ($pendingRegistration['status'] ?? '') === 'pending'): ?>
            <div style="background:var(--bd-blue-soft);border:1px solid #bee3f8;color:var(--bd-blue);padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:600;">
                <i class="mdi mdi-information-outline"></i>
                Your registration was submitted on <?= esc((string) ($pendingRegistration['created_at'] ?? '-')) ?> and is pending approval.
            </div>
        <?php elseif (! empty($pendingRegistration) && ($pendingRegistration['status'] ?? '') === 'rejected'): ?>
            <div style="background:var(--bd-orange-soft);border:1px solid var(--bd-orange-border);color:#c2760a;padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:600;">
                <i class="mdi mdi-alert-circle-outline"></i>
                Your latest registration was rejected. Update your details and submit again.
            </div>
        <?php endif; ?>

        <div class="bd-quick-bar">
            <div class="bd-quick-actions">
                <h3 class="bd-quick-actions__title">Next Step</h3>
                <div class="bd-quick-actions__list">
                    <a href="<?= esc($registration_url ?? '#') ?>" class="bd-qa-btn" style="background:var(--bd-teal);color:#fff;border-color:var(--bd-teal);">
                        <i class="mdi mdi-account-plus" style="color:#fff;"></i>
                        <?= (! empty($pendingRegistration) && ($pendingRegistration['status'] ?? '') === 'pending') ? 'View Registration Status' : 'Register as Plan Holder' ?>
                    </a>
                </div>
            </div>
            <div class="bd-alert-feed">
                <h3 class="bd-alert-feed__title">Account Status</h3>
                <div class="bd-alert-item" style="color:var(--bd-orange);">
                    <i class="mdi mdi-lock-outline"></i>
                    <span>Features locked until registration is approved.</span>
                </div>
            </div>
        </div>

        <div class="bd-panel">
            <div class="bd-card">
                <div class="bd-card__header"><h3 class="bd-card__title">Service Applications</h3></div>
                <div class="bd-card__body"><div class="bd-empty" style="opacity:0.5;">Locked until registration</div></div>
            </div>
            <div class="bd-card">
                <div class="bd-card__header"><h3 class="bd-card__title">Membership Details</h3></div>
                <div class="bd-card__body"><div class="bd-empty" style="opacity:0.5;">Locked until registration</div></div>
            </div>
            <div class="bd-card">
                <div class="bd-card__header"><h3 class="bd-card__title">Payments</h3></div>
                <div class="bd-card__body"><div class="bd-empty" style="opacity:0.5;">Locked until registration</div></div>
            </div>
        </div>

    <?php else: ?>
        <!-- ====== IS A PLAN HOLDER: Full Dashboard ====== -->
        <div class="bd-kpis">
            <div class="bd-kpi">
                <div class="bd-kpi__icon bd-kpi__icon--teal"><i class="mdi mdi-shield-check"></i></div>
                <div>
                    <div class="bd-kpi__label">Membership</div>
                    <div class="bd-kpi__value"><?= esc(ucfirst((string) ($membership['membership_status'] ?? 'n/a'))) ?></div>
                </div>
            </div>
            <div class="bd-kpi">
                <div class="bd-kpi__icon bd-kpi__icon--blue"><i class="mdi mdi-clipboard-text-outline"></i></div>
                <div>
                    <div class="bd-kpi__label">Plan Status</div>
                    <div class="bd-kpi__value"><?= esc(ucfirst((string) ($membership['plan_status'] ?? 'n/a'))) ?></div>
                </div>
            </div>
            <div class="bd-kpi">
                <div class="bd-kpi__icon bd-kpi__icon--orange"><i class="mdi mdi-cash-multiple"></i></div>
                <div>
                    <div class="bd-kpi__label">Remaining Balance</div>
                    <div class="bd-kpi__value">₱<?= number_format((float) ($membership['remaining_balance'] ?? 0), 2) ?></div>
                </div>
            </div>
            <div class="bd-kpi">
                <div class="bd-kpi__icon bd-kpi__icon--green"><i class="mdi mdi-package-variant"></i></div>
                <div>
                    <div class="bd-kpi__label">Package</div>
                    <div class="bd-kpi__value" style="font-size:1.1rem;"><?= esc((string) ($membership['package_name'] ?? 'n/a')) ?></div>
                </div>
            </div>
        </div>

        <div class="bd-quick-bar">
            <div class="bd-quick-actions">
                <h3 class="bd-quick-actions__title">Quick Actions</h3>
                <div class="bd-quick-actions__list">
                    <a href="<?= site_url('plan-holder/payment-history') ?>" class="bd-qa-btn">
                        <i class="mdi mdi-credit-card-check"></i> View Payments
                    </a>
                    <a href="<?= site_url('plan-holder/services') ?>" class="bd-qa-btn">
                        <i class="mdi mdi-tools"></i> Service Requests
                    </a>
                    <a href="<?= site_url('plan-holder/profile') ?>" class="bd-qa-btn">
                        <i class="mdi mdi-account-cog"></i> My Profile
                    </a>
                    <a href="<?= site_url('plan-holder/packages') ?>" class="bd-qa-btn">
                        <i class="mdi mdi-package-variant"></i> View Packages
                    </a>
                </div>
            </div>
            <div class="bd-alert-feed">
                <h3 class="bd-alert-feed__title">Account Info</h3>
                <div class="bd-alert-item">
                    <i class="mdi mdi-identifier"></i>
                    <span><strong><?= esc((string) ($membership['unique_identifier'] ?? '-')) ?></strong></span>
                </div>
                <div class="bd-alert-item" style="margin-top:6px;">
                    <i class="mdi mdi-office-building"></i>
                    <span><?= esc((string) ($membership['branch_name'] ?? '-')) ?></span>
                </div>
            </div>
        </div>

        <!-- ====== Tabs ====== -->
        <div class="bd-tabs" id="bd-tabs">
            <button class="bd-tab bd-tab--active" data-tab="overview" onclick="bdSwitchTab(this)">Overview</button>
            <button class="bd-tab" data-tab="payments" onclick="bdSwitchTab(this)">Payment History</button>
            <button class="bd-tab" data-tab="services" onclick="bdSwitchTab(this)">Service Requests</button>
        </div>

        <!-- ================================================================ -->
        <!-- TAB: Overview                                                    -->
        <!-- ================================================================ -->
        <div class="bd-panel" id="bd-tab-overview">
            <div class="bd-card">
                <div class="bd-card__header"><h3 class="bd-card__title">Membership Summary</h3></div>
                <div class="bd-card__body">
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Plan Holder ID</span><span class="bd-stat-row__value"><?= esc((string) ($membership['unique_identifier'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Branch</span><span class="bd-stat-row__value"><?= esc((string) ($membership['branch_name'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Package</span><span class="bd-stat-row__value"><?= esc((string) ($membership['package_name'] ?? '-')) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Monthly Rate</span><span class="bd-stat-row__value">₱<?= number_format((float) ($membership['locked_price'] ?? 0), 2) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Start Date</span><span class="bd-stat-row__value"><?= esc((string) ($membership['effective_date'] ?? '-')) ?></span></div>
                </div>
            </div>
            <div class="bd-card">
                <div class="bd-card__header"><h3 class="bd-card__title">Payment Summary</h3></div>
                <div class="bd-card__body">
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Total Payments</span><span class="bd-stat-row__value"><?= count($payment_history ?? []) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Remaining Balance</span><span class="bd-stat-row__value" style="color:var(--bd-orange);">₱<?= number_format((float) ($membership['remaining_balance'] ?? 0), 2) ?></span></div>
                </div>
            </div>
            <div class="bd-card">
                <div class="bd-card__header"><h3 class="bd-card__title">Service Requests</h3></div>
                <div class="bd-card__body">
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Total Requests</span><span class="bd-stat-row__value"><?= count($service_requests ?? []) ?></span></div>
                    <div class="bd-stat-row"><span class="bd-stat-row__label">Available Packages</span><span class="bd-stat-row__value"><?= count($packages ?? []) ?></span></div>
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
                    <?php if (empty($payment_history ?? [])): ?>
                        <div class="bd-empty">No payment history yet.</div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="bd-table">
                                <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($payment_history as $payment): ?>
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
        <!-- TAB: Service Requests                                            -->
        <!-- ================================================================ -->
        <div class="bd-panel" id="bd-tab-services" style="display:none;">
            <div class="bd-card" style="grid-column:1/-1;">
                <div class="bd-card__header"><h3 class="bd-card__title">Service Requests</h3></div>
                <div class="bd-card__body" style="padding-top:4px;">
                    <?php if (empty($service_requests ?? [])): ?>
                        <div class="bd-empty">No service requests yet.</div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="bd-table">
                                <thead><tr><th>ID</th><th>Package</th><th>Status</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($service_requests as $request): ?>
                                        <tr>
                                            <td><strong>#<?= esc((string) ($request['application_id'] ?? '-')) ?></strong></td>
                                            <td><?= esc((string) ($request['package_name'] ?? '-')) ?></td>
                                            <td><span class="bd-badge <?= ($request['status'] ?? '') === 'approved' || ($request['status'] ?? '') === 'completed' ? 'bd-badge--green' : 'bd-badge--teal' ?>"><?= esc(ucfirst((string) ($request['status'] ?? '-'))) ?></span></td>
                                            <td><?= esc((string) ($request['created_at'] ?? '-')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php endif; ?>

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
