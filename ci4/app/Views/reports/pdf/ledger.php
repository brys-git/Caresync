<?php
/**
 * Ledger of a plan holder (PDF body; header/layout are the shared report ones).
 * Data from LedgerPdfService::build().
 */
$kv = static fn (string $label, string $value): string =>
    '<tr><td style="width:26%;background:#f0f0f0;font-weight:bold;">' . esc($label) . '</td><td>' . $value . '</td></tr>';
?>
<table class="rpt-table" style="margin-bottom:10px;">
    <tbody>
        <?= $kv('Plan holder', esc($holder_name)) ?>
        <?= $kv('Plan no.', esc($plan_no)) ?>
        <?= $kv('Plan', esc($plan_name !== '' ? $plan_name : '-')) ?>
        <?= $kv('Monthly fee', esc(cs_peso($monthly_fee))) ?>
        <?= $kv('Contract total', esc(cs_peso($contract))) ?>
        <?= $kv('Months paid', (int) $months_paid . ' of ' . (int) $total_months) ?>
    </tbody>
</table>

<table class="rpt-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Coverage</th>
            <th class="num">Amount</th>
            <th>Method</th>
            <th>OR / Reference</th>
            <th>Received by</th>
            <th class="num">Running total</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($payments === []): ?>
            <tr><td colspan="7" class="rpt-empty">No verified payments yet.</td></tr>
        <?php else: ?>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= esc(date('M j, Y', strtotime($p['date']))) ?></td>
                    <td><?= esc($p['period']) ?></td>
                    <td class="num"><?= esc(cs_peso($p['amount'])) ?></td>
                    <td><?= esc($p['method']) ?></td>
                    <td><?= esc($p['ref'] !== '' ? $p['ref'] : '-') ?></td>
                    <td><?= esc($p['received'] !== '' ? $p['received'] : '-') ?></td>
                    <td class="num"><?= esc(cs_peso($p['running'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr><td colspan="2"><strong>Total paid</strong></td><td class="num"><strong><?= esc(cs_peso($total_paid)) ?></strong></td><td colspan="4"></td></tr>
        <tr class="rpt-total"><td colspan="2"><strong>Remaining balance</strong></td><td class="num"><strong><?= esc(cs_peso($remaining)) ?></strong></td><td colspan="4"></td></tr>
    </tfoot>
</table>

<p class="rpt-note" style="margin-top:10px;">Only verified payments are listed. Date generated: <?= esc($generated) ?> (Philippine time).</p>
