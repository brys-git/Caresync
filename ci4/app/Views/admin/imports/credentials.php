<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php // Flash messages are surfaced as toasts by layouts/_shell.php. ?>
<div class="alert alert-warning" role="alert">
    <strong>Handle with care.</strong> This file contains every plain temporary password. Download or print it, hand the logins out, then delete the file. The system stores only hashed passwords, so these cannot be shown again.
</div>

<?php if ($rows === null): ?>
    <section class="cs-panel">
        <div class="cs-panel__body cs-panel__body--flush">
            <?php if (empty($files)): ?>
                <?= view('components/empty_state', ['icon' => 'ti-key', 'title' => 'No credentials files']) ?>
            <?php else: ?>
                <div class="cs-tablewrap">
                    <table class="cs-table">
                        <thead><tr><th>File</th><th>Created</th><th>Logins</th><th class="cs-table__actions">Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($files as $f): ?>
                                <tr>
                                    <td><?= esc($f['name']) ?></td>
                                    <td><?= esc(date('M j, Y g:i A', $f['modified'])) ?></td>
                                    <td><?= (int) $f['rows'] ?></td>
                                    <td class="cs-table__actions">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('/admin/imports/credentials/' . $f['name']) ?>">Printable page</a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('/admin/imports/credentials/' . $f['name'] . '/download') ?>">Download CSV</a>
                                        <form method="post" action="<?= site_url('/admin/imports/credentials/' . $f['name'] . '/delete') ?>" class="d-inline"
                                              data-cs-confirm="Delete this file? The temporary passwords cannot be recovered afterwards." data-cs-confirm-title="Delete file" data-cs-confirm-ok="Delete">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <div class="mb-3 d-flex justify-content-between">
        <a href="<?= site_url('/admin/imports/credentials') ?>" class="btn btn-ghost btn-sm">Back</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="ti ti-printer" aria-hidden="true"></i> Print</button>
    </div>
    <section class="cs-panel">
        <div class="cs-panel__body cs-panel__body--flush">
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>Ref</th><th>Name</th><th>Address</th><th>Username</th><th>Temporary password</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-nowrap"><?= esc($r[0] ?? '') ?></td>
                                <td><?= esc($r[1] ?? '') ?></td>
                                <td><?= esc($r[2] ?? '') ?></td>
                                <td class="text-nowrap"><code><?= esc($r[3] ?? '') ?></code></td>
                                <td class="text-nowrap"><code><?= esc($r[4] ?? '') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>
<?= $this->endSection() ?>
