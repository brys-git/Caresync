<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$sm = $summary ?? [];
$statusTone = ['submitted' => 'text-bg-warning', 'confirmed' => 'text-bg-success', 'discrepancy' => 'text-bg-danger', 'cancelled' => 'text-bg-secondary'];
$pg = $progress;
$c = $pg['counts'];
$stateTone = ['collected' => 'text-bg-info', 'remitted' => 'text-bg-warning', 'confirmed' => 'text-bg-success', 'not_collected' => 'text-bg-secondary', 'remaining' => 'text-bg-light border'];
$stateLabel = ['collected' => 'Collected', 'remitted' => 'Remitted', 'confirmed' => 'Confirmed', 'not_collected' => 'Not collected', 'remaining' => 'Remaining'];
?>
<?= view('components/stat_rail', ['stats' => [
    ['label' => 'Ready to remit', 'icon' => 'ti-cash', 'value' => $sm['available_gross'] ?? 0, 'money' => true, 'meta' => number_format((float) ($sm['available_count'] ?? 0)) . ' collection(s), ₱' . number_format((float) ($sm['available_commission'] ?? 0), 2) . ' commission when remitted'],
    ['label' => 'Commission earned', 'icon' => 'ti-coin', 'value' => $sm['earned'] ?? 0, 'money' => true, 'tone' => 'ok', 'meta' => 'on confirmed remittances'],
    ['label' => 'Waiting for confirmation', 'icon' => 'ti-hourglass', 'value' => $sm['pending'] ?? 0, 'money' => true, 'tone' => 'warn'],
    ['label' => 'On hold (discrepancy)', 'icon' => 'ti-alert-triangle', 'value' => $sm['on_hold'] ?? 0, 'money' => true, 'tone' => ((float) ($sm['on_hold'] ?? 0)) > 0 ? 'stop' : ''],
]]) ?>

<p class="cs-muted mb-3"><i class="ti ti-info-circle" aria-hidden="true"></i> Commission: <?= esc($rule) ?>.</p>

<?php if (! empty($overdue_items)): ?>
    <?= view('components/alert', ['type' => 'danger', 'message' => count($overdue_items) . ' collected payment(s) have waited more than ' . (int) $max_days . ' days to be remitted. Please remit them now.']) ?>
<?php endif; ?>
<?php foreach (($unclosed ?? []) as $u): ?>
    <?= view('components/alert', ['type' => 'warning', 'message' => 'You have not closed ' . $u['month_label'] . ' yet. Account for every plan holder below, remit everything and close the month.']) ?>
<?php endforeach; ?>

