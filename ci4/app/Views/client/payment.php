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
        <?php $canPay = (int) ($remaining_months ?? 0) > 0; ?>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Payment History</h2>
                <?php if ($canPay): ?>
                    <a class="btn btn-primary btn-sm" href="<?= base_url('client/payment/make') ?>">Make Payment</a>
                <?php else: ?>
                    <span class="d-flex align-items-center gap-2">
                        <a class="btn btn-primary btn-sm disabled" aria-disabled="true" tabindex="-1" href="#">Make Payment</a>
                        <small class="cs-muted">No months left to pay in this cycle.</small>
                    </span>
                <?php endif; ?>
            </div>
            <div class="cs-panel__body">
                <?php if ((string) ($plan['status'] ?? '') !== 'active'): ?>
                    <div class="alert alert-info mb-3">Your initial payment is pending verification.</div>
                <?php endif; ?>
                <?= view('components/stat_rail', ['stats' => [
                    ['label' => 'Monthly Contribution', 'value' => $plan['monthly_fee'] ?? 0, 'money' => true],
                    ['label' => 'Months Paid', 'value' => (int) ($plan['months_paid'] ?? 0), 'meta' => $months_awaiting_verification > 0 ? ($months_awaiting_verification . ' month' . ($months_awaiting_verification === 1 ? '' : 's') . ' awaiting verification') : null],
                    ['label' => 'Remaining Balance', 'value' => $plan['remaining_balance'] ?? 0, 'money' => true],
                    ['label' => 'Next Due Date', 'value' => cs_date($plan['next_due_date'] ?? null)],
                ]]) ?>
            </div>
            <div class="cs-panel__body cs-panel__body--flush">
                <?php if (empty($payments)): ?>
                    <?= view('components/empty_state', [
                        'icon'  => 'ti-receipt',
                        'title' => 'No payments recorded yet',
                        'text'  => 'Once your first payment is posted, it appears here with a receipt.',
                    ]) ?>
                <?php else: ?>
                    <div class="cs-tablewrap">
                        <table class="cs-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Months Covered</th>
                                    <th class="cs-num">Amount</th>
                                    <th>Payment Method</th>
                                    <th>Reference Number</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <?php $status = strtolower((string) ($payment['status'] ?? 'pending')); ?>
                                    <tr>
                                        <td class="text-nowrap"><?= cs_date($payment['payment_date'] ?? null) ?></td>
                                        <td><?= esc((string) ((int) ($payment['months_covered'] ?? 1))) ?></td>
                                        <td class="cs-num"><?= cs_money($payment['amount'] ?? 0) ?></td>
                                        <td><?= esc(strtoupper((string) ($payment['payment_method'] ?? '-'))) ?></td>
                                        <td><?= esc((string) ($payment['reference_number'] ?? '-')) ?></td>
                                        <td><?= cs_status($status) ?></td>
                                        <td>
                                            <?php if ($status === 'paid'): ?>
                                                <a class="btn btn-ghost btn-sm" href="<?= base_url('client/payment/download-receipt/' . (int) $payment['payment_id']) ?>">
                                                    <i class="ti ti-download" aria-hidden="true"></i>
                                                    <span class="cs-visually-hidden">Download receipt</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
