<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remittance Form - Kaagapay Mo Karamay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            width: 210mm;
            min-height: 297mm;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; background: #fff; }
            .form-container {
                border: none;
                box-shadow: none;
                width: 100%;
                min-height: calc(297mm - 16mm);
                margin: 0;
                padding: 8mm;
            }
        }

        body {
            margin: 0 auto;
        }

        .form-container {
            width: 100%;
            min-height: calc(297mm - 16mm);
            margin: 0 auto;
        }

        table td, table th {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 0.75rem;
            height: 28px;
        }
        .input-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
            padding-left: 5px;
            height: 1.2rem;
            vertical-align: bottom;
        }
    </style>
</head>
<body class="bg-gray-100 py-8 px-4 font-sans text-xs">
    <div class="no-print max-w-5xl mx-auto mb-4 text-right">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Print Form</button>
    </div>

    <div class="form-container max-w-5xl mx-auto bg-white p-6 shadow-lg border border-gray-300">
        <div class="flex justify-between items-start border-b-2 border-black pb-2 mb-2">
            <div class="flex items-center gap-4">
                <div class="w-24 h-24 border border-gray-300 flex items-center justify-center text-[10px] text-center italic">
                    Logo
                </div>
                <div>
                    <h1 class="text-xl font-bold uppercase tracking-tighter">KAAGAPAY MO KARAMAY FUNERAL HOMES CO.</h1>
                    <h2 class="text-lg font-bold text-center">DAMAYAN BURIAL PROGRAM</h2>
                    <p class="text-[10px]">
                        <strong>MAIN OFFICE:</strong> #65 J.F. Dias Ave. Ampid 1, San Mateo, Rizal<br>
                        <strong>BRANCH OFFICE:</strong> 01 Dakila cor. Constitutional Road, Batasan Hills, Q.C. / 61 B Rizal Ave., corner Santa Cruz, Manila<br>
                        Poblacion, Puerto Galera, Oriental Mindoro / Brgy. Babangonan, Victoria, Oriental Mindoro
                    </p>
                </div>
            </div>
            <div class="text-right text-[10px]">
                <p>SEC. REG. Cs201511616</p>
                <p>TIN #: 099-062-116</p>
                <p class="mt-2 text-sm">DATE: <span class="input-line w-32"><?= esc($export_date ?? '') ?></span></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-[11px] mb-2 font-bold uppercase italic">
            <div>Ricardo C. Ramilo <span class="block font-normal text-[9px] not-italic">FOUNDER / CEO</span></div>
            <div class="text-right">Cells: 0997-512-7828 / 0928-934-7852<br>Tel No.: 8-518-9288</div>
        </div>

        <div class="bg-gray-200 text-center py-1 border border-black font-bold tracking-widest text-sm mb-2">
            REMITTANCES
        </div>

        <div class="grid grid-cols-3 gap-2 mb-2 uppercase text-[10px]">
            <div>NAME OF COORDINATOR: <span class="input-line w-48"><?= esc($coordinator_name ?? '') ?></span></div>
            <div>CONTACT NUMBERS: <span class="input-line w-48"><?= esc($coordinator_contact ?? '') ?></span></div>
            <div>LOCATION/AREA: <span class="input-line w-48"><?= esc($location_area ?? '') ?></span></div>
        </div>

        <table class="w-full border-collapse border border-black">
            <thead class="bg-gray-100 uppercase text-[9px]">
                <tr>
                    <th rowspan="2" class="w-8">No.</th>
                    <th rowspan="2" class="w-64 text-left">Name of Plan Holders</th>
                    <th rowspan="2" class="w-16">I.D. Control Number</th>
                    <th rowspan="2" class="w-16">Date Started</th>
                    <th colspan="12" class="text-center">Months</th>
                </tr>
                <tr class="text-[8px]">
                    <th class="w-6">JAN</th>
                    <th class="w-6">FEB</th>
                    <th class="w-6">MAR</th>
                    <th class="w-6">APR</th>
                    <th class="w-6">MAY</th>
                    <th class="w-6">JUN</th>
                    <th class="w-6">JUL</th>
                    <th class="w-6">AUG</th>
                    <th class="w-6">SEPT</th>
                    <th class="w-6">OCT</th>
                    <th class="w-6">NOV</th>
                    <th class="w-6">DEC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($export_rows ?? []) as $row): ?>
                    <tr>
                        <td class="text-center font-bold"><?= (int) ($row['no'] ?? 0) ?>.</td>
                        <td><?= esc($row['plan_holder_name'] ?? '') ?></td>
                        <td><?= esc($row['control_no'] ?? '') ?></td>
                        <td><?= esc($row['date_started'] ?? '') ?></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="text-center"><?= esc($row['months'][$m] ?? '') ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4 text-[10px] italic">
            * Please ensure all details are correct before submission.
        </div>
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
