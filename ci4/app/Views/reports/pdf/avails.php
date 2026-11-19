<?php
/** Avails and waivers report body (same columns as admin/reports/avails.php). */
$rows = $rows ?? [];
$sum = ['price' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
?>
<table class="rpt-table">
    <thead>
        <tr>
            <th>Plan Holder</th>
            <th>Package / Service</th>
            <th class="num">Price</th>
            <th>Down Payment</th>
            <th>Term</th>
            <th class="num">Paid So Far</th>
            <th class="num">Balance</th>
            <th>Status</th>
            <th>Approved</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
            $sum['price'] += (float) $r['package_price'];
            $sum['paid'] += (float) $r['paid_so_far'];
            $sum['balance'] += (float) $r['balance'];
            ?>
            <tr>
                <td><?= esc((string) $r['client_name']) ?><br><span class="rpt-note"><?= esc((string) ($r['unique_identifier'] ?: '-')) ?> &middot; <?= esc((string) ($r['branch_name'] ?? '')) ?></span></td>
                <td><?= esc((string) ($r['what'] ?? '-')) ?><br><span class="rpt-note"><?= esc((string) $r['kind_label']) ?></span></td>
                <td class="num"><?= esc(cs_peso($r['package_price'])) ?></td>
                <td>
                    <?php if ($r['down_payment_status'] === 'waived'): ?>
                        <strong>Waived</strong> (<?= esc(cs_peso($r['down_payment_amount'])) ?>)<br>
                        <span class="rpt-note">By <?= esc($r['waived_by_name'] !== '' ? $r['waived_by_name'] : '-') ?><?= $r['waived_at'] ? ' on ' . esc(date('j M Y', strtotime((string) $r['waived_at']))) : '' ?></span><br>
                        <span class="rpt-note">Reason: <?= esc((string) ($r['waiver_reason'] ?? '-')) ?></span>
                    <?php else: ?>
                        <?= esc((string) $r['down_label']) ?> (<?= esc(cs_peso($r['down_payment_amount'])) ?>)
                    <?php endif; ?>
                </td>
                <td><?= esc((string) $r['term_label']) ?><?php if ((int) $r['term_months'] > 0): ?><br><span class="rpt-note"><?= esc(cs_peso($r['monthly_instalment'])) ?> / month</span><?php endif; ?></td>
                <td class="num"><?= esc(cs_peso($r['paid_so_far'])) ?></td>
                <td class="num"><?= esc(cs_peso($r['balance'])) ?></td>
                <td><?= esc((string) $r['status_label']) ?></td>
                <td><?= $r['approved_at'] ? esc(date('j M Y', strtotime((string) $r['approved_at']))) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="9" class="rpt-empty">No avails for the selected filters.</td></tr>
        <?php endif; ?>
    </tbody>
    <?php if ($rows !== []): ?>
        <tfoot>
            <tr class="rpt-total">
                <td colspan="2" class="num">Total (<?= count($rows) ?> avail<?= count($rows) === 1 ? '' : 's' ?>)</td>
                <td class="num"><?= esc(cs_peso($sum['price'])) ?></td>
                <td colspan="2"></td>
                <td class="num"><?= esc(cs_peso($sum['paid'])) ?></td>
                <td class="num"><?= esc(cs_peso($sum['balance'])) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>
