<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commission Report - Kaagapay Mo Karamay</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; margin: 0; color: #111; }
        .no-print { text-align: right; margin-bottom: 10px; }
        .no-print button { background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 12px; cursor: pointer; }
        h1 { font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
        h2 { font-size: 12px; margin: 0 0 8px; font-weight: normal; color: #444; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 7px; font-size: 10px; text-align: left; }
        th { background: #f0f0f0; text-transform: uppercase; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #fafafa; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Print</button></div>

    <h1>Kaagapay Mo Karamay Funeral Homes Co.</h1>
    <h2>
        Commission Report<?= ! empty($branch_name) ? ' &mdash; ' . esc($branch_name) : ' (System-wide)' ?>
    </h2>
    <div class="meta">
        Period: <?= esc((string) ($filters['date_from'] ?? '-')) ?> to <?= esc((string) ($filters['date_to'] ?? '-')) ?>
        &middot; Generated <?= esc(date('F j, Y g:i A')) ?>
        &middot; <?= count($rows ?? []) ?> collector(s)
    </div>

    <table>
        <thead>
            <tr>
                <th>Collector</th>
                <th>Role</th>
                <th class="num">Transactions</th>
                <th class="num">Total Collected</th>
                <th class="num">Commission (10%)</th>
                <th class="num">Net Remitted</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalCommission = 0.0; ?>
            <?php foreach (($rows ?? []) as $row): ?>
                <?php $totalCommission += (float) ($row['commission'] ?? 0); ?>
                <tr>
                    <td><?= esc(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''))) ?></td>
                    <td><?= esc((string) ($row['role_name'] ?? '-')) ?></td>
                    <td class="num"><?= esc((string) ($row['transaction_count'] ?? 0)) ?></td>
                    <td class="num"><?= esc(number_format((float) ($row['total_collected'] ?? 0), 2)) ?></td>
                    <td class="num"><?= esc(number_format((float) ($row['commission'] ?? 0), 2)) ?></td>
                    <td class="num"><?= esc(number_format((float) ($row['net_remitted'] ?? 0), 2)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" style="text-align:center; padding:16px;">No collections found for this period.</td></tr>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($rows)): ?>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;">Total Commission</td>
                    <td class="num"><?= esc(number_format($totalCommission, 2)) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <?php if (! empty($auto_print)): ?>
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>
</html>
