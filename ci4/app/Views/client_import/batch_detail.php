<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-import.css') ?>">

<?php
    $base = ($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin' : 'branch-admin';
    $status = strtolower((string) ($batch['status'] ?? 'staged'));
    $badge = $status === 'committed' ? 'so-badge--green' : ($status === 'discarded' ? '' : 'so-badge--amber');
    $commitSuccess = $commit_success ?? [];
    $hasCommitSuccess = ! empty($commitSuccess['created']) || ! empty($commitSuccess['linked']);
?>

<div class="so ci">

    <div class="ci-link-row">
        <div style="flex:1;">
            <h1 class="so-header__title" style="font-size:1.35rem;">Batch detail</h1>
            <p class="so-header__sub">
                <strong><?= esc($batch['original_name'] ?? $batch['filename'] ?? 'Document') ?></strong>
                · <?= strtoupper(esc((string) ($batch['format'] ?? ''))) ?> ·
                uploaded <?= esc(date('M d, Y H:i', strtotime((string) ($batch['created_at'] ?? 'now')))) ?>
                by <?= esc($batch['uploader_name'] ?? '-') ?>
                <?= (($role_layout ?? '') === 'layouts/admin' && ! empty($batch['branch_name'])) ? '· ' . esc($batch['branch_name']) : '' ?>
            </p>
        </div>
        <span class="so-badge <?= $badge ?>"><?= esc(ucfirst($status)) ?></span>
        <?php if ($status === 'staged'): ?>
            <a class="so-btn so-btn--purple" href="<?= base_url($base . '/client-import/review/' . (int) $batch['import_batch_id']) ?>"><i class="mdi mdi-clipboard-edit-outline"></i> Review records</a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error"><i class="mdi mdi-alert-circle-outline"></i> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success"><i class="mdi mdi-check-circle-outline"></i> <?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if ($hasCommitSuccess): ?>
        <div class="so-alert so-alert--success">
            <strong><i class="mdi mdi-check-decagram-outline"></i> Import committed successfully</strong> —
            <?= (int) $commitSuccess['created'] ?> new account(s) created, <?= (int) $commitSuccess['linked'] ?> linked, <?= (int) $commitSuccess['skipped'] ?> skipped.
        </div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="ci-summary">
        <div class="ci-stat ci-stat--ready"><div class="ci-stat__label">Ready</div><div class="ci-stat__value"><?= (int) $counts['ready'] ?></div></div>
        <div class="ci-stat ci-stat--attention"><div class="ci-stat__label">Needs attention</div><div class="ci-stat__value"><?= (int) $counts['needs_attention'] ?></div></div>
        <div class="ci-stat ci-stat--duplicate"><div class="ci-stat__label">Duplicates</div><div class="ci-stat__value"><?= (int) $counts['duplicate'] ?></div></div>
        <div class="ci-stat ci-stat--skip"><div class="ci-stat__label">Skipped</div><div class="ci-stat__value"><?= (int) $counts['skip'] ?></div></div>
        <div class="ci-stat ci-stat--decided"><div class="ci-stat__label">Committed</div><div class="ci-stat__value"><?= (int) ($batch['committed_count'] ?? 0) ?></div></div>
    </div>

    <?php if ($status === 'committed' && ! empty($batch['committed_at'])): ?>
        <p style="font-size:.82rem;color:var(--so-ink-soft);">
            Committed on <?= esc(date('M d, Y H:i', strtotime((string) $batch['committed_at']))) ?> by <?= esc($batch['committer_name'] ?? '-') ?>.
            Every write was performed in one transaction — no partial commits.
        </p>
    <?php endif; ?>

    <!-- Records -->
    <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($records as $record): ?>
            <?php
                $decision = (string) ($record['admin_decision'] ?? 'pending');
                $createdId = (int) ($record['created_plan_holder_id'] ?? 0);
                $linkedId = (int) ($record['linked_plan_holder_id'] ?? 0);
                $password = (string) ($record['temp_password_plain'] ?? '');
                $passwordCleared = $record['temp_password_hash'] === null && $password === '';
                $recordStatus = (string) ($record['record_status'] ?? 'ready');
            ?>
            <div class="ci-record">
                <header class="ci-record__head">
                    <div class="ci-record__index">#<?= (int) ($record['source_index'] ?? 0) ?></div>
                    <div class="ci-record__name">
                        <span><?= esc(trim((string) ($record['first_name'] ?? '') . ' ' . (string) ($record['last_name'] ?? ''))) ?></span>
                        <div class="ci-record__sub">DOB <?= esc((string) ($record['date_of_birth'] ?? '-')) ?: '-' ?></div>
                    </div>
                    <span class="ci-chip <?= match ($recordStatus) { 'ready' => 'ci-chip--ready', 'needs_attention' => 'ci-chip--needs_attention', 'duplicate' => 'ci-chip--duplicate', default => 'ci-chip--skip' } ?>"><?= esc(ucwords(str_replace('_', ' ', $recordStatus))) ?></span>
                    <span class="ci-chip <?= $decision === 'skip' ? 'ci-chip--skip' : 'ci-chip--decided' ?>"><?= esc(ucwords(str_replace('_', ' ', $decision))) ?></span>

                    <div class="ci-record__actions">
                        <?php if ($createdId > 0): ?>
                            <a class="so-btn so-btn--outline so-btn--sm" href="<?= base_url($base . '/client-management/view/' . $createdId) ?>"><i class="mdi mdi-account-outline"></i> View client</a>
                        <?php elseif ($linkedId > 0): ?>
                            <a class="so-btn so-btn--outline so-btn--sm" href="<?= base_url($base . '/client-management/view/' . $linkedId) ?>"><i class="mdi mdi-link-variant"></i> Linked client</a>
                        <?php endif; ?>
                        <button type="button" class="ci-toggle ci-detail-raw"><i class="mdi mdi-file-search-outline"></i> Source</button>
                    </div>
                </header>

                <div class="ci-record__body">
                    <div class="ci-panel">
                        <div class="ci-panel__title"><i class="mdi mdi-key-outline"></i> Credentials</div>
                        <?php if ($decision === 'create_new'): ?>
                            <?php if ($passwordCleared): ?>
                                <div class="ci-none">Temporary password has been cleared (privacy). Credentials already handed over.</div>
                            <?php else: ?>
                                <div class="ci-form-grid">
                                    <div class="ci-field"><label>Username</label><div style="font-weight:800;"><?= esc($record['temp_username'] ?? '') ?></div></div>
                                    <div class="ci-field"><label>Email</label><div style="font-weight:800;"><?= esc($record['temp_email'] ?? '') ?></div></div>
                                    <div class="ci-field"><label>Temporary password</label><div><span class="ci-password"><?= esc($password !== '' ? $password : '(hidden)') ?></span></div></div>
                                    <div class="ci-field"><label>Must change on login</label><div style="font-weight:800;">Yes</div></div>
                                </div>
                                <button type="button" class="ci-toggle ci-clear-cred" data-record="<?= (int) $record['import_record_id'] ?>" style="margin-top:10px;"><i class="mdi mdi-eraser"></i> Clear credentials</button>
                            <?php endif; ?>
                        <?php elseif ($decision === 'link_existing'): ?>
                            <div class="ci-none">No new account — linked to existing client #<?= $linkedId ?>. No credentials issued.</div>
                        <?php else: ?>
                            <div class="ci-none">No credentials — this record was not imported.</div>
                        <?php endif; ?>
                    </div>

                    <div class="ci-panel">
                        <div class="ci-panel__title"><i class="mdi mdi-information-outline"></i> Outcome &amp; validation</div>
                        <?php if ($decision !== 'skip'): ?>
                            <div style="font-size:.82rem;line-height:1.7;color:var(--so-ink-soft);">
                                <?php if ($decision === 'create_new'): ?>
                                    Created user <b>#<?= (int) ($record['created_user_id'] ?? 0) ?></b>, plan holder <b>#<?= $createdId ?></b>, plan <b>#<?= (int) ($record['created_plan_id'] ?? 0) ?></b>
                                    <?php if (! empty($record['created_plan_id'])): ?>— <?= (int) $record['created_plan_id'] ?><?php endif; ?>.
                                <?php elseif ($decision === 'link_existing'): ?>
                                    Linked to existing plan holder <b>#<?= $linkedId ?></b>. No duplicate created.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="ci-none">Record was skipped and not imported.</div>
                        <?php endif; ?>

                        <?php if (($record['validation_errors'] ?? []) !== []): ?>
                            <ul class="ci-issues" style="margin-top:10px;">
                                <?php foreach ($record['validation_errors'] as $issue): ?>
                                    <li class="ci-issue ci-issue--<?= ($issue['level'] ?? 'warning') === 'error' ? 'error' : 'warning' ?>">
                                        <i class="mdi <?= ($issue['level'] ?? '') === 'error' ? 'mdi-close-circle-outline' : 'mdi-alert-outline' ?>"></i>
                                        <span><?= esc($issue['message'] ?? '') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="ci-raw" hidden><?= esc((string) ($record['extracted_text'] ?? '')) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Print credentials -->
    <?php if ($status === 'committed'): ?>
        <div style="text-align:right;">
            <button type="button" class="so-btn so-btn--purple" id="ciPrintCreds"><i class="mdi mdi-printer"></i> Print credentials</button>
        </div>
    <?php endif; ?>

</div>

<script>
(function () {
    'use strict';

    const base = '<?= esc($base, 'js') ?>';

    document.getElementById('ciRecordList') && null;

    document.addEventListener('click', function (e) {
        const rawBtn = e.target.closest('.ci-detail-raw');
        if (rawBtn) {
            const raw = rawBtn.closest('.ci-record').querySelector('.ci-raw');
            if (raw) raw.hidden = !raw.hidden;
        }

        const clearBtn = e.target.closest('.ci-clear-cred');
        if (clearBtn) {
            const id = clearBtn.dataset.record;
            if (!confirm('Clear the temporary password for this record? It cannot be recovered.')) return;
            fetch(base + '/client-import/record/' + id + '/clear-credentials', { method: 'POST' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) window.location.reload();
                    else alert(data.error || 'Could not clear credentials.');
                });
        }
    });

    const printBtn = document.getElementById('ciPrintCreds');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            const rows = Array.prototype.slice.call(document.querySelectorAll('.ci-record')).map(function (card) {
                const name = card.querySelector('.ci-record__name span');
                const username = card.querySelector('.ci-field:nth-of-type(1) div');
                const pass = card.querySelector('.ci-password');
                return {
                    name: name ? name.textContent.trim() : '',
                    username: username ? username.textContent.trim() : '',
                    password: pass ? pass.textContent.trim() : '',
                };
            }).filter(function (r) { return r.password && r.password !== '(hidden)'; });

            if (rows.length === 0) {
                alert('No temporary credentials available to print.');
                return;
            }

            const win = window.open('', '_blank', 'width=640,height=760');
            if (!win) { alert('Please allow pop-ups to print credentials.'); return; }
            const rowsHtml = rows.map(function (r, i) {
                return '<tr><td>' + (i + 1) + '.</td><td><b>' + esc(r.name) + '</b></td><td>' + esc(r.username) + '</td><td class="pw">' + esc(r.password) + '</td></tr>';
            }).join('');

            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>KAAGAPAY — Temporary Credentials</title><style>'
                + 'body{font-family:Segoe UI,Arial,sans-serif;margin:28px;}h1{font-size:18px;color:#1e3a5f;}'
                + 'p{font-size:12px;color:#555;}table{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px;}'
                + 'td,th{border:1px solid #ddd;padding:8px 10px;text-align:left;}th{background:#eef1f5;}'
                + '.pw{font-family:Consolas,monospace;letter-spacing:1px;font-weight:700;color:#1e3a5f;}'
                + '.note{margin-top:24px;font-size:11px;color:#888;border-top:1px solid #eee;padding-top:10px;}'
                + '</style></head><body>'
                + '<h1>KAAGAPAY MO KARAMAY FUNERAL HOMES CO.</h1>'
                + '<p>Temporary login credentials for newly imported plan holders.</p>'
                + '<table><thead><tr><th>#</th><th>Name</th><th>Username</th><th>Temporary password</th></tr></thead><tbody>'
                + rowsHtml
                + '</tbody></table>'
                + '<p class="note">Clients must change their password on first login. Keep this sheet with the client.</p>'
                + '</body></html>');
            win.document.close();
            win.focus();
            win.print();
        });
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = String(s == null ? '' : s);
        return d.innerHTML;
    }
})();
</script>
<?= $this->endSection() ?>
