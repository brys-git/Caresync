<?php
/**
 * Horizontal bar chart (HTML/CSS only). One row per item: label, bar, value.
 * On a phone the label sits above its bar, so nothing scrolls sideways.
 *
 * <?= view('components/hbar_chart', ['title' => 'Members by branch', 'items' => [['label' => 'Calapan', 'value' => 12]], 'empty' => 'No branches yet']) ?>
 */
$items = $items ?? [];
$title = (string) ($title ?? 'Chart');
$updated = isset($updated) && $updated ? (int) $updated : null;
$max   = 0.0;
foreach ($items as $i) {
    $max = max($max, (float) ($i['value'] ?? 0));
}
?>
<?php if ($items === []): ?>
    <?= view('components/empty_state', ['icon' => 'ti-building-community', 'title' => (string) ($empty ?? 'Nothing to show yet')]) ?>
<?php else: ?>
    <ul class="cs-hbars" aria-label="<?= esc($title, 'attr') ?>">
        <?php foreach ($items as $i): ?>
            <?php $v = (float) ($i['value'] ?? 0); $pct = $max > 0 ? max($v > 0 ? 2 : 0, round($v / $max * 100)) : 0; ?>
            <li class="cs-hbars__row">
                <span class="cs-hbars__label"><?= esc((string) $i['label']) ?></span>
                <span class="cs-hbars__track"><span class="cs-hbars__bar" style="width: <?= (int) $pct ?>%"></span></span>
                <span class="cs-hbars__val cs-num"><?= esc(number_format($v)) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($updated): ?><p class="cs-chart__updated px-3 pb-3">Updated <?= esc(date('g:i A', $updated)) ?> · refreshes every 10 minutes</p><?php endif; ?>
<?php endif; ?>
