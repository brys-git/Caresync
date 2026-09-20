<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remittance Form - Kaagapay Mo Karamay</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/caresync.css') ?>">
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 1.5rem 1rem; background: var(--cs-paper); font-family: var(--cs-sans); font-size: 12px; color: #000; }
        .no-print { max-width: 64rem; margin: 0 auto 1rem; text-align: right; }
        .form-container { max-width: 64rem; margin: 0 auto; background: #fff; padding: 1.5rem; border: 1px solid #ccc; }
        .rf-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: .5rem; margin-bottom: .5rem; }
        .rf-brand { display: flex; align-items: center; gap: 1rem; }
        .rf-logo { width: 6rem; height: 6rem; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px; font-style: italic; text-align: center; }
        .rf-title { font-size: 1.25rem; font-weight: 700; text-transform: uppercase; margin: 0; }
        .rf-sub { font-size: 1.1rem; font-weight: 700; text-align: center; margin: 0; }
        .rf-small { font-size: 10px; margin: 0; }
        .rf-right { text-align: right; font-size: 10px; }
        .rf-right p { margin: 0; }
        .rf-people { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 11px; margin-bottom: .5rem; font-weight: 700; text-transform: uppercase; font-style: italic; }
        .rf-people small { display: block; font-weight: 400; font-size: 9px; font-style: normal; }
        .rf-banner { background: #e5e7eb; text-align: center; padding: .25rem 0; border: 1px solid #000; font-weight: 700; letter-spacing: .1em; font-size: .9rem; margin-bottom: .5rem; }
        .rf-fields { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: .5rem; text-transform: uppercase; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        th, td { border: 1px solid #000; padding: 4px 6px; font-size: .75rem; height: 28px; }
        thead { background: #f3f4f6; text-transform: uppercase; font-size: 9px; }
        th.rf-name { text-align: left; width: 16rem; }
        td.rf-center { text-align: center; }
        .rf-months th { font-size: 8px; width: 1.5rem; }
        .input-line { border-bottom: 1px solid #000; display: inline-block; min-width: 150px; padding-left: 5px; height: 1.2rem; vertical-align: bottom; }
        .rf-note { margin-top: 1rem; font-size: 10px; font-style: italic; }
        .btn-print { background: var(--cs-pine-600); color: #fff; border: 0; padding: .5rem 1rem; border-radius: 6px; cursor: pointer; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; background: #fff; }
            .form-container { border: none; max-width: none; padding: 8mm; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" class="btn-print" onclick="window.print()">Print form</button></div>

    <div class="form-container">
        <div class="rf-head">
            <div class="rf-brand">
                <div class="rf-logo">Logo</div>
                <div>
                    <h1 class="rf-title">KAAGAPAY MO KARAMAY FUNERAL HOMES CO.</h1>
                    <h2 class="rf-sub">DAMAYAN BURIAL PROGRAM</h2>
                    <p class="rf-small">
                        <strong>MAIN OFFICE:</strong> #65 J.F. Dias Ave. Ampid 1, San Mateo, Rizal<br>
                        <strong>BRANCH OFFICE:</strong> 01 Dakila cor. Constitutional Road, Batasan Hills, Q.C. / 61 B Rizal Ave., corner Santa Cruz, Manila<br>
                        Poblacion, Puerto Galera, Oriental Mindoro / Brgy. Babangonan, Victoria, Oriental Mindoro
                    </p>
                </div>
            </div>
            <div class="rf-right">
                <p>SEC. REG. Cs201511616</p>
                <p>TIN #: 099-062-116</p>
                <p>DATE: <span class="input-line"><?= esc($export_date ?? '') ?></span></p>
            </div>
        </div>

        <div class="rf-people">
            <div>Ricardo C. Ramilo <small>FOUNDER / CEO</small></div>
            <div class="rf-right">Cells: 0997-512-7828 / 0928-934-7852<br>Tel No.: 8-518-9288</div>
        </div>

        <div class="rf-banner">REMITTANCES</div>

        <div class="rf-fields">
            <div>NAME OF COORDINATOR: <span class="input-line"><?= esc($coordinator_name ?? '') ?></span></div>
            <div>CONTACT NUMBERS: <span class="input-line"><?= esc($coordinator_contact ?? '') ?></span></div>
            <div>LOCATION/AREA: <span class="input-line"><?= esc($location_area ?? '') ?></span></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2">No.</th>
                    <th rowspan="2" class="rf-name">Name of Plan Holders</th>
                    <th rowspan="2">I.D. Control Number</th>
                    <th rowspan="2">Date Started</th>
                    <th colspan="12">Months</th>
                </tr>
                <tr class="rf-months">
                    <?php foreach (['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC'] as $mon): ?>
                        <th><?= $mon ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($export_rows ?? []) as $row): ?>
                    <tr>
                        <td class="rf-center"><strong><?= (int) ($row['no'] ?? 0) ?>.</strong></td>
                        <td><?= esc($row['plan_holder_name'] ?? '') ?></td>
                        <td><?= esc($row['control_no'] ?? '') ?></td>
                        <td><?= esc($row['date_started'] ?? '') ?></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="rf-center"><?= esc($row['months'][$m] ?? '') ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="rf-note">* Please ensure all details are correct before submission.</div>
    </div>

    <?php if (! isset($auto_print) || $auto_print === true): ?>
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>
</html>
