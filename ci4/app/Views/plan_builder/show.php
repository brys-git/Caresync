<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$isActive = (int) ($program['is_active'] ?? 0) === 1;
$holderCount = (int) ($plan_holder_count ?? 0);
?>

<a class="text-decoration-none text-muted mb-3 d-inline-block" href="<?= base_url('plan-builder') ?>">
    <i class="ti ti-arrow-left me-1"></i> Back to Plan Builder
</a>

<section class="cs-panel mb-3">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title"><?= esc((string) $program['program_name']) ?></h2>
        <?php if (! empty($can_manage)): ?>
            <div class="d-flex gap-2">
                <a href="<?= base_url('plan-builder/' . (int) $program['program_id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                <form method="post" action="<?= base_url('plan-builder/' . (int) $program['program_id'] . '/toggle') ?>" data-cs-confirm="<?= $isActive ? 'Deactivate this plan?' : 'Activate this plan?' ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                </form>
                <form method="post" action="<?= base_url('plan-builder/' . (int) $program['program_id'] . '/delete') ?>" data-cs-confirm="Delete this plan? This cannot be undone.">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <div class="cs-panel__body">
        <?php if (! empty($program['description'])): ?>
            <p class="cs-muted"><?= esc((string) $program['description']) ?></p>
        <?php endif; ?>
        <?= view('components/stat_rail', ['stats' => [
            ['label' => 'Plan Price', 'value' => $program['plan_price'] ?? 0, 'money' => true, 'tone' => 'money'],
            [
                'label' => 'Monthly Contribution',
                'value' => $program['monthly_fee'] ?? 0,
                'money' => true,
                // Plain text, not cs_money() - stat_rail's 'meta' field is
                // esc()'d as a whole, which would show cs_money()'s <span>
                // markup as literal text instead of rendering it.
                'meta'  => '₱' . number_format((float) ($program['monthly_fee'] ?? 0), 2) . ' / month × ' . ((int) ($program['term_months'] ?? 0)) . ' months',
            ],
            ['label' => 'Status', 'value' => $isActive ? 'Active' : 'Inactive', 'tone' => $isActive ? 'ok' : ''],
            ['label' => 'Plan Holders', 'value' => $holderCount],
        ]]) ?>
    </div>
</section>

<section class="cs-panel">
    <div class="cs-panel__head">
        <h2 class="cs-panel__title">Entitled Package</h2>
    </div>
    <div class="cs-panel__body">
        <?php if (empty($package)): ?>
            <p class="cs-muted mb-0">No entitled package set for this plan.</p>
        <?php else: ?>
            <h3 class="h6 mb-1"><?= esc((string) $package['package_name']) ?></h3>
            <?php if (! empty($package['description'])): ?>
                <p class="cs-muted"><?= esc((string) $package['description']) ?></p>
            <?php endif; ?>

            <h4 class="h6 mt-3 mb-2">Inclusions</h4>
            <?php if (empty($inclusions)): ?>
                <p class="cs-muted mb-0">No inclusions listed.</p>
            <?php else: ?>
                <ul class="mb-0">
                    <?php foreach ($inclusions as $item): ?>
                        <li>
                            <strong><?= esc((string) $item['item_name']) ?></strong>
                            <?php if (! empty($item['description'])): ?>
                                <span class="cs-muted"> - <?= esc((string) $item['description']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
