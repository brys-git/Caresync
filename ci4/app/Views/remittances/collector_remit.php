<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$sm = $summary ?? [];
$statusTone = ['submitted' => 'text-bg-warning', 'confirmed' => 'text-bg-success', 'discrepancy' => 'text-bg-danger', 'cancelled' => 'text-bg-secondary'];
?>
<?= view('components/stat_rail', ['stats' => [
    ['label' => 'Ready to remit', 'icon' => 'ti-cash', 'value' => $sm['available_gross'] ?? 0, 'money' => true, 'meta' => number_format((float) ($sm['available_count'] ?? 0)) . ' collection(s), ₱' . number_format((float) ($sm['available_commission'] ?? 0), 2) . ' commission when remitted'],
    ['label' => 'Commission earned', 'icon' => 'ti-coin', 'value' => $sm['earned'] ?? 0, 'money' => true, 'tone' => 'ok', 'meta' => 'on confirmed remittances'],
    ['label' => 'Waiting for confirmation', 'icon' => 'ti-hourglass', 'value' => $sm['pending'] ?? 0, 'money' => true, 'tone' => 'warn'],
    ['label' => 'On hold (discrepancy)', 'icon' => 'ti-alert-triangle', 'value' => $sm['on_hold'] ?? 0, 'money' => true, 'tone' => ((float) ($sm['on_hold'] ?? 0)) > 0 ? 'stop' : ''],
]]) ?>

<p class="cs-muted mb-3"><i class="ti ti-info-circle" aria-hidden="true"></i> Commission: <?= esc($rule) ?>.</p>

<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Collections to remit</h2>
        <p class="cs-panel__note">Your verified cash collections that are not in a remittance yet. Tick the ones you are handing over.</p>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($available)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-cash-off', 'title' => 'Nothing to remit', 'text' => 'Cash you collect and that is verified appears here.']) ?>
        <?php else: ?>
            <form method="post" action="<?= site_url('collector/remit') ?>" id="remitForm" data-cs-once>
                <?= csrf_field() ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th style="width:2.5rem"><input type="checkbox" id="selAll" class="form-check-input" aria-label="Select all collections"></th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Receipt</th>
                                <th class="cs-num">Collected</th>
                                <th class="cs-num">Plan</th>
                                <th class="cs-num">Package</th>
                                <th class="cs-num">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available as $a): ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input rem-pick" name="payment_ids[]" value="<?= (int) $a['payment_id'] ?>" data-amount="<?= esc((string) $a['amount'], 'attr') ?>" data-base="<?= esc((string) $a['commission_base'], 'attr') ?>" aria-label="Remit collection <?= (int) $a['payment_id'] ?>"></td>
                                    <td class="text-nowrap"><?= cs_date($a['payment_date']) ?></td>
                                    <td><?= esc((string) $a['client_name']) ?><div class="cs-table__sub"><?= esc((string) ($a['unique_identifier'] ?: '-')) ?></div></td>
                                    <td><?= esc((string) ($a['official_receipt_number'] ?: '-')) ?></td>
                                    <td class="cs-num"><?= cs_money($a['amount']) ?></td>
                                    <td class="cs-num"><?= cs_money($a['plan_part']) ?></td>
                                    <td class="cs-num"><?= (float) $a['package_part'] > 0 ? cs_money($a['package_part']) : '-' ?></td>
                                    <td class="cs-num"><?= cs_money($a['commission']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    <div class="row g-3 align-items-end">
                        <div class="col-6 col-md-3"><small class="cs-muted d-block">Gross collected</small><strong id="tGross">₱0.00</strong></div>
                        <div class="col-6 col-md-3"><small class="cs-muted d-block">Your commission (<?= esc(rtrim(rtrim(number_format($rate * 100, 2), '0'), '.')) ?>%)</small><strong id="tComm" class="cs-money--brass">₱0.00</strong></div>
                        <div class="col-12 col-md-3"><small class="cs-muted d-block">Net to hand over</small><strong id="tNet" class="fs-5">₱0.00</strong></div>
                        <div class="col-12 col-md-3"><label class="form-label small" for="remNote">Note (optional)</label><input id="remNote" type="text" name="note" maxlength="255" class="form-control form-control-sm"></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3" id="remBtn" disabled>Submit remittance</button>
                    <span class="cs-muted small ms-2">The amounts are worked out again by the system when you submit.</span>
                </div>
            </form>
            <script>
            (function () {
                var rate = <?= json_encode((float) $rate) ?>;
                var picks = Array.prototype.slice.call(document.querySelectorAll('.rem-pick'));
                var money = function (n) { return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
                function update() {
                    var g = 0, b = 0, n = 0;
                    picks.forEach(function (p) { if (p.checked) { g += parseFloat(p.dataset.amount); b += parseFloat(p.dataset.base); n++; } });
                    var c = Math.round(b * rate * 100) / 100;
                    document.getElementById('tGross').textContent = money(g);
                    document.getElementById('tComm').textContent = money(c);
                    document.getElementById('tNet').textContent = money(Math.round((g - c) * 100) / 100);
                    document.getElementById('remBtn').disabled = n === 0;
                }
                picks.forEach(function (p) { p.addEventListener('change', update); });
                document.getElementById('selAll').addEventListener('change', function (e) { picks.forEach(function (p) { p.checked = e.target.checked; }); update(); });
                update();
            })();
            </script>
        <?php endif; ?>
    </div>
</section>

<section class="cs-panel">
    <div class="cs-panel__head"><h2 class="cs-panel__title">My remittances</h2></div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($history)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-receipt', 'title' => 'No remittances yet']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>#</th><th>Submitted</th><th class="cs-num">Collected</th><th class="cs-num">Commission</th><th class="cs-num">Net</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><a href="<?= site_url('collector/remit/' . (int) $h['remittance_id']) ?>">#<?= (int) $h['remittance_id'] ?></a></td>
                                <td class="text-nowrap"><?= cs_date($h['submitted_at'], true) ?></td>
                                <td class="cs-num"><?= cs_money($h['gross_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($h['commission_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($h['net_amount']) ?></td>
                                <td><span class="badge <?= $statusTone[$h['status']] ?? 'text-bg-secondary' ?>"><?= esc((string) $h['status_label']) ?></span></td>
                                <td class="text-end">
                                    <?php if ($h['status'] === 'submitted'): ?>
                                        <form method="post" action="<?= site_url('collector/remit/cancel/' . (int) $h['remittance_id']) ?>" class="d-inline" data-cs-confirm="Cancel remittance #<?= (int) $h['remittance_id'] ?>? Its collections go back to 'ready to remit'." data-cs-confirm-title="Cancel remittance" data-cs-confirm-ok="Cancel remittance" data-cs-once>
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-ghost">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
