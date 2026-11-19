<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
// Shared by System Admin (branch picker) and Branch Admin (own branch only, enforced server-side).
$base = ($reports_base_path ?? '/admin/reports') . '/avails';
$query = (! empty($filters['branch_id']) ? '&branch_id=' . (int) $filters['branch_id'] : '')
    . (! empty($filters['search']) ? '&search=' . urlencode((string) $filters['search']) : '')
    . (! empty($filters['status']) ? '&status=' . urlencode((string) $filters['status']) : '')
    . (! empty($filters['date_from']) ? '&date_from=' . urlencode((string) $filters['date_from']) : '')
    . (! empty($filters['date_to']) ? '&date_to=' . urlencode((string) $filters['date_to']) : '');
$statuses = ['' => 'All', 'awaiting_down_payment' => 'Awaiting down payment', 'active' => 'Active', 'settled' => 'Settled', 'cancelled' => 'Cancelled', 'waived' => 'Down payment waived'];
$sum = ['price' => 0.0, 'paid' => 0.0, 'balance' => 0.0];
foreach ($rows as $r) {
    $sum['price'] += (float) $r['package_price'];
    $sum['paid'] += (float) $r['paid_so_far'];
    $sum['balance'] += (float) $r['balance'];
}
?>
<div class="mb-3 text-end">
    <a class="btn btn-ghost btn-sm" href="<?= site_url($base . '?mode=print' . $query) ?>" target="_blank" rel="noopener"><i class="ti ti-printer me-1"></i> Print</a>
    <a class="btn btn-ghost btn-sm" href="<?= site_url($base . '?mode=csv' . $query) ?>"><i class="ti ti-file-spreadsheet me-1"></i> Export Excel</a>
    <a class="btn btn-primary btn-sm" href="<?= site_url($base . '?mode=pdf' . $query) ?>"><i class="ti ti-file-type-pdf me-1"></i> Export PDF</a>
</div>

<?php $this->setData(['reports_base_path' => $reports_base_path ?? '/admin/reports']) ?>
<?= $this->include('partials/reports_nav') ?>

<form class="cs-filters mb-3" method="get" action="<?= site_url($base) ?>">
    <div class="cs-filters__field cs-filters__field--wide">
        <label class="form-label" for="av_search">Search</label>
        <input type="search" id="av_search" name="search" class="form-control" maxlength="100" value="<?= esc((string) ($filters['search'] ?? ''), 'attr') ?>" placeholder="Name, plan number or package">
    </div>
    <div class="cs-filters__field">
        <label class="form-label" for="av_status">Status</label>
        <select id="av_status" name="status" class="form-select">
            <?php foreach ($statuses as $k => $label): ?>
                <option value="<?= esc($k, 'attr') ?>" <?= (string) ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="cs-filters__field">
        <label class="form-label" for="av_from">Approved from</label>
        <input type="date" id="av_from" name="date_from" class="form-control" value="<?= esc((string) ($filters['date_from'] ?? ''), 'attr') ?>">
    </div>
    <div class="cs-filters__field">
        <label class="form-label" for="av_to">Approved to</label>
        <input type="date" id="av_to" name="date_to" class="form-control" value="<?= esc((string) ($filters['date_to'] ?? ''), 'attr') ?>">
    </div>
    <?php if (! empty($branches)): ?>
        <div class="cs-filters__field">
            <label class="form-label" for="av_branch">Branch</label>
            <select id="av_branch" class="form-select" name="branch_id">
                <option value="0">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= (int) $branch['branch_id'] ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) $branch['branch_id'] ? 'selected' : '' ?>><?= esc((string) $branch['branch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="cs-filters__actions">
        <button type="submit" class="btn btn-primary">Apply</button>
        <?php if ($query !== '' && $query !== (! empty($filters['branch_id']) ? '&branch_id=' . (int) $filters['branch_id'] : '')): ?>
            <a class="btn btn-ghost" href="<?= site_url($base) ?><?= ! empty($filters['branch_id']) && ! empty($branches) ? '?branch_id=' . (int) $filters['branch_id'] : '' ?>">Clear filters</a>
        <?php endif; ?>
    </div>
</form>

<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($rows)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-package-off', 'title' => 'No avails match these filters']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th>Plan Holder</th>
                            <th>Package / Service</th>
                            <th class="cs-num">Price</th>
                            <th style="min-width:230px">Down Payment</th>
                            <th>Term</th>
                            <th class="cs-num">Paid So Far</th>
                            <th class="cs-num">Balance</th>
                            <th>Status</th>
                            <th>Approved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <?= esc((string) $r['client_name']) ?>
                                    <div class="cs-table__sub"><?= esc((string) ($r['unique_identifier'] ?: '-')) ?><?= ! empty($branches) ? ' · ' . esc((string) ($r['branch_name'] ?? '')) : '' ?></div>
                                </td>
                                <td><?= esc((string) ($r['what'] ?? '-')) ?><div class="cs-table__sub"><?= esc((string) $r['kind_label']) ?></div></td>
                                <td class="cs-num"><?= cs_money($r['package_price']) ?></td>
                                <td>
                                    <?php if ($r['down_payment_status'] === 'waived'): ?>
                                        <strong>Waived</strong> <span class="cs-table__sub">(<?= cs_peso($r['down_payment_amount']) ?>)</span>
                                        <div class="cs-table__sub">By <?= esc($r['waived_by_name'] !== '' ? $r['waived_by_name'] : '-') ?><?= $r['waived_at'] ? ' on ' . esc(date('j M Y', strtotime((string) $r['waived_at']))) : '' ?></div>
                                        <div class="cs-table__sub">Reason: <?= esc((string) ($r['waiver_reason'] ?? '-')) ?></div>
                                    <?php else: ?>
                                        <?= esc((string) $r['down_label']) ?> <span class="cs-table__sub">(<?= cs_peso($r['down_payment_amount']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string) $r['term_label']) ?><?php if ((int) $r['term_months'] > 0): ?><div class="cs-table__sub"><?= cs_peso($r['monthly_instalment']) ?> / month</div><?php endif; ?></td>
                                <td class="cs-num"><?= cs_money($r['paid_so_far']) ?></td>
                                <td class="cs-num"><?= cs_money($r['balance']) ?></td>
                                <td><?= esc((string) $r['status_label']) ?></td>
                                <td class="text-nowrap"><?= $r['approved_at'] ? esc(date('j M Y', strtotime((string) $r['approved_at']))) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Total (<?= count($rows) ?> avail<?= count($rows) === 1 ? '' : 's' ?>)</th>
                            <th class="cs-num"><?= cs_money($sum['price']) ?></th>
                            <th colspan="2"></th>
                            <th class="cs-num"><?= cs_money($sum['paid']) ?></th>
                            <th class="cs-num"><?= cs_money($sum['balance']) ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
