<?php
/**
 * Button Component
 * 
 * Renders a standardized button with consistent styling
 * 
 * @param string $label Button label
 * @param string $url Button URL/href
 * @param string $type Button type (primary, secondary, success, danger, warning, info)
 * @param string $size Button size (sm, md, lg) - default is md
 * @param bool $outline Use outline variant
 * @param string $icon Optional icon HTML/emoji
 * @param string $class Additional CSS classes
 * @param bool $block Full width button
 * @param string $target Link target (e.g., '_blank')
 * 
 * Usage:
 * <?= view('components/button', ['label' => 'Submit', 'url' => '/submit', 'type' => 'primary']) ?>
 * <?= view('components/button', ['label' => 'Delete', 'url' => '/delete', 'type' => 'danger', 'outline' => true]) ?>
 */

$label = (string) ($label ?? '');
$url = (string) ($url ?? '#');
$type = strtolower((string) ($type ?? 'primary'));
$size = strtolower((string) ($size ?? 'md'));
$outline = (bool) ($outline ?? false);
$icon = (string) ($icon ?? '');
$class = (string) ($class ?? '');
$block = (bool) ($block ?? false);
$target = (string) ($target ?? '');

// Build button classes
$sizeClass = match ($size) {
    'sm' => 'btn-sm',
    'lg' => 'btn-lg',
    default => '',
};

$outlinePrefix = $outline ? 'outline-' : '';
$btnClass = "btn btn-{$outlinePrefix}{$type} {$sizeClass}";

if ($block) {
    $btnClass .= ' w-100';
}

if ($class) {
    $btnClass .= " {$class}";
}
?>

<a href="<?= esc($url) ?>" 
   class="<?= esc(trim($btnClass)) ?>"
   <?php if ($target): ?>target="<?= esc($target) ?>"<?php endif; ?>>
    <?php if ($icon): ?>
        <span class="me-2"><?= $icon ?></span>
    <?php endif; ?>
    <?= esc($label) ?>
</a>
