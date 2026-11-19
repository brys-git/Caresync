<?php
/** Overdue report body - same columns and wording as admin/reports/overdue.php. */
$rows = $rows ?? [];
$totalBalance = 0.0; $totalPkgBalance = 0.0; $totalPlanOver = 0.0; $totalPkgOver = 0.0;
?>
<table class="rpt-table">
    <thead>
        <tr>
            <th>Plan Holder</th>
            <th>Branch</th>
            <th>Contact</th>
            <th class="num">Plan Balance</th>
            <th class="num">Package Balance</th>
            <th class="center">Months Paid</th>
            <th class="num">Plan Overdue</th>
            <th class="num">Package Overdue</th>
            <th class="num">Total Overdue</th>
            <th class="center">Days Overdue</th>
            <th>Oldest Due Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $totalBalance += (float) ($row['remaining_balance'] ?? 0);
            $totalPkgBalance += (float) ($row['package_balance'] ?? 0);
            $totalPlanOver += (float) ($row['overdue_amount'] ?? 0);
            $totalPkgOver += (float) ($row['package_overdue_amount'] ?? 0);
            $countdown = (string) ($row['status_text'] ?? '');
            ?>
            <tr>
                <td>
                    <?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?><br>
                    <span class="rpt-note"><?= esc((string) ($row['unique_identifier'] ?? '-')) ?></span>
                </td>
                <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                <td><?= esc((string) ($row['contact_number'] ?? '-')) ?></td>
                <td class="num"><?= esc(cs_peso($row['remaining_balance'] ?? 0)) ?></td>
                <td class="num"><?= ! empty($row['has_package']) ? esc(cs_peso($row['package_balance'] ?? 0)) : '-' ?></td>
                <td class="center"><?= (int) ($row['months_paid'] ?? 0) ?></td>
                <td class="num"><?= (int) ($row['overdue_months'] ?? 0) > 0 ? esc(cs_peso($row['overdue_amount'] ?? 0)) . '<br><span class="rpt-note">' . (int) $row['overdue_months'] . ' month(s)</span>' : '-' ?></td>
                <td class="num"><?= (int) ($row['package_overdue_count'] ?? 0) > 0 ? esc(cs_peso($row['package_overdue_amount'] ?? 0)) . '<br><span class="rpt-note">' . (int) $row['package_overdue_count'] . ' instalment(s)</span>' : '-' ?></td>
                <td class="num"><strong><?= esc(cs_peso($row['total_overdue_amount'] ?? ($row['overdue_amount'] ?? 0))) ?></strong></td>
                <td class="center"><?= (int) ($row['days_overdue'] ?? 0) ?> days</td>
                <td><?= cs_date($row['next_due_date'] ?? null) ?></td>
                <td><?= esc($countdown) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="13" class="rpt-empty">No records for the selected filters.</td></tr>
        <?php endif; ?>
    </tbody>
    <?php if ($rows !== []): ?>
        <tfoot>
            <tr class="rpt-total">
                <td colspan="3" class="num">Total (<?= count($rows) ?> account<?= count($rows) === 1 ? '' : 's' ?>)</td>
                <td class="num"><?= esc(cs_peso($totalBalance)) ?></td>
                <td class="num"><?= esc(cs_peso($totalPkgBalance)) ?></td>
                <td></td>
                <td class="num"><?= esc(cs_peso($totalPlanOver)) ?></td>
                <td class="num"><?= esc(cs_peso($totalPkgOver)) ?></td>
                <td class="num"><?= esc(cs_peso($totalPlanOver + $totalPkgOver)) ?></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>
