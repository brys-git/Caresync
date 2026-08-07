<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-import.css') ?>">

<div class="so ci">

    <div>
        <h1 class="so-header__title">Client Record Import</h1>
        <p class="so-header__sub">Upload a client-record document, review every extracted client, then commit. Nothing is saved until you approve it.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error"><i class="mdi mdi-alert-circle-outline"></i> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success"><i class="mdi mdi-check-circle-outline"></i> <?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="so-grid" style="grid-template-columns: 1.2fr 1fr;">

        <!-- Upload card -->
        <div class="so-card">
            <div class="so-card__title">Upload a document</div>
            <p style="font-size:0.85rem;color:var(--so-ink-soft);margin:0 0 14px;">
                Supported formats: <b>.docx</b> (typed record-profiling documents) and <b>.csv</b> (downloadable template).
                Extracted records go to a review screen — the database is only written after you commit.
            </p>

            <form method="post" action="<?= base_url(($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin/client-import/upload' : 'branch-admin/client-import/upload') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <?php if (isset($branches) && $branches !== []): ?>
                    <div class="so-form-group">
                        <label class="so-form-label" for="branch_id">Branch for this import</label>
                        <select class="so-form-select" name="branch_id" id="branch_id" required>
                            <option value="">— Select branch —</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= (int) $branch['branch_id'] ?>" <?= isset($filter_branch_id) && (int) $filter_branch_id === (int) $branch['branch_id'] ? 'selected' : '' ?>><?= esc($branch['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <label class="ci-drop" id="ciDrop">
                    <input type="file" name="import_file" id="ciFile" accept=".docx,.csv" required>
                    <span class="mdi mdi-cloud-upload-outline ci-drop__icon"></span>
                    <div class="ci-drop__title">Click to choose a document, or drag it here</div>
                    <div class="ci-drop__hint" id="ciDropHint">.docx or .csv — max file size is handled by the server</div>
                </label>

                <div style="margin-top:16px;display:flex;gap:10px;align-items:center;">
                    <button class="so-btn so-btn--purple" type="submit">
                        <i class="mdi mdi-file-document-outline"></i> Parse &amp; stage for review
                    </button>
                    <a class="so-btn so-btn--outline" href="<?= base_url(($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin/client-import/template/csv' : 'branch-admin/client-import/template/csv') ?>">
                        <i class="mdi mdi-download"></i> CSV template
                    </a>
                </div>
            </form>
        </div>

        <!-- How it works -->
        <div class="so-card">
            <div class="so-card__title">How the import works</div>
            <ol style="font-size:0.85rem;color:var(--so-ink-soft);line-height:1.8;margin:0;padding-left:20px;">
                <li>The document is parsed and each client is mapped to CareSync fields.</li>
                <li>Existing clients are detected automatically (name + birthdate scoring).</li>
                <li>You review each record, fix anything unclear, and decide: <b>Create</b>, <b>Link to existing</b>, or <b>Skip</b>.</li>
                <li>Committing creates accounts with temporary passwords — clients change them on first login.</li>
                <li>Every import is logged with who uploaded and who committed it.</li>
            </ol>
        </div>

    </div>

    <!-- Recent batches -->
    <?php if (isset($batches) && $batches !== []): ?>
        <div class="so-card" style="padding:0;overflow:hidden;">
            <div class="so-card__title" style="padding:16px 18px 6px;">Recent imports</div>
            <div style="overflow-x:auto;">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Uploaded</th>
                            <th>By</th>
                            <th>Records</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                            <?php
                                $status = strtolower((string) ($batch['status'] ?? 'staged'));
                                $badge = $status === 'committed' ? 'so-badge--green' : ($status === 'discarded' ? '' : 'so-badge--amber');
                            ?>
                            <tr>
                                <td style="font-weight:700;"><?= esc($batch['original_name'] ?? $batch['filename'] ?? '-') ?></td>
                                <td><?= esc(date('M d, Y H:i', strtotime((string) ($batch['created_at'] ?? 'now')))) ?></td>
                                <td><?= esc($batch['uploader_name'] ?? '-') ?></td>
                                <td><?= (int) ($batch['total_records'] ?? 0) ?></td>
                                <td><span class="so-badge <?= $badge ?>"><?= esc(ucfirst($status)) ?></span></td>
                                <td class="so-link-row">
                                    <?php if ($status === 'staged'): ?>
                                        <a class="so-btn so-btn--purple so-btn--sm" href="<?= base_url(($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin/client-import/review/' : 'branch-admin/client-import/review/') . (int) $batch['import_batch_id'] ?>">Review</a>
                                    <?php endif; ?>
                                    <a class="so-btn so-btn--outline so-btn--sm" href="<?= base_url(($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin/client-import/history/' : 'branch-admin/client-import/history/') . (int) $batch['import_batch_id'] ?>">Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="text-align:right;">
            <a class="so-btn so-btn--ghost" href="<?= base_url(($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin/client-import/history' : 'branch-admin/client-import/history') ?>">View full history <i class="mdi mdi-arrow-right"></i></a>
        </div>
    <?php else: ?>
        <div class="so-empty"><i class="mdi mdi-inbox-outline"></i> No imports yet — upload a client-record document above.</div>
    <?php endif; ?>

</div>

<script>
(function () {
    const drop = document.getElementById('ciDrop');
    const fileInput = document.getElementById('ciFile');
    const hint = document.getElementById('ciDropHint');

    if (!drop || !fileInput) return;

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) {
            e.preventDefault();
            drop.classList.add('ci-drop--dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) {
            e.preventDefault();
            drop.classList.remove('ci-drop--dragover');
        });
    });
    drop.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            onFile();
        }
    });
    fileInput.addEventListener('change', onFile);

    function onFile() {
        if (!fileInput.files.length) return;
        const f = fileInput.files[0];
        drop.classList.add('ci-drop--has-file');
        hint.textContent = f.name + ' — ' + (f.size / 1024).toFixed(1) + ' KB selected';
    }
})();
</script>
<?= $this->endSection() ?>