<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Collections to remit</h2>
        <p class="cs-panel__note">Everything you collected (cash, and GCash you checked on your own GCash account) that is not in a remittance yet. You can remit as often as you like, even a few collections at a time; they are all ticked to start.</p>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($available)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-cash-off', 'title' => 'Nothing to remit', 'text' => 'Payments you collect appear here until you remit them.']) ?>
        <?php else: ?>
            <form method="post" action="<?= site_url('collector/remit') ?>" id="remitForm" data-cs-once>
                <?= csrf_field() ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th style="width:2.5rem"><input type="checkbox" id="selAll" class="form-check-input" checked aria-label="Select all collections"></th>
                                <th>Collected</th>
                                <th>Client</th>
                                <th>Receipt / reference</th>
                                <th>Method</th>
                                <th class="cs-num">Collected</th>
                                <th class="cs-num">Plan</th>
                                <th class="cs-num">Package</th>
                                <th class="cs-num">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available as $a): ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input rem-pick" name="payment_ids[]" value="<?= (int) $a['payment_id'] ?>" checked data-method="<?= esc((string) $a['payment_method'], 'attr') ?>" data-amount="<?= esc((string) $a['amount'], 'attr') ?>" data-base="<?= esc((string) $a['commission_base'], 'attr') ?>" aria-label="Remit collection <?= (int) $a['payment_id'] ?>"></td>
                                    <td class="text-nowrap"><?= cs_date($a['payment_date']) ?></td>
                                    <td><?= esc((string) $a['client_name']) ?><div class="cs-table__sub"><?= esc((string) ($a['unique_identifier'] ?: '-')) ?></div></td>
                                    <td><?= esc((string) (($a['official_receipt_number'] ?? '') !== '' ? $a['official_receipt_number'] : ($a['reference_number'] ?? '-'))) ?></td>
                                    <td><?= esc(strtoupper((string) $a['payment_method'])) ?></td>
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
                        <div class="col-6 col-md-2"><small class="cs-muted d-block">Cash</small><strong id="tCash">₱0.00</strong></div>
                        <div class="col-6 col-md-2"><small class="cs-muted d-block">GCash</small><strong id="tGcash">₱0.00</strong></div>
                        <div class="col-6 col-md-2"><small class="cs-muted d-block">Gross collected</small><strong id="tGross">₱0.00</strong></div>
                        <div class="col-6 col-md-2"><small class="cs-muted d-block">Your commission (<?= esc(rtrim(rtrim(number_format($rate * 100, 2), '0'), '.')) ?>%)</small><strong id="tComm" class="cs-money--brass">₱0.00</strong></div>
                        <div class="col-6 col-md-2"><small class="cs-muted d-block">Net to hand over</small><strong id="tNet" class="fs-5">₱0.00</strong></div>
                        <div class="col-12 col-md-2"><label class="form-label small" for="remNote">Note (optional)</label><input id="remNote" type="text" name="note" maxlength="255" class="form-control form-control-sm"></div>
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
                    var g = 0, b = 0, n = 0, cash = 0, gc = 0;
                    picks.forEach(function (p) {
                        if (!p.checked) { return; }
                        var a = parseFloat(p.dataset.amount);
                        g += a; b += parseFloat(p.dataset.base); n++;
                        if (p.dataset.method === 'gcash') { gc += a; } else { cash += a; }
                    });
                    var c = Math.round(b * rate * 100) / 100;
                    document.getElementById('tCash').textContent = money(cash);
                    document.getElementById('tGcash').textContent = money(gc);
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

