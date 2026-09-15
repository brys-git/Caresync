<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $state = (string) ($access['state'] ?? 'unregistered'); ?>

<?php // Flash messages are already surfaced as toasts by layouts/_shell.php - no need to render them again here. ?>

<?php if ($state === 'unregistered'): ?>
    <section class="cs-panel">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Payment History</h2></div>
        <div class="cs-panel__body">
            <p class="cs-muted mb-0">Register to view your payment history.</p>
        </div>
    </section>
<?php else: ?>
    <section class="cs-panel">
        <div class="cs-panel__head">
            <div>
                <h2 class="cs-panel__title">Payment History</h2>
                <p class="cs-panel__note">Every payment on this plan, newest first.</p>
            </div>
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
<?php endif; ?>
<?= $this->endSection() ?>
