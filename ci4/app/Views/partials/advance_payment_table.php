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
 *   int    $ap_colspan        (optional) colspan for the empty-state row
 */
$paymentService = new \App\Services\PaymentService();
$canApprove = ! empty($ap_can_approve ?? false);
$showProof = ! empty($ap_show_proof ?? false);
$actionBase = (string) ($ap_action_base ?? '');
// 9 fixed columns: Plan Holder, Coverage Period, Amount, Date, Method,
// Reference/OR, Staff Account, Remaining Months, Status.
$colspan = (int) ($ap_colspan ?? (9 + ($canApprove ? 1 : 0) + ($showProof ? 1 : 0)));
?>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Plan Holder</th>
                <th>Coverage Period</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Method</th>
                <th>Reference / OR</th>
                <th>Staff Account</th>
                <th>Remaining Months</th>
                <th>Status</th>
                <?php if ($showProof): ?><th>Proof</th><?php endif; ?>
                <?php if ($canApprove): ?><th class="text-end">Action</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($ap_rows ?? []) as $row): ?>
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
                    <td><?= esc((string) ($row['first_name'] . ' ' . $row['last_name'])) ?><br><small class="text-muted"><?= esc((string) ($row['unique_identifier'] ?: 'No ID')) ?></small></td>
                    <td><?= esc($coverageLabel) ?></td>
                    <td>P<?= esc(number_format((float) $row['amount'], 2)) ?></td>
                    <td><?= esc((string) $row['payment_date']) ?></td>
                    <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                    <td><?= esc((string) ($row['reference_number'] ?: ($row['official_receipt_number'] ?: '-'))) ?></td>
                    <td><?= $staffName !== '' ? esc($staffName) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= $remaining > 0 ? esc((string) $remaining) : '<span class="text-muted">-</span>' ?></td>
                    <td>
                        <span class="badge text-bg-<?= $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') ?>">
                            <?= esc(ucfirst($status)) ?>
                        </span>
                    </td>
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
                        <td class="text-end">
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
            <?php if (empty($ap_rows)): ?>
                <tr><td colspan="<?= $colspan ?>" class="text-center text-muted py-4"><?= esc((string) ($ap_empty_message ?? 'No payment records found.')) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
