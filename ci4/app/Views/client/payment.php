<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'unregistered'); ?>
<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<?php if ($state === 'unregistered' || $state === 'awaiting_activation'): ?>
    <?php $unregistered = $state === 'unregistered'; ?>
    <div class="cs-restricted">
        <div class="cs-restricted__blur">
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Payment history</h2></div>
                <div class="cs-panel__body">
                    <p class="cs-muted mb-0"><?= $unregistered ? 'Register to view your plan\'s payment overview.' : 'Complete your initial payment to unlock your payment overview.' ?></p>
                </div>
            </section>
        </div>
        <div class="cs-restricted__overlay">
            <div class="cs-panel cs-restricted__card">
                <div class="cs-panel__body text-center">
                    <h2 class="cs-panel__title mb-2"><?= $unregistered ? 'Register to access this feature' : 'Complete your initial payment' ?></h2>
                    <p class="cs-muted"><?= $unregistered ? 'Payment is restricted until you complete plan registration.' : 'You already registered. Submit your initial payment to unlock your membership.' ?></p>
                    <a href="<?= base_url($unregistered ? 'plan-info' : 'initial-payment') ?>" class="btn btn-primary"><?= $unregistered ? 'Register now' : 'Go to initial payment' ?></a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php if (($access['initial_payment_status'] ?? 'none') === 'cancelled'): ?>
        <?= view('components/alert', ['type' => 'danger', 'dismissible' => false, 'message' => 'Payment rejected. Please resubmit.']) ?>
    <?php endif; ?>

    <?php if ($plan): ?>
        <?php $canPay = (int) ($remaining_months ?? 0) > 0; ?>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <h2 class="cs-panel__title">Payment history</h2>
                <?php if ($canPay): ?>
                    <a class="btn btn-primary btn-sm" href="<?= base_url('client/payment/make') ?>">Make payment</a>
                <?php else: ?>
                    <span class="d-flex align-items-center gap-2">
                        <a class="btn btn-primary btn-sm disabled" aria-disabled="true" tabindex="-1" href="#">Make payment</a>
                        <small class="cs-muted">No months left to pay in this cycle.</small>
                    </span>
                <?php endif; ?>
            </div>
            <div class="cs-panel__body">
                <?php if ((string) ($plan['status'] ?? '') !== 'active'): ?>
                    <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'message' => 'Your initial payment is pending verification.']) ?>
                <?php endif; ?>
                <?= view('components/stat_rail', ['stats' => [
                    ['label' => 'Monthly contribution', 'value' => $plan['monthly_fee'] ?? 0, 'money' => true],
                    ['label' => 'Months paid', 'value' => (int) ($plan['months_paid'] ?? 0), 'meta' => $months_awaiting_verification > 0 ? ($months_awaiting_verification . ' month' . ($months_awaiting_verification === 1 ? '' : 's') . ' awaiting verification') : null],
                    ['label' => 'Remaining balance', 'value' => $plan['remaining_balance'] ?? 0, 'money' => true],
                    ['label' => 'Next due date', 'value' => strip_tags(cs_date($plan['next_due_date'] ?? null))],
                ]]) ?>
                <?php $planHolderRemarks = trim((string) ($access['plan_holder']['remarks'] ?? '')); ?>
                <?php if ($planHolderRemarks !== ''): ?>
                    <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'class' => 'mt-3 mb-0', 'title' => 'Note from your branch:', 'message' => $planHolderRemarks]) ?>
                <?php endif; ?>
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
                                    <th>Months covered</th>
                                    <th class="cs-num">Amount</th>
                                    <th>Payment method</th>
                                    <th>Reference / OR</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= cs_date($payment['payment_date'] ?? null) ?></td>
                                        <td class="text-nowrap"><?= cs_coverage_period($payment['coverage_start'] ?? null, (int) ($payment['months_covered'] ?? 1)) ?></td>
                                        <td class="cs-num"><?= cs_money($payment['amount'] ?? 0) ?></td>
                                        <td><?= esc(strtoupper((string) ($payment['payment_method'] ?? '—'))) ?></td>
                                        <td><code><?= esc((string) ($payment['official_receipt_number'] ?? $payment['reference_number'] ?? '—')) ?></code></td>
                                        <td><?= esc((string) ($payment['remarks'] ?: '—')) ?></td>
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
            <div class="cs-panel__head"><h2 class="cs-panel__title">Payment history</h2></div>
            <div class="cs-panel__body">
                <p class="cs-muted mb-0">No plan found on your account yet.</p>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
