<?php
/**
 * Membership State Indicator Component
 * 
 * Renders a standardized membership state display with color coding and messaging
 * 
 * @param string $state Membership state (active, pending, delinquent, suspended)
 * @param int $overdueMonths Number of overdue months (for delinquent state)
 * @param bool $compact Compact display (badge only) vs detailed display
 * 
 * Usage:
 * <?= view('components/membership_state', ['state' => 'active']) ?>
 * <?= view('components/membership_state', ['state' => 'delinquent', 'overdueMonths' => 3]) ?>
 */

$state = strtolower(trim((string) ($state ?? 'active')));
$overdueMonths = (int) ($overdueMonths ?? 0);
$compact = (bool) ($compact ?? false);

// State definitions
$stateConfig = [
    'active' => [
        'label' => 'Active',
        'badge_class' => 'success',
        'icon' => '✓',
        'description' => 'Your membership is active and in good standing.',
    ],
    'pending' => [
        'label' => 'Pending',
        'badge_class' => 'warning',
        'icon' => '⏳',
        'description' => 'Your membership registration is under review.',
    ],
    'delinquent' => [
        'label' => 'Delinquent',
        'badge_class' => 'warning',
        'icon' => '⚠',
        'description' => "Your membership has $overdueMonths overdue months. Services available for 2 months.",
    ],
    'suspended' => [
        'label' => 'Suspended',
        'badge_class' => 'danger',
        'icon' => '✕',
        'description' => 'Your membership is suspended. Contact our office to restore.',
    ],
];

$config = $stateConfig[$state] ?? $stateConfig['active'];
?>

<?php if ($compact): ?>
    <!-- Compact badge-only display -->
    <span class="badge text-bg-<?= esc($config['badge_class']) ?>">
        <?= esc($config['icon']) ?> <?= esc($config['label']) ?>
    </span>
<?php else: ?>
    <!-- Detailed display with context -->
    <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-<?= esc($config['badge_class']) ?> p-2" style="font-size: 1.2em;">
            <?= esc($config['icon']) ?>
        </span>
        <div>
            <strong><?= esc($config['label']) ?></strong>
            <p class="text-muted small mb-0"><?= esc($config['description']) ?></p>
        </div>
    </div>
<?php endif; ?>
