<?= $this->extend('layouts/branch_admin') ?>

<?= $this->section('content') ?>
<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($payments)): ?>
            <?= view('components/empty_state', [
                'icon'  => 'ti-cash',
                'title' => 'No cash payments recorded yet',
                'action' => ['label' => 'Record New Payment', 'url' => 'branch-admin/cash-payment-record'],
            ]) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Months</th>
                            <th class="cs-num">Amount</th>
                            <th>Reference / OR</th>
                            <th>Date Recorded</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= esc((string) $payment['client_name']) ?></td>
                                <td><?= (int) $payment['months_covered'] ?></td>
                                <td class="cs-num"><?= cs_money($payment['amount']) ?></td>
                                <td><code><?= esc((string) $payment['receipt_number']) ?></code></td>
                                <td class="text-nowrap"><?= cs_date($payment['recorded_date'] ?? null) ?></td>
                                <td>
                                    <?php if ($payment['verified']): ?>
                                        <?= cs_status('verified') ?>
                                        <div class="cs-table__sub"><?= cs_date($payment['verified_date'] ?? null) ?></div>
                                    <?php else: ?>
                                        <?= cs_status('pending', 'Pending Verification') ?>
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

<?php if (! empty($payments)): ?>
    <div class="mt-3">
        <a href="<?= base_url('branch-admin/cash-payment-record') ?>" class="btn btn-primary btn-sm">Record New Payment</a>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
