<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$tabs = ['today' => 'Today', 'upcoming' => 'Upcoming', 'done' => 'Done'];
$tone = ['todo' => 'text-bg-secondary', 'in_progress' => 'text-bg-primary', 'done' => 'text-bg-success', 'problem' => 'text-bg-danger'];
$list = $groups[$tab];
?>
<ul class="nav nav-tabs mb-3">
    <?php foreach ($tabs as $k => $label): ?>
        <li class="nav-item"><a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="<?= site_url('staff/my-tasks?tab=' . $k) ?>"><?= esc($label) ?> <span class="badge text-bg-light border"><?= count($groups[$k]) ?></span></a></li>
    <?php endforeach; ?>
</ul>

<?php if ($list === []): ?>
    <?= view('components/empty_state', ['icon' => 'ti-checklist', 'title' => $tab === 'done' ? 'Nothing finished yet' : 'No tasks here', 'text' => $tab === 'today' ? 'Tasks that are due today, overdue or in progress show here.' : '']) ?>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($list as $t): ?>
        <div class="col-lg-6">
            <section class="cs-panel h-100 <?= $t['overdue'] ? 'border border-danger' : '' ?>" id="task-<?= (int) $t['task_id'] ?>">
                <div class="cs-panel__head" style="flex-wrap:wrap;gap:.5rem">
                    <div>
                        <h2 class="cs-panel__title"><?= esc((string) $t['name']) ?></h2>
                        <p class="cs-panel__note">For <strong><?= esc((string) ($t['deceased_name'] ?: $t['client_name'])) ?></strong></p>
                    </div>
                    <div>
                        <span class="badge <?= $tone[$t['status']] ?? 'text-bg-secondary' ?>"><?= esc($t['status_label']) ?></span>
                        <?php if ($t['overdue']): ?><span class="badge text-bg-danger">Overdue</span><?php endif; ?>
                    </div>
                </div>
                <div class="cs-panel__body">
                    <?php if ($t['due_at']): ?><p class="mb-1"><i class="ti ti-clock" aria-hidden="true"></i> Due <strong><?= esc(date('D, M j, g:ia', strtotime((string) $t['due_at']))) ?></strong></p><?php endif; ?>
                    <?php if (! empty($t['instructions'])): ?><p class="mb-2"><?= esc((string) $t['instructions']) ?></p><?php endif; ?>
                    <p class="mb-1"><i class="ti ti-user" aria-hidden="true"></i> Family: <?= esc((string) ($t['family_name'] ?: '-')) ?><?= $t['family_relationship'] ? ' (' . esc((string) $t['family_relationship']) . ')' : '' ?>
                        <?php if ($t['tel'] !== ''): ?> · <a href="tel:<?= esc($t['tel'], 'attr') ?>"><i class="ti ti-phone" aria-hidden="true"></i> <?= esc((string) $t['family_mobile']) ?></a><?php endif; ?></p>
                    <?php if (! empty($t['wake_address'])): ?>
                        <p class="mb-1"><i class="ti ti-map-pin" aria-hidden="true"></i> <?= esc((string) $t['wake_address']) ?> · <a href="<?= esc($t['maps_url'], 'attr') ?>" target="_blank" rel="noopener">Open in Google Maps</a></p>
                    <?php endif; ?>
                    <?php if (! empty($t['wake_start'])): ?><p class="mb-1 small cs-muted">Wake from <?= esc(date('M j, g:ia', strtotime((string) $t['wake_start']))) ?><?= ! empty($t['burial_at']) ? ' · burial ' . esc(date('M j, g:ia', strtotime((string) $t['burial_at']))) : '' ?></p><?php endif; ?>
                    <?php if ($t['status'] === 'problem'): ?><div class="alert alert-danger py-2 my-2">You reported: <?= esc((string) $t['problem_reason']) ?> - your Branch Admin was told.</div><?php endif; ?>
                    <?php if ($t['status'] === 'done'): ?>
                        <p class="mb-0 text-success">Done <?= esc(date('M j, g:ia', strtotime((string) $t['done_at']))) ?><?= $t['note'] ? ' · ' . esc((string) $t['note']) : '' ?>
                            <?php if (! empty($t['document_path'])): ?> · <a href="<?= site_url('service-files/task/' . (int) $t['task_id']) ?>" target="_blank" rel="noopener">your upload</a><?php endif; ?></p>
                    <?php elseif ($t['can_act']): ?>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php if ($t['status'] === 'todo'): ?>
                                <form method="post" action="<?= site_url('staff/my-tasks/' . (int) $t['task_id'] . '/start') ?>" data-cs-once><?= csrf_field() ?><button type="submit" class="btn btn-primary btn-sm">Start</button></form>
                            <?php endif; ?>
                        </div>
                        <?php if (in_array($t['status'], ['todo', 'in_progress'], true)): ?>
                            <form method="post" action="<?= site_url('staff/my-tasks/' . (int) $t['task_id'] . '/done') ?>" enctype="multipart/form-data" class="mt-3 p-2 border rounded" data-cs-once>
                                <?= csrf_field() ?>
                                <label class="form-label small mb-1" for="note<?= (int) $t['task_id'] ?>">Note (optional)</label>
                                <input class="form-control form-control-sm mb-2" id="note<?= (int) $t['task_id'] ?>" name="note" maxlength="1000">
                                <label class="form-label small mb-1" for="doc<?= (int) $t['task_id'] ?>"><?= (int) $t['requires_document'] === 1 ? 'Document or photo (required)' : 'Photo or document (optional)' ?></label>
                                <input class="form-control form-control-sm mb-2" type="file" id="doc<?= (int) $t['task_id'] ?>" name="document" accept="image/jpeg,image/png,image/webp,application/pdf" <?= (int) $t['requires_document'] === 1 ? 'required' : '' ?>>
                                <button type="submit" class="btn btn-success btn-sm">Done</button>
                            </form>
                            <form method="post" action="<?= site_url('staff/my-tasks/' . (int) $t['task_id'] . '/problem') ?>" class="mt-2 p-2 border rounded" data-cs-once>
                                <?= csrf_field() ?>
                                <label class="form-label small mb-1" for="prob<?= (int) $t['task_id'] ?>">Report a problem</label>
                                <input class="form-control form-control-sm mb-2" id="prob<?= (int) $t['task_id'] ?>" name="reason" maxlength="500" placeholder="What went wrong?" required>
                                <button type="submit" class="btn btn-outline-danger btn-sm">Report a problem</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
