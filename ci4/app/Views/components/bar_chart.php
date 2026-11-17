<?php
/**
 * Column chart drawn with plain HTML/CSS (no chart library, nothing loaded from
 * the internet). Each column has the amount in its title and in a "Show amounts"
 * table underneath, so the numbers are readable on a phone and by screen readers.
 *
 * <?= view('components/bar_chart', [
 *     'title'  => 'Monthly collections',
 *     'items'  => [['label' => 'Nov', 'full' => 'Nov 2026', 'value' => 1200.0], ...],
 *     'money'  => true,
 *     'empty'  => 'No collections recorded yet',
 * ]) ?>
 */
$items = $items ?? [];
$money = (bool) ($money ?? false);
$title = (string) ($title ?? 'Chart');
$unit  = (string) ($unit ?? 'Month');
$updated = isset($updated) && $updated ? (int) $updated : null;
$max   = 0.0;
foreach ($items as $i) {
    $max = max($max, (float) ($i['value'] ?? 0));
}
$fmt = static function (float $v) use ($money): string {
    if (! $money) {
        return number_format($v);
    }
    if ($v >= 1000000) {
        return '₱' . rtrim(rtrim(number_format($v / 1000000, 1), '0'), '.') . 'M';
    }
    if ($v >= 1000) {
        return '₱' . rtrim(rtrim(number_format($v / 1000, 1), '0'), '.') . 'k';
    }

    return '₱' . number_format($v);
};
$full = static fn (float $v): string => $money ? '₱' . number_format($v, 2) : number_format($v);
?>
<?php if ($max <= 0): ?>
    <?= view('components/empty_state', ['icon' => 'ti-chart-bar-off', 'title' => (string) ($empty ?? 'Nothing to show yet')]) ?>
<?php else: ?>
    <figure class="cs-chart" aria-label="<?= esc($title, 'attr') ?>">
        <div class="cs-chart__top"><span class="cs-muted">Highest: <strong><?= esc($full($max)) ?></strong></span></div>
        <div class="cs-chart__cols">
            <?php foreach ($items as $i): ?>
                <?php $v = (float) ($i['value'] ?? 0); $pct = $max > 0 ? max(2, round($v / $max * 100)) : 2; ?>
                <div class="cs-chart__col" title="<?= esc((string) ($i['full'] ?? $i['label']) . ': ' . $full($v), 'attr') ?>">
                    <span class="cs-chart__val"><?= esc($v > 0 ? $fmt($v) : '') ?></span>
                    <span class="cs-chart__bar" style="height: <?= (int) $pct ?>%"></span>
                    <span class="cs-chart__lbl"><?= esc((string) $i['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <details class="cs-chart__details">
            <summary>Show amounts</summary>
            <table class="cs-table cs-table--compact">
                <thead><tr><th><?= esc($unit) ?></th><th class="cs-num">Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr><td><?= esc((string) ($i['full'] ?? $i['label'])) ?></td><td class="cs-num"><?= esc($full((float) ($i['value'] ?? 0))) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
        <?php if ($updated): ?><p class="cs-chart__updated">Updated <?= esc(date('g:i A', $updated)) ?> · refreshes every 10 minutes</p><?php endif; ?>
    </figure>
<?php endif; ?>
