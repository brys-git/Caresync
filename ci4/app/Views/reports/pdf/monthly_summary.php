<?php
/** Monthly collection summary report body (one row per closed month per collector). */
$rows = $rows ?? [];
$t = ['m' => 0, 'c' => 0, 'n' => 0, 'g' => 0.0, 'cash' => 0.0, 'gc' => 0.0, 'comm' => 0.0, 'net' => 0.0];
?>
<table class="rpt-table">
    <thead>
        <tr>
            <th>Month</th>
            <th>Collector</th>
            <th class="center">Assigned</th>
            <th class="center">Collected</th>
            <th class="center">Not collected</th>
            <th class="num">Cash</th>
            <th class="num">GCash</th>
            <th class="num">Gross</th>
            <th class="num">Commission</th>
            <th class="num">Net remitted</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $t['m'] += (int) $row['members_assigned']; $t['c'] += (int) $row['collected_count']; $t['n'] += (int) $row['not_collected_count'];
            $t['g'] += (float) $row['gross_amount']; $t['cash'] += (float) $row['cash_amount']; $t['gc'] += (float) $row['gcash_amount'];
            $t['comm'] += (float) $row['commission_amount']; $t['net'] += (float) $row['net_amount'];
            ?>
            <tr>
                <td><?= esc((string) $row['month_label']) ?></td>
                <td><?= esc((string) $row['collector_name']) ?><?= ! empty($row['branch_name']) ? ' <span class="rpt-note">(' . esc((string) $row['branch_name']) . ')</span>' : '' ?></td>
                <td class="center"><?= (int) $row['members_assigned'] ?></td>
                <td class="center"><?= (int) $row['collected_count'] ?></td>
                <td class="center"><?= (int) $row['not_collected_count'] ?></td>
                <td class="num"><?= esc(cs_peso($row['cash_amount'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['gcash_amount'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['gross_amount'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['commission_amount'])) ?></td>
                <td class="num"><?= esc(cs_peso($row['net_amount'])) ?></td>
            </tr>
            <?php foreach ($row['not_collected'] as $nc): ?>
                <tr><td></td><td colspan="9" class="rpt-note">Not collected: <?= esc((string) $nc['name']) ?> (<?= esc((string) ($nc['unique_identifier'] ?? '')) ?>) - <?= esc((string) $nc['reason']) ?></td></tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="10" class="rpt-empty">No closed months for the selected filters.</td></tr>
        <?php endif; ?>
    </tbody>
    <?php if ($rows !== []): ?>
        <tfoot>
            <tr class="rpt-total">
                <td class="num" colspan="2">Total</td>
                <td class="center"><?= $t['m'] ?></td>
                <td class="center"><?= $t['c'] ?></td>
                <td class="center"><?= $t['n'] ?></td>
                <td class="num"><?= esc(cs_peso($t['cash'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['gc'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['g'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['comm'])) ?></td>
                <td class="num"><?= esc(cs_peso($t['net'])) ?></td>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>
