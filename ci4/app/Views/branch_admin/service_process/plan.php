<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$dtv = static fn ($v): string => empty($v) ? '' : date('Y-m-d\TH:i', strtotime((string) $v));
$p = $plan;
$total = count($tasks);
$done = count(array_filter($tasks, static fn ($t): bool => $t['status'] === 'done'));
$stageTone = ['pending' => 'text-bg-secondary', 'scheduled' => 'text-bg-info', 'preparing' => 'text-bg-info', 'ongoing' => 'text-bg-primary', 'burial_day' => 'text-bg-warning', 'completed' => 'text-bg-success', 'closed' => 'text-bg-dark', 'cancelled' => 'text-bg-danger'];
$taskTone = ['todo' => 'text-bg-secondary', 'in_progress' => 'text-bg-primary', 'done' => 'text-bg-success', 'problem' => 'text-bg-danger'];
$staffOptions = static function (array $staff, $selected): string {
    $h = '<option value="">Not assigned</option>';
    foreach ($staff as $m) {
        $h .= '<option value="' . (int) $m['user_id'] . '"' . ((int) $selected === (int) $m['user_id'] ? ' selected' : '') . '>' . esc($m['name']) . '</option>';
    }

    return $h;
};
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/ongoing') ?>">Service board</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/calendar') ?>">Wakes and burials</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= site_url('branch-admin/service-package/requests') ?>">Requests</a></li>
</ul>

<section class="cs-panel mb-3">
    <div class="cs-panel__head" style="flex-wrap:wrap;gap:.5rem">
        <div>
            <h2 class="cs-panel__title"><?= esc((string) ($s['deceased_name'] ?: $s['client_name'])) ?> <span class="badge <?= $stageTone[$s['status']] ?? 'text-bg-secondary' ?>" id="stageBadge"><?= esc($s['stage_label']) ?></span>
                <?php if ($ready): ?><span class="badge text-bg-success">Ready to complete</span><?php endif; ?></h2>
            <p class="cs-panel__note"><?= esc($s['what']) ?> · Plan holder <?= esc($s['client_name']) ?> (<?= esc((string) $s['unique_identifier']) ?>) · Tasks <?= $done ?>/<?= $total ?></p>
        </div>
        <?php if ($is_open): ?>
            <form method="post" action="<?= site_url('branch-admin/service-package/services/' . (int) $s['service_id'] . '/cancel') ?>" data-cs-confirm="Cancel this service?" data-cs-confirm-title="Cancel service" data-cs-confirm-ok="Cancel service" data-cs-once>
                <?= csrf_field() ?><button type="submit" class="btn btn-sm btn-ghost">Cancel service</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($is_open && ! $cleared && $gate_message !== ''): ?>
        <div class="cs-panel__body"><div class="alert alert-warning mb-0"><?= esc($gate_message) ?> Tasks cannot be started until then.</div></div>
    <?php endif; ?>
</section>

