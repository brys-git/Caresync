<?php
/**
 * Reusable Advance Payment results table.
 *
 * Expected view data:
 *   array  $ap_rows           payment rows (needs the staff_first_name/staff_last_name and
 *                              payment_coverage_until columns joined in by the controller)
 *   bool   $ap_can_approve    (optional) show Approve/Reject actions for pending rows
 *   string $ap_action_base    (optional) base URL for approve/reject actions, e.g.
 *                              base_url('branch-admin/payment-tracking')
 *   bool   $ap_show_proof     (optional) show a Proof column
 *   string $ap_empty_message  (optional) text shown when there are no rows
 */
$paymentService = new \App\Services\PaymentService();
$canApprove = ! empty($ap_can_approve ?? false);
$showProof = ! empty($ap_show_proof ?? false);
$actionBase = (string) ($ap_action_base ?? '');
?>
<?php if (empty($ap_rows)): ?>
    <?= view('components/empty_state', [
        'icon'  => 'ti-receipt',
        'title' => (string) ($ap_empty_message ?? 'No payment records found.'),
    ]) ?>
<?php else: ?>
    <div class="cs-tablewrap">
        <table class="cs-table">
            <thead>
                <tr>
                    <th>Plan Holder</th>
                    <th>Coverage Period</th>
                    <th class="cs-num">Amount</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference / OR</th>
                    <th>Staff Account</th>
                    <th>Remaining Months</th>
                    <th>Status</th>
                    <?php if ($showProof): ?><th>Proof</th><?php endif; ?>
                    <?php if ($canApprove): ?><th class="cs-table__actions">Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ap_rows as $row): ?>
                    <?php
                    $status = strtolower((string) ($row['status'] ?? 'pending'));
                    $coverageStart = (string) ($row['coverage_start'] ?? $row['payment_date'] ?? '');
                    $coverageLabel = $coverageStart !== ''
                        ? $paymentService->describeCoverage($coverageStart, (int) ($row['months_covered'] ?? 1))
                        : (int) ($row['months_covered'] ?? 1) . ' month(s)';
                    $remaining = $paymentService->remainingMonths($row['payment_coverage_until'] ?? null);
                    $staffName = trim((string) ($row['staff_first_name'] ?? '') . ' ' . (string) ($row['staff_last_name'] ?? ''));
                    ?>
                    <tr>
                        <td>
                            <?= esc((string) ($row['first_name'] . ' ' . $row['last_name'])) ?>
                            <div class="cs-table__sub"><?= esc((string) ($row['unique_identifier'] ?: 'No ID')) ?></div>
                        </td>
                        <td><?= esc($coverageLabel) ?></td>
                        <td class="cs-num"><?= cs_money($row['amount']) ?></td>
                        <td class="text-nowrap"><?= cs_date($row['payment_date']) ?></td>
                        <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                        <td><?= esc((string) ($row['reference_number'] ?: ($row['official_receipt_number'] ?: '-'))) ?></td>
                        <td><?= $staffName !== '' ? esc($staffName) : '<span class="text-muted">-</span>' ?></td>
                        <td><?= $remaining > 0 ? esc((string) $remaining) : '<span class="text-muted">-</span>' ?></td>
                        <td><?= cs_status($status) ?></td>
                        <?php if ($showProof): ?>
                            <td>
                                <?php if (! empty($row['proof_image'] ?? null)): ?>
                                    <a href="<?= base_url('uploads/payment-proofs/' . $row['proof_image']) ?>" target="_blank">View</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($canApprove): ?>
                            <td class="cs-table__actions">
                                <?php if ($status === 'pending'): ?>
                                    <form method="post" action="<?= esc($actionBase . '/approve/' . (int) $row['payment_id'], 'attr') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                    </form>
                                    <form method="post" action="<?= esc($actionBase . '/reject/' . (int) $row['payment_id'], 'attr') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="text" name="rejection_reason" class="form-control form-control-sm d-inline-block" style="max-width: 180px;" placeholder="Rejection reason">
                                        <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">No action</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
