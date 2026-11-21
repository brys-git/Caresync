<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php $svc = new \App\Services\PlanClosureService(); ?>
<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if (empty($requests)): ?>
            <?= view('components/empty_state', ['icon' => 'ti-circle-check', 'title' => 'No closure requests', 'text' => 'When a client asks to close their plan, it appears here.']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead>
                        <tr><th>Client</th><th>Asked on</th><th>Reason given</th><th>Close the plan</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('branch-admin/client-management/view/' . (int) $r['plan_holder_id']) ?>"><?= esc(trim((string) $r['first_name'] . ' ' . (string) $r['last_name'])) ?></a><br>
                                <small class="cs-muted"><?= esc((string) ($r['contact_number'] ?? '')) ?></small>
                            </td>
                            <td class="text-nowrap"><?= esc(date('M j, Y', strtotime((string) $r['created_at']))) ?></td>
                            <td><?= esc((string) ($r['reason'] ?: '—')) ?></td>
                            <td style="min-width:34rem"><?= view('partials/close_plan_form', ['plan_holder_id' => (int) $r['plan_holder_id'], 'reasons' => $reasons, 'back' => 'requests', 'can_close' => $svc->canClose((int) $r['plan_holder_id'])]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