<?php if ($is_open): ?>
<form method="post" action="<?= site_url('branch-admin/service-package/services/' . (int) $s['service_id'] . '/plan') ?>" data-cs-once>
    <?= csrf_field() ?>
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <section class="cs-panel h-100">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Schedule</h2><p class="cs-panel__note">When all of wake, burial, cemetery and the family contact are filled in, the service becomes <em>Scheduled</em>.</p></div>
                <div class="cs-panel__body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="pickup_at">Pick-up date and time</label><input class="form-control" type="datetime-local" id="pickup_at" name="pickup_at" value="<?= esc($dtv($p['pickup_at'] ?? null), 'attr') ?>"></div>
                        <div class="col-md-8"><label class="form-label" for="pickup_place">Pick-up place</label><input class="form-control" id="pickup_place" name="pickup_place" maxlength="255" value="<?= esc((string) ($p['pickup_place'] ?? ''), 'attr') ?>" placeholder="Hospital, funeral home, home..."></div>
                        <div class="col-md-4"><label class="form-label" for="wake_start">Wake starts</label><input class="form-control" type="datetime-local" id="wake_start" name="wake_start" value="<?= esc($dtv($p['wake_start'] ?? null), 'attr') ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="wake_end">Wake ends</label><input class="form-control" type="datetime-local" id="wake_end" name="wake_end" value="<?= esc($dtv($p['wake_end'] ?? null), 'attr') ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="burial_at">Burial date and time</label><input class="form-control" type="datetime-local" id="burial_at" name="burial_at" value="<?= esc($dtv($p['burial_at'] ?? null), 'attr') ?>"></div>
                        <div class="col-md-8"><label class="form-label" for="wake_address">Wake address <?php if ($maps_url !== ''): ?><a class="small ms-2" href="<?= esc($maps_url, 'attr') ?>" target="_blank" rel="noopener">Open in Google Maps</a><?php endif; ?></label><input class="form-control" id="wake_address" name="wake_address" maxlength="255" value="<?= esc((string) ($p['wake_address'] ?? ''), 'attr') ?>"></div>
                        <div class="col-md-4"><label class="form-label" for="cemetery">Cemetery</label><input class="form-control" id="cemetery" name="cemetery" maxlength="255" value="<?= esc((string) ($p['cemetery'] ?? ''), 'attr') ?>"></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="cs-panel h-100">
                <div class="cs-panel__head"><h2 class="cs-panel__title">Family contact</h2></div>
                <div class="cs-panel__body">
                    <div class="mb-3"><label class="form-label" for="family_name">Name</label><input class="form-control" id="family_name" name="family_name" maxlength="150" value="<?= esc((string) ($p['family_name'] ?? ''), 'attr') ?>"></div>
                    <div class="mb-3"><label class="form-label" for="family_mobile">Mobile</label><input class="form-control" id="family_mobile" name="family_mobile" maxlength="30" value="<?= esc((string) ($p['family_mobile'] ?? ''), 'attr') ?>"></div>
                    <div class="mb-3"><label class="form-label" for="family_relationship">Relationship to the deceased</label><input class="form-control" id="family_relationship" name="family_relationship" maxlength="100" value="<?= esc((string) ($p['family_relationship'] ?? ''), 'attr') ?>"></div>
                    <button type="submit" class="btn btn-primary">Save plan</button>
                </div>
            </section>
        </div>
    </div>
</form>
<?php else: ?>
<section class="cs-panel mb-3"><div class="cs-panel__body">
    <div class="row g-3">
        <div class="col-md-4"><small class="cs-muted d-block">Wake</small><?= ! empty($p['wake_start']) ? esc(date('M j, g:ia', strtotime((string) $p['wake_start']))) : '-' ?> · <?= esc((string) ($p['wake_address'] ?? '')) ?></div>
        <div class="col-md-4"><small class="cs-muted d-block">Burial</small><?= ! empty($p['burial_at']) ? esc(date('M j, g:ia', strtotime((string) $p['burial_at']))) : '-' ?> · <?= esc((string) ($p['cemetery'] ?? '')) ?></div>
        <div class="col-md-4"><small class="cs-muted d-block">Family contact</small><?= esc((string) ($p['family_name'] ?? '-')) ?> <?= esc((string) ($p['family_mobile'] ?? '')) ?></div>
    </div>
</div></section>
<?php endif; ?>

