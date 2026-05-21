<?php
/**
 * Info Card Component
 * 
 * Renders a standardized info card for displaying key-value pairs
 * 
 * @param string $label The label/heading
 * @param string $value The value to display
 * @param string $size Bootstrap grid size (e.g., 'md-3', 'md-4', 'md-6')
 * @param string $bg Background class ('light', 'white', 'secondary', etc.)
 * @param string $class Additional CSS classes
 * 
 * Usage:
 * <?= view('components/info_card', ['label' => 'Status', 'value' => 'Active', 'size' => 'md-3']) ?>
 */

$label = (string) ($label ?? '');
$value = (string) ($value ?? '-');
$size = (string) ($size ?? 'md-3');
$bg = (string) ($bg ?? '');
$class = (string) ($class ?? '');
$bgClass = $bg ? "bg-{$bg}" : '';
?>
<div class="col-<?= esc($size) ?>">
    <div class="border rounded p-3 <?= esc($bgClass) ?> <?= esc($class) ?>">
        <small class="text-muted d-block"><?= esc($label) ?></small>
        <strong><?= esc($value) ?></strong>
    </div>
</div>
