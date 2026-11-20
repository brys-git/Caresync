<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$att = $attention ?? ['overdue' => 0, 'problems' => 0, 'ready' => 0];
$tone = ['pending' => 'text-bg-secondary', 'scheduled' => 'text-bg-info', 'preparing' => 'text-bg-info', 'ongoing' => 'text-bg-primary', 'burial_day' => 'text-bg-warning', 'completed' => 'text-bg-success', 'closed' => 'text-bg-dark'];
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" href="<?= site_url('branch-admin/service-package/ongoing') ?>">Service board</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/calendar') ?>">Wakes and burials</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/requests') ?>">Requests</a></li>
</ul>

<?php if ($att['overdue'] + $att['problems'] + $att['ready'] > 0): ?>
    <div class="cs-panel mb-3"><div class="cs-panel__body d-flex flex-wrap gap-3">
        <?php if ($att['overdue'] > 0): ?><span class="cs-status cs-status--stop"><?= (int) $att['overdue'] ?> overdue task<?= $att['overdue'] === 1 ? '' : 's' ?></span><?php endif; ?>
        <?php if ($att['problems'] > 0): ?><span class="cs-status cs-status--stop"><?= (int) $att['problems'] ?> reported problem<?= $att['problems'] === 1 ? '' : 's' ?></span><?php endif; ?>
        <?php if ($att['ready'] > 0): ?><span class="cs-status cs-status--ok"><?= (int) $att['ready'] ?> ready to complete</span><?php endif; ?>
    </div></div>
<?php endif; ?>

<div class="row g-3 flex-nowrap overflow-auto pb-2" id="serviceBoard">
    <?php foreach ($columns as $stage => $cards): ?>
        <div class="col-10 col-md-4 col-xl-3" data-stage="<?= esc($stage, 'attr') ?>">
            <section class="cs-panel h-100">
                <div class="cs-panel__head"><h2 class="cs-panel__title"><?= esc(\App\Services\ServiceProcessService::stageLabel($stage)) ?> <span class="badge <?= $tone[$stage] ?? 'text-bg-secondary' ?>"><?= count($cards) ?></span></h2></div>
                <div class="cs-panel__body">
                    <?php if ($cards === []): ?><p class="cs-muted small mb-0">Nothing here.</p><?php endif; ?>
                    <?php foreach ($cards as $c): ?>
                        <a class="d-block border rounded p-2 mb-2 text-decoration-none <?= $c['tasks_overdue'] > 0 || $c['tasks_problem'] > 0 ? 'border-danger' : '' ?>" href="<?= site_url('branch-admin/service-package/services/' . (int) $c['service_id'] . '/plan') ?>" data-service="<?= (int) $c['service_id'] ?>">
                            <strong><?= esc((string) ($c['deceased_name'] ?: $c['client_name'])) ?></strong>
                            <div class="cs-table__sub"><?= esc($c['what']) ?> · <?= esc($c['client_name']) ?></div>
                            <?php if (! empty($c['plan']['burial_at'])): ?><div class="small">Burial <?= esc(date('M j, g:ia', strtotime((string) $c['plan']['burial_at']))) ?></div><?php endif; ?>
                            <div class="small mt-1">Tasks <?= (int) $c['tasks_done'] ?>/<?= (int) $c['tasks_total'] ?>
                                <?php if ($c['tasks_overdue'] > 0): ?><span class="text-danger fw-bold"> · <?= (int) $c['tasks_overdue'] ?> overdue</span><?php endif; ?>
                                <?php if ($c['tasks_problem'] > 0): ?><span class="text-danger fw-bold"> · <?= (int) $c['tasks_problem'] ?> problem</span><?php endif; ?>
                                <?php if ($c['ready']): ?><span class="text-success fw-bold"> · ready to complete</span><?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