<section class="cs-panel mb-3" id="tasks">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Tasks</h2><p class="cs-panel__note">Built from what the package includes. Give each task to a staff member of this branch with a due date and time.</p></div>
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($tasks === []): ?><p class="cs-muted p-3 mb-0">No tasks yet.</p><?php endif; ?>
        <?php foreach ($tasks as $t): ?>
            <div class="p-3 border-bottom" id="task-<?= (int) $t['task_id'] ?>">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span class="badge <?= $taskTone[$t['status']] ?? 'text-bg-secondary' ?>"><?= esc($t['status_label']) ?></span>
                    <?php if ($t['overdue']): ?><span class="badge text-bg-danger">Overdue</span><?php endif; ?>
                    <?php if ((int) $t['requires_document'] === 1): ?><span class="badge text-bg-light border">Needs a document</span><?php endif; ?>
                    <?php if (! empty($t['document_path'])): ?><a class="small" href="<?= site_url('service-files/task/' . (int) $t['task_id']) ?>" target="_blank" rel="noopener">View upload</a><?php endif; ?>
                    <?php if ($t['status'] === 'done'): ?><span class="cs-muted small">Done <?= esc(date('M j, g:ia', strtotime((string) $t['done_at']))) ?> by <?= esc($t['staff_name'] ?: '-') ?></span><?php endif; ?>
                </div>
                <?php if ($t['status'] === 'problem'): ?>
                    <div class="alert alert-danger py-2">Problem reported: <?= esc((string) $t['problem_reason']) ?>
                        <?php if ($is_open): ?>
                            <form method="post" action="<?= site_url('branch-admin/service-package/tasks/' . (int) $t['task_id'] . '/resolve') ?>" class="d-inline ms-2" data-cs-once><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-ghost">Back to "to do"</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (! empty($t['note'])): ?><p class="small mb-2"><strong>Note:</strong> <?= esc((string) $t['note']) ?></p><?php endif; ?>
                <?php if ($is_open): ?>
                    <form method="post" action="<?= site_url('branch-admin/service-package/tasks/' . (int) $t['task_id']) ?>" data-cs-once>
                        <?= csrf_field() ?>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3"><label class="form-label small" for="n<?= (int) $t['task_id'] ?>">Task</label><input class="form-control form-control-sm" id="n<?= (int) $t['task_id'] ?>" name="name" maxlength="150" value="<?= esc((string) $t['name'], 'attr') ?>" required></div>
                            <div class="col-md-3"><label class="form-label small" for="i<?= (int) $t['task_id'] ?>">Instructions</label><input class="form-control form-control-sm" id="i<?= (int) $t['task_id'] ?>" name="instructions" value="<?= esc((string) $t['instructions'], 'attr') ?>"></div>
                            <div class="col-md-2"><label class="form-label small" for="s<?= (int) $t['task_id'] ?>">Assigned to</label><select class="form-select form-select-sm" id="s<?= (int) $t['task_id'] ?>" name="assigned_staff_id"><?= $staffOptions($staff, $t['assigned_staff_id']) ?></select></div>
                            <div class="col-md-2"><label class="form-label small" for="d<?= (int) $t['task_id'] ?>">Due</label><input class="form-control form-control-sm" type="datetime-local" id="d<?= (int) $t['task_id'] ?>" name="due_at" value="<?= esc($dtv($t['due_at']), 'attr') ?>"></div>
                            <div class="col-md-2 d-flex gap-2 align-items-center">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="requires_document" value="1" id="r<?= (int) $t['task_id'] ?>" <?= (int) $t['requires_document'] === 1 ? 'checked' : '' ?>><label class="form-check-label small" for="r<?= (int) $t['task_id'] ?>">Document</label></div>
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                    <div class="d-flex gap-2 mt-2">
                        <form method="post" action="<?= site_url('branch-admin/service-package/tasks/' . (int) $t['task_id'] . '/move') ?>" data-cs-once><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button type="submit" class="btn btn-sm btn-ghost" aria-label="Move up"><i class="ti ti-arrow-up"></i></button></form>
                        <form method="post" action="<?= site_url('branch-admin/service-package/tasks/' . (int) $t['task_id'] . '/move') ?>" data-cs-once><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button type="submit" class="btn btn-sm btn-ghost" aria-label="Move down"><i class="ti ti-arrow-down"></i></button></form>
                        <?php if (! in_array($t['status'], ['done', 'in_progress'], true)): ?>
                            <form method="post" action="<?= site_url('branch-admin/service-package/tasks/' . (int) $t['task_id'] . '/remove') ?>" data-cs-confirm="Remove this task?" data-cs-confirm-title="Remove task" data-cs-confirm-ok="Remove" data-cs-once><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-ghost text-danger">Remove</button></form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div><strong><?= esc((string) $t['name']) ?></strong> <span class="cs-muted"><?= esc($t['staff_name'] ?: '') ?></span></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($is_open): ?>
            <form method="post" action="<?= site_url('branch-admin/service-package/services/' . (int) $s['service_id'] . '/tasks') ?>" class="p-3 bg-light" data-cs-once>
                <?= csrf_field() ?>
                <strong class="d-block mb-2">Add a task</strong>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3"><input class="form-control form-control-sm" name="name" maxlength="150" placeholder="Task" aria-label="Task name" required></div>
                    <div class="col-md-3"><input class="form-control form-control-sm" name="instructions" placeholder="Instructions" aria-label="Instructions"></div>
                    <div class="col-md-2"><select class="form-select form-select-sm" name="assigned_staff_id" aria-label="Assigned to"><?= $staffOptions($staff, 0) ?></select></div>
                    <div class="col-md-2"><input class="form-control form-control-sm" type="datetime-local" name="due_at" aria-label="Due"></div>
                    <div class="col-md-2 d-flex gap-2 align-items-center"><div class="form-check"><input class="form-check-input" type="checkbox" name="requires_document" value="1" id="newdoc"><label class="form-check-label small" for="newdoc">Document</label></div><button type="submit" class="btn btn-sm btn-primary">Add</button></div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if (in_array($s['status'], ['ongoing', 'burial_day'], true)): ?>
