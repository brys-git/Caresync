<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/service-offer.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-import.css') ?>">

<?php
    $base = ($role_layout ?? 'layouts/branch_admin') === 'layouts/admin' ? 'admin' : 'branch-admin';
?>

<div class="so ci">

    <div class="ci-link-row">
        <div style="flex:1;">
            <h1 class="so-header__title">Import history</h1>
            <p class="so-header__sub">Every uploaded client-record document, its outcome, and who handled it.</p>
        </div>
        <a class="so-btn so-btn--purple" href="<?= base_url($base . '/client-import') ?>"><i class="mdi mdi-upload"></i> New import</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="so-alert so-alert--error"><i class="mdi mdi-alert-circle-outline"></i> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="so-alert so-alert--success"><i class="mdi mdi-check-circle-outline"></i> <?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <?php if (isset($branches) && $branches !== []): ?>
        <form method="get" action="<?= base_url($base . '/client-import/history') ?>" style="display:flex;gap:10px;align-items:center;">
            <label class="so-form-label" style="margin:0;">Branch:</label>
            <select class="so-form-select" name="branch_id" onchange="this.form.submit()" style="width:auto;min-width:200px;">
                <option value="">All branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= (int) $branch['branch_id'] ?>" <?= isset($filter_branch_id) && (int) $filter_branch_id === (int) $branch['branch_id'] ? 'selected' : '' ?>><?= esc($branch['branch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>

    <?php if ($batches === []): ?>
        <div class="so-empty"><i class="mdi mdi-inbox-outline"></i> No imports found.</div>
    <?php else: ?>
        <div class="so-card" style="padding:0;overflow:hidden;">
            <div style="overflow-x:auto;">
                <table class="so-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Uploaded</th>
                            <th>By</th>
                            <?php if (($role_layout ?? '') === 'layouts/admin'): ?><th>Branch</th><?php endif; ?>
                            <th>Format</th>
                            <th>Ready</th>
                            <th>Needs Attn.</th>
                            <th>Dup.</th>
                            <th>Committed</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                            <?php
                                $status = strtolower((string) ($batch['status'] ?? 'staged'));
                                $badge = $status === 'committed' ? 'so-badge--green' : ($status === 'discarded' ? '' : 'so-badge--amber');
                                $batchId = (int) $batch['import_batch_id'];
                            ?>
                            <tr>
                                <td style="font-weight:700;"><?= esc($batch['original_name'] ?? $batch['filename'] ?? '-') ?></td>
                                <td><?= esc(date('M d, Y H:i', strtotime((string) ($batch['created_at'] ?? 'now')))) ?></td>
                                <td><?= esc($batch['uploader_name'] ?? '-') ?></td>
                                <?php if (($role_layout ?? '') === 'layouts/admin'): ?><td><?= esc($batch['branch_name'] ?? '-') ?></td><?php endif; ?>
                                <td><?= strtoupper(esc((string) ($batch['format'] ?? ''))) ?></td>
                                <td><?= (int) ($batch['ready_count'] ?? 0) ?></td>
                                <td><?= (int) ($batch['needs_attention_count'] ?? 0) ?></td>
                                <td><?= (int) ($batch['duplicate_count'] ?? 0) ?></td>
                                <td><?= (int) ($batch['committed_count'] ?? 0) ?></td>
                                <td><span class="so-badge <?= $badge ?>"><?= esc(ucfirst($status)) ?></span></td>
                                <td class="so-link-row">
                                    <?php if ($status === 'staged'): ?>
                                        <a class="so-btn so-btn--purple so-btn--sm" href="<?= base_url($base . '/client-import/review/' . $batchId) ?>">Review</a>
                                    <?php endif; ?>
                                    <a class="so-btn so-btn--outline so-btn--sm" href="<?= base_url($base . '/client-import/history/' . $batchId) ?>">Detail</a>
                                    <a class="so-btn so-btn--ghost so-btn--sm" href="<?= base_url($base . '/client-import/download/' . $batchId) ?>" title="Download original file"><i class="mdi mdi-download"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>
