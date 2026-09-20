<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commission Report - Kaagapay Mo Karamay</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/caresync.css') ?>">
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: var(--cs-sans); font-size: 11px; margin: 0; color: #111; }
        .no-print { text-align: right; margin-bottom: 10px; }
        .no-print button { background: var(--cs-pine-600); color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-size: 12px; cursor: pointer; }
        h1 { font-size: 14px; margin: 0 0 2px; text-transform: uppercase; }
        h2 { font-size: 12px; margin: 0 0 8px; font-weight: normal; color: #444; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 7px; font-size: 10px; text-align: left; }
        th { background: #f0f0f0; text-transform: uppercase; }
        td.num, th.num { text-align: right; }
        td.empty { text-align: center; padding: 16px; }\n        tfoot td { font-weight: bold; background: #fafafa; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Print report</button></div>

    <h1>Kaagapay Mo Karamay Funeral Homes Co.</h1>
    <h2>
        Commission Report<?= ! empty($branch_name) ? ' &mdash; ' . esc($branch_name) : ' (System-wide)' ?>
    </h2>
    <div class="meta">
        Period: <?= cs_date($filters['date_from'] ?? null) ?> to <?= cs_date($filters['date_to'] ?? null) ?>
        &middot; Generated <?= cs_datetime(time()) ?>
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
                    <td class="num"><?= cs_money($row['total_collected'] ?? 0) ?></td>
                    <td class="num"><?= cs_money($row['commission'] ?? 0) ?></td>
                    <td class="num"><?= cs_money($row['net_remitted'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="empty">No collections found for this period.</td></tr>
            <?php endif; ?>
        </tbody>
        <?php if (! empty($rows)): ?>
            <tfoot>
                <tr>
                    <td colspan="4" class="num">Total Commission</td>
                    <td class="num"><?= cs_money($totalCommission) ?></td>
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
