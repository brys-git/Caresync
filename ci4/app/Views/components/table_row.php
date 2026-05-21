<?php
/**
 * Table Row Component
 * 
 * Renders a standardized table row with consistent styling
 * 
 * @param string $label Row label/heading
 * @param string $value Row value
 * @param array $badges Optional array of badge data [['status' => 'active', 'label' => 'Active'], ...]
 * @param string $class Additional CSS classes
 * 
 * Usage:
 * <table class="table">
 *     <tbody>
 *         <?= view('components/table_row', ['label' => 'Status', 'value' => 'Active']) ?>
 *         <?= view('components/table_row', ['label' => 'Membership', 'badges' => [['status' => 'active']]]) ?>
 *     </tbody>
 * </table>
 */

$label = (string) ($label ?? '');
$value = (string) ($value ?? '-');
$badges = (array) ($badges ?? []);
$class = (string) ($class ?? '');
?>

<tr class="<?= esc($class) ?>">
    <th><?= esc($label) ?></th>
    <td>
        <?php if ($badges): ?>
            <?php foreach ($badges as $badge): ?>
                <?= view('components/status_badge', $badge) ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?= esc($value) ?>
        <?php endif; ?>
    </td>
</tr>
