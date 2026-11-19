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
        <?= $kv('ID Control No.', esc($id_control_no ?? '-')) ?>
        <?= $kv('Plan no.', esc($plan_no)) ?>
        <?= $kv('Plan', esc($plan_name !== '' ? $plan_name : '-')) ?>
        <?= $kv('Monthly fee', esc(cs_peso($monthly_fee))) ?>
        <?= $kv('Contract total', esc(cs_peso($contract))) ?>
        <?= $kv('Months paid', (int) $months_paid . ' of ' . (int) $total_months) ?>
    </tbody>
</table>

<?php if (! empty($sections)): ?><h3 style="margin:8px 0 4px;">Plan contributions</h3><?php endif; ?>
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

<?php foreach (($sections ?? []) as $s): ?>
    <h3 style="margin:14px 0 4px;">Package: <?= esc($s['title']) ?> <span style="font-weight:normal;">(<?= esc($s['status']) ?>)</span></h3>
    <table class="rpt-table" style="margin-bottom:6px;">
        <tbody>
            <?= $kv('Price', esc(cs_peso($s['price']))) ?>
            <?= $kv('Down payment', esc($s['down'])) ?>
            <?= $kv('Instalments', esc($s['term'])) ?>
        </tbody>
    </table>
    <?php if ($s['waiver'] !== ''): ?><p class="rpt-note" style="margin:0 0 6px;"><strong>Note:</strong> <?= esc($s['waiver']) ?></p><?php endif; ?>
    <table class="rpt-table">
        <thead><tr><th>Date</th><th>Applied to</th><th class="num">Amount</th><th>Method</th><th>OR / Reference</th><th class="num">Balance</th></tr></thead>
        <tbody>
            <?php if ($s['rows'] === []): ?>
                <tr><td colspan="6" class="rpt-empty">No payments on this package yet.</td></tr>
            <?php else: foreach ($s['rows'] as $r): ?>
                <tr><td><?= esc(date('M j, Y', strtotime($r['date']))) ?></td><td><?= esc($r['what']) ?></td><td class="num"><?= esc(cs_peso($r['amount'])) ?></td><td><?= esc($r['method']) ?></td><td><?= esc($r['ref'] !== '' ? $r['ref'] : '-') ?></td><td class="num"><?= esc(cs_peso($r['balance'])) ?></td></tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="2"><strong>Package paid</strong></td><td class="num"><strong><?= esc(cs_peso($s['paid'])) ?></strong></td><td colspan="3"></td></tr>
            <tr class="rpt-total"><td colspan="2"><strong>Package balance</strong></td><td class="num"><strong><?= esc(cs_peso($s['balance'])) ?></strong></td><td colspan="3"></td></tr>
        </tfoot>
    </table>
<?php endforeach; ?>
<?php if (! empty($sections)): ?>
    <table class="rpt-table" style="margin-top:12px;"><tbody>
        <tr class="rpt-total"><td style="width:50%"><strong>Total paid (plan + package)</strong></td><td class="num"><strong><?= esc(cs_peso($grand_paid)) ?></strong></td></tr>
        <tr class="rpt-total"><td><strong>Total balance (plan + package)</strong></td><td class="num"><strong><?= esc(cs_peso($grand_balance)) ?></strong></td></tr>
    </tbody></table>
<?php endif; ?>

<p class="rpt-note" style="margin-top:10px;">Only verified payments are listed. Date generated: <?= esc($generated) ?> (Philippine time).</p>
