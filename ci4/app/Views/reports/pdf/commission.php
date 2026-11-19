<?php
/** Commission report body (from remittances) - same columns as admin/reports/commission.php. */
$rows = $rows ?? [];
$t = ['n' => 0, 'g' => 0.0, 'ce' => 0.0, 'cp' => 0.0, 'ch' => 0.0, 'nc' => 0.0, 'np' => 0.0];
$rule = (new \App\Services\RemittanceService())->ruleText();
?>
<p class="rpt-note" style="margin:0 0 6px;">Rule in force: <?= esc($rule) ?>. Commission is earned when the Branch Admin confirms the remittance.</p>
<table class="rpt-table">
    <thead>
        <tr>
            <th>Collector</th>
            <th class="center">Remittances</th>
            <th class="num">Total Collected</th>
            <th class="num">Commission Earned</th>
            <th class="num">Commission Waiting</th>
            <th class="num">On Hold</th>
            <th class="num">Net Remitted</th>
            <th class="num">Net Waiting</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $t['n'] += (int) $row['remittances']; $t['g'] += (float) $row['gross']; $t['ce'] += (float) $row['commission_earned'];
            $t['cp'] += (float) $row['commission_pending']; $t['ch'] += (float) $row['commission_on_hold'];
            $t['nc'] += (float) $row['net_confirmed']; $t['np'] += (float) $row['net_pending'];
            ?>
            <tr>
                <td><?= esc((string) $row['collector_name']) ?></td>
                <td class="center"><?= (int) $row['remittances'] ?></td>
                <td class="num"><?= esc(cs_peso($row['gross'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['commission_earned'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['commission_pending'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['commission_on_hold'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['net_confirmed'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['net_pending'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="8" class="rpt-empty">No records for the selected filters.</td></tr>
        <?php endif; ?>
    </tbody>
    <?php if ($rows !== []): ?>
        <tfoot>
            <tr class="rpt-total">
                <td class="num">Total</td>
                <td class="center"><?= $t['n'] ?></td>
                <td class="num"><?= esc(cs_peso($t['g'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['ce'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['cp'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['ch'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['nc'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['np'])) ?></td>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>
