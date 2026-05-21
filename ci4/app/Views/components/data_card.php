<?php
/**
 * Data Display Card Component
 * 
 * Renders a styled card for displaying structured data
 * 
 * @param string $title Card title
 * @param array $data Array of key-value pairs ['key' => 'label', 'value' => 'value']
 * @param array $actions Optional array of action buttons
 * @param string $class Additional CSS classes
 * 
 * Usage:
 * <?= view('components/data_card', [
 *     'title' => 'Account Information',
 *     'data' => [
 *         ['label' => 'Username', 'value' => 'john_doe'],
 *         ['label' => 'Email', 'value' => 'john@example.com'],
 *     ],
 *     'actions' => [
 *         ['label' => 'Edit', 'url' => '/edit', 'class' => 'btn-primary'],
 *     ]
 * ]) ?>
 */

$title = (string) ($title ?? '');
$data = (array) ($data ?? []);
$actions = (array) ($actions ?? []);
$class = (string) ($class ?? '');
?>

<div class="card <?= esc($class) ?>">
    <div class="card-body">
        <?php if ($title): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?= esc($title) ?></h5>
                <?php if ($actions): ?>
                    <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ($actions as $action): ?>
                            <a href="<?= esc($action['url'] ?? '#') ?>" 
                               class="btn <?= esc($action['class'] ?? 'btn-outline-primary') ?>">
                                <?= esc($action['label'] ?? 'Action') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach ($data as $item): ?>
                <?php
                $label = (string) ($item['label'] ?? '');
                $value = (string) ($item['value'] ?? '-');
                $size = (string) ($item['size'] ?? 'md-6');
                $badges = (array) ($item['badges'] ?? []);
                ?>
                <div class="col-<?= esc($size) ?>">
                    <small class="text-muted d-block"><?= esc($label) ?></small>
                    <?php if ($badges): ?>
                        <strong>
                            <?php foreach ($badges as $badge): ?>
                                <?= view('components/status_badge', $badge) ?>
                            <?php endforeach; ?>
                        </strong>
                    <?php else: ?>
                        <strong><?= esc($value) ?></strong>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
