<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-import.css') ?>">

<?php
    $base = ($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin' : 'branch-admin';
    $uploaderName = trim((string) ($batch['uploader_name'] ?? ''));
    $batchBranch = (string) ($batch['branch_name'] ?? '');
?>

<div class="so ci">

    <!-- Header -->
    <div class="ci-link-row">
        <div style="flex:1;">
            <h1 class="so-header__title" style="font-size:1.35rem;">Review import</h1>
            <p class="so-header__sub">
                <strong><?= esc($batch['original_name'] ?? $batch['filename'] ?? 'Document') ?></strong>
                · <?= strtoupper(esc((string) ($batch['format'] ?? ''))) ?> ·
                uploaded <?= esc(date('M d, Y H:i', strtotime((string) ($batch['created_at'] ?? 'now')))) ?>
                <?= $uploaderName !== '' ? 'by ' . esc($uploaderName) : '' ?>
                <?= $batchBranch !== '' ? '· ' . esc($batchBranch) : '' ?>
            </p>
        </div>
        <a class="so-btn so-btn--outline" href="<?= base_url($base . '/client-import/history/' . (int) $batch['import_batch_id']) ?>"><i class="mdi mdi-history"></i> Batch detail</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error"><i class="mdi mdi-alert-circle-outline"></i> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success"><i class="mdi mdi-check-circle-outline"></i> <?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (! empty($commit_errors['message'])): ?>
        <div class="so-alert so-alert--error">
            <strong><i class="mdi mdi-alert-octagon-outline"></i> <?= esc($commit_errors['message']) ?></strong>
            <?php if (! empty($commit_errors['errors'])): ?>
                <ul style="margin:8px 0 0 18px;padding:0;">
                    <?php foreach ($commit_errors['errors'] as $label => $errs): ?>
                        <li style="margin:3px 0;"><b><?= esc($label) ?></b>: <?= esc(implode(' · ', $errs)) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="ci-summary" id="ciSummary">
        <div class="ci-stat ci-stat--ready">
            <div class="ci-stat__label">Ready</div>
            <div class="ci-stat__value" id="stat-ready"><?= (int) $counts['ready'] ?></div>
        </div>
        <div class="ci-stat ci-stat--attention">
            <div class="ci-stat__label">Needs attention</div>
            <div class="ci-stat__value" id="stat-na"><?= (int) $counts['needs_attention'] ?></div>
        </div>
        <div class="ci-stat ci-stat--duplicate">
            <div class="ci-stat__label">Duplicates</div>
            <div class="ci-stat__value" id="stat-dup"><?= (int) $counts['duplicate'] ?></div>
        </div>
        <div class="ci-stat ci-stat--skip">
            <div class="ci-stat__label">Skipped</div>
            <div class="ci-stat__value" id="stat-skip"><?= (int) $counts['skip'] ?></div>
        </div>
        <div class="ci-stat ci-stat--decided">
            <div class="ci-stat__label">Decided</div>
            <div class="ci-stat__value" id="stat-decided"><?= (int) $counts['decided'] ?> <small style="font-size:.7rem;color:var(--ci-slate);">/ <?= (int) $counts['total'] ?></small></div>
        </div>
    </div>

    <!-- Record cards -->
    <div id="ciRecordList" style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($records as $record): ?>
            <?= view('client_import/_record_card', ['record' => $record, 'role_layout' => $role_layout ?? 'layouts/branch_admin', 'packages' => $packages ?? []]) ?>
        <?php endforeach; ?>
    </div>

    <!-- Sticky commit bar -->
    <form method="post" action="<?= base_url($base . '/client-import/batch/' . (int) $batch['import_batch_id'] . '/commit') ?>" id="ciCommitForm">
        <?= csrf_field() ?>
        <div class="ci-commit-bar">
            <div class="ci-commit-bar__summary">
                <span><b id="bar-decided"><?= (int) $counts['decided'] ?></b> decided</span>
                <span><b id="bar-unresolved"><?= (int) $counts['unresolved'] ?></b> unresolved</span>
                <span><b id="bar-blocking"><?= (int) $counts['blocking'] ?></b> blocking</span>
                <div class="ci-progress" style="min-width:160px;">
                    <div class="ci-progress__bar" id="bar-progress" style="width:<?= $counts['total'] > 0 ? (int) round(($counts['decided'] / $counts['total']) * 100) : 0 ?>%;"></div>
                </div>
            </div>
            <button type="submit" class="ci-commit-btn" id="ciCommitBtn" <?= $counts['can_commit'] ? '' : 'disabled' ?>>
                <i class="mdi mdi-content-save-all"></i> Commit import
            </button>
        </div>
    </form>

</div>

<!-- Confirm modal -->
<div class="ci-modal-overlay" id="ciConfirmModal">
    <div class="ci-modal">
        <div class="ci-modal__title">Commit this import?</div>
        <div class="ci-modal__body" id="ciConfirmBody"></div>
        <div class="ci-modal__footer">
            <button type="button" class="so-btn so-btn--outline" data-close-modal>Cancel</button>
            <button type="button" class="so-btn so-btn--purple" id="ciConfirmYes">Yes, commit all</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const base = '<?= esc($base, 'js') ?>';
    const recordList = document.getElementById('ciRecordList');

    // ---- utilities ----
    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = String(s == null ? '' : s);
        return d.innerHTML;
    }

    function cardOf(btn) {
        return btn.closest('.ci-record');
    }

    function recordId(card) {
        return card.dataset.recordId;
    }

    function collectForm(card) {
        const fd = new FormData();
        card.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name) return;
            if (el.type === 'radio' && !el.checked) return;
            fd.append(el.name, el.value);
        });
        return fd;
    }

    function chipFor(status) {
        const map = {
            ready: ['ci-chip--ready', 'Ready'],
            needs_attention: ['ci-chip--needs_attention', 'Needs Attention'],
            duplicate: ['ci-chip--duplicate', 'Duplicate'],
            skip: ['ci-chip--skip', 'Skip'],
        };
        return map[status] || ['ci-chip--ready', status];
    }

    function borderFor(status) {
        return { ready: 'ci-record--ready', needs_attention: 'ci-record--attention', duplicate: 'ci-record--duplicate' }[status] || '';
    }

    function renderIssues(card, issues) {
        const container = card.querySelector('.ci-issues');
        if (!container) return;
        if (!issues || issues.length === 0) {
            container.innerHTML = '<li class="ci-none"><i class="mdi mdi-check-circle-outline"></i> No validation issues.</li>';
            return;
        }
        container.innerHTML = issues.map(function (issue) {
            const isError = issue.level === 'error';
            return '<li class="ci-issue ci-issue--' + (isError ? 'error' : 'warning') + '">' +
                '<i class="mdi ' + (isError ? 'mdi-close-circle-outline' : 'mdi-alert-outline') + '"></i>' +
                '<span>' + escHtml(issue.message) + '</span></li>';
        }).join('');
    }

    function setStatus(card, status) {
        card.dataset.status = status;
        const chip = card.querySelector('.ci-status-chip');
        if (chip) {
            const c = chipFor(status);
            chip.className = 'ci-chip ci-status-chip ' + c[0];
            chip.textContent = c[1];
        }
        card.classList.remove('ci-record--ready', 'ci-record--attention', 'ci-record--duplicate');
        const border = borderFor(status);
        if (border) card.classList.add(border);

        const nameInputs = card.querySelectorAll('input[name="first_name"], input[name="last_name"]');
        nameInputs.forEach(function (el) { el.classList.remove('ci-invalid'); });
    }

    function setBlocking(card, hasBlocking) {
        card.querySelectorAll('input[name="first_name"], input[name="last_name"]').forEach(function (el) {
            el.classList.toggle('ci-invalid', !!hasBlocking);
        });
    }

    function updateSummary(summary) {
        if (!summary) return;
        const ids = {
            'stat-ready': summary.ready, 'stat-na': summary.needs_attention,
            'stat-dup': summary.duplicate, 'stat-skip': summary.skip,
            'stat-decided': summary.decided,
        };
        for (const id in ids) {
            const el = document.getElementById(id);
            if (el) {
                if (id === 'stat-decided') el.innerHTML = ids[id] + ' <small style="font-size:.7rem;color:var(--ci-slate);">/ ' + summary.total + '</small>';
                else el.textContent = ids[id];
            }
        }
        const set = function (id, v) { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('bar-decided', summary.decided);
        set('bar-unresolved', summary.unresolved);
        set('bar-blocking', summary.blocking);
        const bar = document.getElementById('bar-progress');
        if (bar) bar.style.width = summary.total > 0 ? Math.round((summary.decided / summary.total) * 100) + '%' : '0%';
        const btn = document.getElementById('ciCommitBtn');
        if (btn) btn.disabled = !summary.can_commit;
    }

    function setDecisionChip(card, decision) {
        let chip = card.querySelector('.ci-decision-chip');
        if (decision === 'pending') {
            if (chip) chip.remove();
            return;
        }
        if (!chip) {
            chip = document.createElement('span');
            chip.className = 'ci-chip ci-chip--decided ci-decision-chip';
            card.querySelector('.ci-record__actions').insertAdjacentElement('beforebegin', chip);
        }
        chip.textContent = decision.replace(/_/g, ' ');
        card.dataset.decision = decision;
    }

    // ---- Save ----
    recordList.addEventListener('click', function (e) {
        const saveBtn = e.target.closest('.ci-save');
        if (!saveBtn) return;
        const card = cardOf(saveBtn);
        const btn = saveBtn;
        btn.disabled = true;

        fetch(base + '/client-import/record/' + recordId(card) + '/save', {
            method: 'POST',
            body: collectForm(card),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btn.disabled = false;
            if (!data.ok) { alert(data.error || 'Save failed'); return; }
            const rec = data.record || {};
            setStatus(card, data.status || rec.record_status || 'needs_attention');
            setBlocking(card, rec.has_blocking_errors);
            renderIssues(card, rec.validation_errors);
            const user = card.querySelector('.ci-cred');
            if (user && data.temp_username) {
                user.innerHTML = '<i class="mdi mdi-key-outline"></i> ' + escHtml(data.temp_username) +
                    ' · <span class="ci-temp-email">' + escHtml(data.temp_email || '') + '</span>';
            }
            // Refresh match candidates? Keep simple: page reload if the status flipped to duplicate.
            updateSummary(data.summary);
        })
        .catch(function () {
            btn.disabled = false;
            alert('Could not save. Please try again.');
        });
    });

    // ---- Decision ----
    recordList.addEventListener('change', function (e) {
        const sel = e.target.closest('.ci-decision');
        if (!sel) return;
        const card = cardOf(sel);
        const decision = sel.value;
        const linkedTarget = card.querySelector('input[name="link_target"]:checked');

        if (decision === 'link_existing' && !linkedTarget) {
            sel.value = 'pending';
            alert('Choose a matching client below (or leave the decision pending).');
            return;
        }

        const body = new FormData();
        body.append('decision', decision);
        if (linkedTarget) body.append('linked_plan_holder_id', linkedTarget.value);

        fetch(base + '/client-import/record/' + recordId(card) + '/decide', {
            method: 'POST',
            body: body,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) { alert(data.error || 'Could not save decision'); return; }
            setDecisionChip(card, data.decision);
            updateSummary(data.summary);
        })
        .catch(function () { alert('Could not save decision.'); });
    });

    // Selecting a link target when already link_existing updates the target.
    recordList.addEventListener('change', function (e) {
        const radio = e.target.closest('input[name="link_target"]');
        if (!radio) return;
        const card = cardOf(radio);
        if (card.dataset.decision === 'link_existing') {
            const body = new FormData();
            body.append('decision', 'link_existing');
            body.append('linked_plan_holder_id', radio.value);
            fetch(base + '/client-import/record/' + recordId(card) + '/decide', { method: 'POST', body: body })
                .then(function (r) { return r.json(); });
        }
    });

    // ---- Beneficiary rows ----
    recordList.addEventListener('click', function (e) {
        if (e.target.closest('.ci-benef-add')) {
            const card = cardOf(e.target.closest('.ci-benef-add'));
            addBeneficiary(card);
        }
        if (e.target.closest('.ci-benef-remove')) {
            const row = e.target.closest('.ci-benef-row');
            const card = row.closest('.ci-record');
            row.remove();
            renumberBeneficiaries(card);
        }
    });

    function addBeneficiary(card) {
        const tbody = card.querySelector('.ci-benef-rows');
        const empty = tbody.querySelector('.ci-benef-empty');
        if (empty) empty.remove();
        const tr = document.createElement('tr');
        tr.className = 'ci-benef-row';
        tr.innerHTML =
            '<td class="ci-benef-idx"></td>' +
            '<td><input type="text" name="beneficiaries[0][first_name]" placeholder="First"></td>' +
            '<td><input type="text" name="beneficiaries[0][middle_name]" placeholder="Middle"></td>' +
            '<td><input type="text" name="beneficiaries[0][last_name]" placeholder="Last"></td>' +
            '<td><input type="text" name="beneficiaries[0][name_extension]" placeholder="Jr."></td>' +
            '<td><input type="text" name="beneficiaries[0][birthday_raw]" placeholder="MM-DD-YYYY"></td>' +
            '<td><input type="text" name="beneficiaries[0][relationship]" placeholder="Relation"></td>' +
            '<td><button type="button" class="ci-benef-remove" title="Remove"><i class="mdi mdi-close"></i></button></td>';
        tbody.appendChild(tr);
        renumberBeneficiaries(card);
        tr.querySelector('input').focus();
    }

    function renumberBeneficiaries(card) {
        const rows = card.querySelectorAll('.ci-benef-row');
        rows.forEach(function (row, i) {
            row.querySelector('.ci-benef-idx').textContent = i + 1;
            row.querySelectorAll('input').forEach(function (input) {
                const m = input.name.match(/^beneficiaries\[\d+\]\[(.+)\]$/);
                if (m) input.name = 'beneficiaries[' + i + '][' + m[1] + ']';
            });
        });
    }

    // ---- Toggles ----
    recordList.addEventListener('click', function (e) {
        const rawBtn = e.target.closest('.ci-toggle-raw');
        if (rawBtn) {
            const card = cardOf(rawBtn);
            const raw = card.querySelector('.ci-raw');
            if (raw) raw.hidden = !raw.hidden;
        }
        const optBtn = e.target.closest('.ci-toggle-optional');
        if (optBtn) {
            const card = cardOf(optBtn);
            const body = card.querySelector('.ci-optional-body');
            if (body) {
                body.hidden = !body.hidden;
                optBtn.textContent = body.hidden ? 'Show' : 'Hide';
            }
        }
    });

    // ---- Commit confirm ----
    const commitBtn = document.getElementById('ciCommitBtn');
    const modal = document.getElementById('ciConfirmModal');
    const confirmYes = document.getElementById('ciConfirmYes');
    const commitForm = document.getElementById('ciCommitForm');

    commitBtn.addEventListener('click', function (e) {
        if (commitBtn.disabled) { e.preventDefault(); return; }
        e.preventDefault();
        const decided = document.getElementById('bar-decided').textContent;
        const unresolved = document.getElementById('bar-unresolved').textContent;
        const body = document.getElementById('ciConfirmBody');
        body.innerHTML =
            '<p>This will write <b>' + decided + '</b> reviewed record(s) into the system in a single transaction.</p>' +
            '<p>New accounts are created with a <b>temporary password</b>; clients will be forced to change it on their first login. ' +
            '<span class="ci-password">' + (unresolved > 0 ? '' : '') + '</span></p>' +
            (unresolved > 0 ? '<p style="color:#dc2626;">⚠ ' + unresolved + ' record(s) still have no decision — they will block the commit.</p>' : '') +
            '<p style="color:var(--so-ink-soft);font-size:.8rem;">This cannot be undone. Nothing is written until you confirm.</p>';
        modal.classList.add('show');
    });

    confirmYes.addEventListener('click', function () {
        commitForm.submit();
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () { modal.classList.remove('show'); });
    });
})();
</script>
<?= $this->endSection() ?>