<section class="cs-panel mb-3" id="complete">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Complete the service</h2>
        <p class="cs-panel__note"><?= $ready ? 'Every task is done. Record the family\'s confirmation to complete the service.' : 'Every task must be done first (' . $done . '/' . $total . ').' ?></p></div>
    <div class="cs-panel__body">
        <form method="post" action="<?= site_url('branch-admin/service-package/services/' . (int) $s['service_id'] . '/complete') ?>" enctype="multipart/form-data" data-cs-once>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label" for="confirmed_name">Family member who confirmed</label><input class="form-control" id="confirmed_name" name="confirmed_name" maxlength="150" required></div>
                <div class="col-md-3"><label class="form-label" for="confirmed_relationship">Relationship</label><input class="form-control" id="confirmed_relationship" name="confirmed_relationship" maxlength="100" required></div>
                <div class="col-md-2"><label class="form-label" for="confirmed_on">Date</label><input class="form-control" type="date" id="confirmed_on" name="confirmed_on" value="<?= esc(date('Y-m-d'), 'attr') ?>" max="<?= esc(date('Y-m-d'), 'attr') ?>" required></div>
                <div class="col-md-3"><label class="form-label" for="confirmed_photo">Photo of the signed form (optional)</label><input class="form-control" type="file" id="confirmed_photo" name="confirmed_photo" accept="image/jpeg,image/png,image/webp"></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3" <?= $ready ? '' : 'disabled' ?>>Complete service</button>
        </form>
    </div>
</section>
<?php endif; ?>

<?php if (in_array($s['status'], ['completed', 'closed'], true)): ?>
<section class="cs-panel mb-3">
    <div class="cs-panel__head"><h2 class="cs-panel__title">Completed</h2></div>
    <div class="cs-panel__body">
        <p class="mb-2">Confirmed by <strong><?= esc((string) ($p['confirmed_name'] ?? '-')) ?></strong> (<?= esc((string) ($p['confirmed_relationship'] ?? '')) ?>) on <?= cs_date($p['confirmed_on'] ?? null) ?>.
            <?php if (! empty($p['confirmed_photo_path'])): ?><a href="<?= site_url('service-files/confirmation/' . (int) $s['service_id']) ?>" target="_blank" rel="noopener">View the signed form</a><?php endif; ?></p>
        <p class="mb-2">Family rating: <?= ! empty($p['rated_at']) ? '<strong>' . (int) $p['rating'] . '/5</strong>' . (! empty($p['feedback']) ? ' - ' . esc((string) $p['feedback']) : '') : '<span class="cs-muted">not rated yet</span>' ?></p>
        <?php if ($s['status'] === 'completed'): ?>
            <form method="post" action="<?= site_url('branch-admin/service-package/services/' . (int) $s['service_id'] . '/close') ?>" data-cs-once><?= csrf_field() ?><button type="submit" class="btn btn-primary">Close service</button></form>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?= $this->endSection() ?>