<section class="cs-panel mb-3" id="month">
    <div class="cs-panel__head" style="flex-wrap:wrap;gap:.5rem">
        <div>
            <h2 class="cs-panel__title">My month: <?= esc($pg['month_label']) ?> <?php if ($pg['closed'] !== null): ?><span class="badge text-bg-success">Closed</span><?php endif; ?></h2>
            <p class="cs-panel__note" id="monthSummary"><strong><?= esc($pg['summary_text']) ?></strong> (<?= (int) $c['collected_only'] ?> not remitted yet, <?= (int) $c['remitted'] ?> waiting for confirmation, <?= (int) $c['confirmed'] ?> confirmed)</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (! empty($prev_month_open) && $pg['month'] !== $prev_month): ?>
                <a class="btn btn-sm btn-ghost" href="<?= site_url('collector/remit?month=' . $prev_month) ?>#month">Previous month (not closed)</a>
            <?php endif; ?>
            <?php if ($pg['month'] !== date('Y-m')): ?>
                <a class="btn btn-sm btn-ghost" href="<?= site_url('collector/remit') ?>#month">This month</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($pg['members'] === []): ?>
            <?= view('components/empty_state', ['icon' => 'ti-users-off', 'title' => 'No plan holders assigned', 'text' => 'Ask your Branch Admin to assign you an area (Collector assignments).']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>Plan holder</th><th>Barangay</th><th>Status</th><th class="cs-num">Collected</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($pg['members'] as $m): ?>
                            <tr>
                                <td><?= esc($m['name']) ?><div class="cs-table__sub"><?= esc($m['unique_identifier'] ?: '-') ?></div></td>
                                <td><?= esc($m['barangay'] ?: '-') ?></td>
                                <td><span class="badge <?= $stateTone[$m['state']] ?>"><?= esc($stateLabel[$m['state']]) ?></span><?= $m['reason'] ? '<div class="cs-table__sub">' . esc($m['reason']) . '</div>' : '' ?></td>
                                <td class="cs-num"><?= $m['amount'] > 0 ? cs_money($m['amount']) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($pg['closed'] === null && $m['state'] === 'remaining'): ?>
                                        <form method="post" action="<?= site_url('collector/remit/not-collected') ?>" class="d-flex gap-1 justify-content-end flex-wrap" data-cs-once>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="plan_holder_id" value="<?= (int) $m['plan_holder_id'] ?>">
                                            <input type="hidden" name="month" value="<?= esc($pg['month'], 'attr') ?>">
                                            <select name="reason_code" class="form-select form-select-sm w-auto" aria-label="Reason not collected" required>
                                                <option value="">Not collected: why?</option>
                                                <?php foreach ($reasons as $code => $label): ?><option value="<?= esc($code, 'attr') ?>"><?= esc($label) ?></option><?php endforeach; ?>
                                            </select>
                                            <input type="text" name="reason_note" maxlength="255" class="form-control form-control-sm w-auto" placeholder="Note (needed for Other)" aria-label="Note">
                                            <button type="submit" class="btn btn-sm btn-ghost">Mark not collected</button>
                                        </form>
                                    <?php elseif ($pg['closed'] === null && $m['state'] === 'not_collected'): ?>
                                        <form method="post" action="<?= site_url('collector/remit/not-collected/undo') ?>" class="d-inline" data-cs-once>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="plan_holder_id" value="<?= (int) $m['plan_holder_id'] ?>">
                                            <input type="hidden" name="month" value="<?= esc($pg['month'], 'attr') ?>">
                                            <button type="submit" class="btn btn-sm btn-ghost">Undo</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <div class="p-3 border-top">
            <?php if ($pg['closed'] !== null): ?>
                <p class="mb-0 text-success">Closed on <?= cs_date($pg['closed']['closed_at'], true) ?>: <?= cs_peso($pg['closed']['gross_amount']) ?> collected, <?= cs_peso($pg['closed']['commission_amount']) ?> commission, <?= cs_peso($pg['closed']['net_amount']) ?> remitted.</p>
            <?php else: ?>
                <form method="post" action="<?= site_url('collector/remit/close-month') ?>" data-cs-confirm="Close <?= esc($pg['month_label'], 'attr') ?>? You will not be able to change this month afterwards." data-cs-confirm-title="Close month" data-cs-confirm-ok="Close <?= esc($pg['month_label'], 'attr') ?>" data-cs-once>
                    <?= csrf_field() ?>
                    <input type="hidden" name="month" value="<?= esc($pg['month'], 'attr') ?>">
                    <button type="submit" class="btn btn-primary" <?= $pg['can_close'] ? '' : 'disabled' ?>>Close <?= esc($pg['month_label']) ?></button>
                    <?php if (! $pg['can_close']): ?>
                        <ul class="cs-muted small mb-0 mt-2"><?php foreach ($pg['blockers'] as $b): ?><li><?= esc($b) ?></li><?php endforeach; ?></ul>
                    <?php else: ?>
                        <span class="cs-muted small ms-2">Everyone is accounted for and every collection is confirmed.</span>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
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
                    <thead><tr><th>#</th><th>Submitted</th><th class="cs-num">Cash</th><th class="cs-num">GCash</th><th class="cs-num">Collected</th><th class="cs-num">Commission</th><th class="cs-num">Net</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><a href="<?= site_url('collector/remit/' . (int) $h['remittance_id']) ?>">#<?= (int) $h['remittance_id'] ?></a></td>
                                <td class="text-nowrap"><?= cs_date($h['submitted_at'], true) ?></td>
                                <td class="cs-num"><?= cs_money($h['cash_amount'] ?? $h['gross_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($h['gcash_amount'] ?? 0) ?></td>
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
