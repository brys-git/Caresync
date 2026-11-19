<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$tone = ['submitted' => 'text-bg-warning', 'confirmed' => 'text-bg-success', 'discrepancy' => 'text-bg-danger', 'cancelled' => 'text-bg-secondary'];
$statuses = ['' => 'All', 'submitted' => 'Waiting for confirmation', 'discrepancy' => 'Discrepancy', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
?>
<?php if (session()->getFlashdata('success')): ?><?= view('components/alert', ['type' => 'success', 'message' => session()->getFlashdata('success')]) ?><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><?= view('components/alert', ['type' => 'danger', 'message' => session()->getFlashdata('error')]) ?><?php endif; ?>

<p class="cs-muted mb-3"><i class="ti ti-info-circle" aria-hidden="true"></i> Commission rule: <?= esc($rule) ?>. <?= (int) $waiting > 0 ? '<strong>' . (int) $waiting . ' remittance(s) need your decision.</strong>' : 'Nothing is waiting for you.' ?>
    <a class="ms-2" href="<?= site_url('branch-admin/reports/commission') ?>">Commission report (Print / Excel / PDF)</a></p>

<form class="cs-filters mb-3" method="get" action="<?= site_url('branch-admin/remittances') ?>">
    <div class="cs-filters__field"><label class="form-label" for="f_status">Status</label>
        <select id="f_status" name="status" class="form-select"><?php foreach ($statuses as $k => $label): ?><option value="<?= esc($k, 'attr') ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div>
    <div class="cs-filters__field"><label class="form-label" for="f_col">Collector</label>
        <select id="f_col" name="collector_id" class="form-select"><option value="0">All</option><?php foreach ($collectors as $c): ?><option value="<?= (int) $c['user_id'] ?>" <?= (int) ($filters['collector_id'] ?? 0) === (int) $c['user_id'] ? 'selected' : '' ?>><?= esc(trim($c['first_name'] . ' ' . $c['last_name'])) ?></option><?php endforeach; ?></select></div>
    <div class="cs-filters__field"><label class="form-label" for="f_from">Submitted from</label><input id="f_from" type="date" name="date_from" class="form-control" value="<?= esc((string) ($filters['from'] ?? ''), 'attr') ?>"></div>
    <div class="cs-filters__field"><label class="form-label" for="f_to">Submitted to</label><input id="f_to" type="date" name="date_to" class="form-control" value="<?= esc((string) ($filters['to'] ?? ''), 'attr') ?>"></div>
    <div class="cs-filters__actions"><button type="submit" class="btn btn-primary">Apply</button></div>
</form>

<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-receipt-off', 'title' => 'No remittances match']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>#</th><th>Collector</th><th>Submitted</th><th class="cs-num">Collected</th><th class="cs-num">Commission</th><th class="cs-num">To receive</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>#<?= (int) $r['remittance_id'] ?></td>
                                <td><?= esc((string) $r['collector_name']) ?></td>
                                <td class="text-nowrap"><?= cs_date($r['submitted_at'], true) ?></td>
                                <td class="cs-num"><?= cs_money($r['gross_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['commission_amount']) ?></td>
                                <td class="cs-num"><?= cs_money($r['net_amount']) ?></td>
                                <td><span class="badge <?= $tone[$r['status']] ?? 'text-bg-secondary' ?>"><?= esc((string) $r['status_label']) ?></span></td>
                                <td class="text-end"><a class="btn btn-sm <?= in_array($r['status'], ['submitted', 'discrepancy'], true) ? 'btn-primary' : 'btn-ghost' ?>" href="<?= site_url('branch-admin/remittances/' . (int) $r['remittance_id']) ?>"><?= in_array($r['status'], ['submitted', 'discrepancy'], true) ? 'Review' : 'View' ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
