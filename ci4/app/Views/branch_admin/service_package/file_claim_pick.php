<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?= view('partials/search_bar', ['action' => base_url('branch-admin/service-package/file-claim'), 'term' => $search, 'placeholder' => 'Name, mobile number or plan number']) ?>

<section class="cs-panel">
    <div class="cs-panel__body cs-panel__body--flush">
        <?php if ($holders === []): ?>
            <?= view('components/empty_state', ['icon' => 'ti-users', 'title' => 'No plan holders found', 'text' => 'Only active plan holders of your branch are listed.']) ?>
        <?php else: ?>
            <div class="cs-tablewrap">
                <table class="cs-table">
                    <thead><tr><th>Plan holder</th><th>Plan no.</th><th>Mobile</th><th class="cs-table__actions">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($holders as $h): ?>
                            <tr>
                                <td><?= esc(trim($h['first_name'] . ' ' . $h['last_name'])) ?></td>
                                <td><?= esc((string) ($h['unique_identifier'] ?: '-')) ?></td>
                                <td><?= esc((string) ($h['contact_number'] ?: '-')) ?></td>
                                <td class="cs-table__actions"><a class="btn btn-sm btn-primary" href="<?= base_url('branch-admin/service-package/file-claim/' . (int) $h['plan_holder_id']) ?>">File claim</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
