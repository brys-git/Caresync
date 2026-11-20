<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$prev = date('Y-m', strtotime($ym . '-01 -1 month'));
$next = date('Y-m', strtotime($ym . '-01 +1 month'));
$colors = ['wake_start' => 'text-bg-info', 'wake_end' => 'text-bg-secondary', 'burial_at' => 'text-bg-warning', 'pickup_at' => 'text-bg-light border'];
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/ongoing') ?>">Service board</a></li>
    <li class="nav-item"><a class="nav-link active" href="<?= site_url('branch-admin/service-package/calendar') ?>">Wakes and burials</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/requests') ?>">Requests</a></li>
</ul>
<div class="d-flex align-items-center gap-2 mb-3">
    <a class="btn btn-sm btn-ghost" href="<?= site_url('branch-admin/service-package/calendar?month=' . $prev) ?>"><i class="ti ti-chevron-left"></i></a>
    <strong><?= esc(date('F Y', strtotime($ym . '-01'))) ?></strong>
    <a class="btn btn-sm btn-ghost" href="<?= site_url('branch-admin/service-package/calendar?month=' . $next) ?>"><i class="ti ti-chevron-right"></i></a>
</div>
<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($events === []): ?>
            <?= view('components/empty_state', ['icon' => 'ti-calendar-off', 'title' => 'No wakes or burials this month']) ?>
        <?php else: ?>
            <?php foreach ($events as $date => $list): ?>
                <div class="p-3 border-bottom">
                    <strong><?= esc(date('l, F j', strtotime($date))) ?></strong>
                    <?php foreach ($list as $e): ?>
                        <div class="d-flex gap-2 align-items-baseline mt-1">
                            <span class="badge <?= $colors[$e['type']] ?? 'text-bg-secondary' ?>"><?= esc($e['label']) ?></span>
                            <span><?= esc(date('g:ia', strtotime($e['at']))) ?></span>
                            <a href="<?= site_url('branch-admin/service-package/services/' . (int) $e['service_id'] . '/plan') ?>"><?= esc($e['who']) ?></a>
                            <span class="cs-muted"><?= esc($e['place']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
