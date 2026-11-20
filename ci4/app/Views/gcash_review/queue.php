<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($rows === []): ?>
            <?= view('components/empty_state', ['icon' => 'ti-brand-gcash', 'title' => 'Nothing to review', 'text' => 'GCash payments from clients appear here, oldest first.']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>Waiting</th><th>Client</th><th>Reference</th><th class="cs-num">Amount</th><th>Paid</th><th>Screenshot</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><span class="badge <?= $r['waiting_days'] >= 3 ? 'text-bg-danger' : ($r['waiting_days'] >= 1 ? 'text-bg-warning' : 'text-bg-light border') ?>"><?= $r['waiting_days'] === 0 ? 'today' : 'waiting ' . (int) $r['waiting_days'] . ' day' . ($r['waiting_days'] === 1 ? '' : 's') ?></span>
                                    <?php if ($r['waiting_for_client']): ?><div class="cs-table__sub">asked for a clearer screenshot</div><?php endif; ?></td>
                                <td><?= esc($r['client_name']) ?><div class="cs-table__sub"><?= esc((string) ($r['unique_identifier'] ?: '-')) ?></div></td>
                                <td class="text-nowrap"><?= esc($r['reference_grouped']) ?></td>
                                <td class="cs-num"><?= cs_money($r['amount']) ?></td>
                                <td class="text-nowrap"><?= ! empty($r['paid_at']) ? esc(date('M j, g:ia', strtotime((string) $r['paid_at']))) : cs_date($r['payment_date']) ?></td>
                                <td><?= $r['has_proof'] ? '<span class="text-success">yes</span>' : '<span class="text-danger">none</span>' ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-primary" href="<?= site_url('gcash-review/' . (int) $r['payment_id']) ?>">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
