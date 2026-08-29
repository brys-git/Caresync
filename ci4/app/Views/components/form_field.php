<?php
/**
 * Form Field Component
 * 
 * Renders a standardized form field with label, input, and error handling
 * 
 * @param string $name Field name (for id, name attributes)
 * @param string $label Display label
 * @param string $type Input type (text, email, password, textarea, select, etc.)
 * @param string $value Current value
 * @param array $options Options for select elements
 * @param bool $required Whether field is required
 * @param string $placeholder Placeholder text
 * @param string $help Help text to display below field
 * @param array $errors Error messages array
 * @param string $class Additional CSS classes
 * 
 * Usage:
 * <?= view('components/form_field', [
 *     'name' => 'email',
 *     'label' => 'Email Address',
 *     'type' => 'email',
 *     'value' => old('email'),
 *     'required' => true,
 *     'errors' => $errors
 * ]) ?>
 */

$name = (string) ($name ?? '');
$label = (string) ($label ?? '');
$type = strtolower((string) ($type ?? 'text'));
$value = (string) ($value ?? '');
$options = (array) ($options ?? []);
$required = (bool) ($required ?? false);
$placeholder = (string) ($placeholder ?? '');
$help = (string) ($help ?? '');
$errors = (array) ($errors ?? []);
$class = (string) ($class ?? '');

$hasError = isset($errors[$name]);
$errorClass = $hasError ? ' is-invalid' : '';
?>

<div class="mb-3">
    <?php if ($label): ?>
        <label for="<?= esc($name) ?>" class="form-label">
            <?= esc($label) ?>
            <?php if ($required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <?php if ($type === 'textarea'): ?>
        <textarea 
            id="<?= esc($name) ?>" 
            name="<?= esc($name) ?>" 
            class="form-control<?= $errorClass ?> <?= esc($class) ?>"
            <?php if ($placeholder): ?>placeholder="<?= esc($placeholder) ?>"<?php endif; ?>
            <?php if ($required): ?>required<?php endif; ?>
        ><?= esc($value) ?></textarea>

    <?php elseif ($type === 'select'): ?>
        <select 
            id="<?= esc($name) ?>" 
            name="<?= esc($name) ?>" 
            class="form-select<?= $errorClass ?> <?= esc($class) ?>"
            <?php if ($required): ?>required<?php endif; ?>
        >
            <option value="">-- Select --</option>
            <?php foreach ($options as $optValue => $optLabel): ?>
                <option value="<?= esc((string) $optValue) ?>" <?php if ((string) $optValue === $value): ?>selected<?php endif; ?>>
                    <?= esc((string) $optLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php else: ?>
        <input 
            type="<?= esc($type) ?>" 
            id="<?= esc($name) ?>" 
            name="<?= esc($name) ?>" 
            value="<?= esc($value) ?>"
            class="form-control<?= $errorClass ?> <?= esc($class) ?>"
            <?php if ($placeholder): ?>placeholder="<?= esc($placeholder) ?>"<?php endif; ?>
            <?php if ($required): ?>required<?php endif; ?>
        />
    <?php endif; ?>

    <?php if ($hasError): ?>
        <div class="invalid-feedback d-block">
            <?= esc($errors[$name]) ?>
        </div>
    <?php elseif ($help): ?>
        <small class="form-text text-muted d-block"><?= esc($help) ?></small>
    <?php endif; ?>
</div>
