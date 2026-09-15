<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'unregistered'); ?>
<?php /* Restricted blur+overlay: no caresync.css equivalent, see client/membership.php. */ ?>
<style>
    .restricted-wrap { position: relative; }
    .restricted-blur { filter: blur(4px); pointer-events: none; user-select: none; }
    .restricted-modal {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.55);
    }
</style>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<?php if ($state === 'unregistered'): ?>
    <div class="restricted-wrap">
        <div class="restricted-blur">
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Payment</h2></div>
                <div class="cs-panel__body">
                    <p class="cs-muted mb-0">Register to view your plan's payment overview.</p>
                </div>
            </section>
        </div>
        <div class="restricted-modal">
            <div class="cs-panel shadow" style="max-width: 420px;">
                <div class="cs-panel__body text-center">
                    <h5 class="mb-2">Register to access this feature</h5>
                    <p class="cs-muted">Payment is restricted until you complete plan registration.</p>
                    <a href="<?= base_url('plan-info') ?>" class="btn btn-primary">Register Now</a>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($state === 'awaiting_activation'): ?>
    <div class="restricted-wrap">
        <div class="restricted-blur">
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Payment</h2></div>
                <div class="cs-panel__body">
                    <p class="cs-muted mb-0">Complete your initial payment to unlock your payment overview.</p>
                </div>
            </section>
        </div>
        <div class="restricted-modal">
            <div class="cs-panel shadow" style="max-width: 420px;">
                <div class="cs-panel__body text-center">
                    <h5 class="mb-2">Complete Your Initial Payment</h5>
                    <p class="cs-muted">You already registered. Submit your initial payment to unlock your membership.</p>
                    <a href="<?= base_url('initial-payment') ?>" class="btn btn-primary">Go to Initial Payment</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if (($access['initial_payment_status'] ?? 'none') === 'cancelled'): ?>
        <div class="alert alert-danger">Payment rejected. Please resubmit.</div>
    <?php endif; ?>

    <?php if ($plan): ?>
        <section class="cs-panel">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title"><?= esc($plan_name !== '' ? $plan_name : 'Your Plan') ?></h2>
            </div>
            <div class="cs-panel__body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="cs-muted d-block">Plan Name</small>
                        <strong><?= esc($plan_name !== '' ? $plan_name : '-') ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="cs-muted d-block">Monthly Contribution</small>
                        <strong><?= cs_money($plan['monthly_fee'] ?? 0) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="cs-muted d-block">Remaining Balance</small>
                        <strong><?= cs_money($plan['remaining_balance'] ?? 0) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="cs-muted d-block">Months Paid</small>
                        <strong><?= esc((string) ((int) ($plan['months_paid'] ?? 0))) ?></strong>
                        <?php if ($months_awaiting_verification > 0): ?>
                            <div class="small text-muted mt-1">
                                <?= esc((string) $months_awaiting_verification) ?> month<?= $months_awaiting_verification === 1 ? '' : 's' ?> awaiting verification
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <small class="cs-muted d-block">Next Due Date</small>
                        <strong><?= cs_date($plan['next_due_date'] ?? null) ?></strong>
                    </div>
                </div>
            </div>
            <div class="cs-panel__foot">
                <?php if ((string) ($plan['status'] ?? '') !== 'active'): ?>
                    <div class="alert alert-info mb-3">Your initial payment is pending verification.</div>
                <?php endif; ?>
                <a class="btn btn-primary" href="<?= base_url('client/payment/make') ?>">Make Payment</a>
            </div>
        </section>
    <?php else: ?>
        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Payment</h2></div>
            <div class="cs-panel__body">
                <p class="cs-muted mb-0">No plan found on your account yet.</p>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
