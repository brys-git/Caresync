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
    <?= cs_status($state, $config['label']) ?>
<?php else: ?>
    <!-- Detailed display with context -->
    <div class="d-flex align-items-center gap-2">
        <?= cs_status($state, $config['icon']) ?>
        <div>
            <strong><?= esc($config['label']) ?></strong>
            <p class="cs-muted small mb-0"><?= esc($config['description']) ?></p>
        </div>
    </div>
<?php endif; ?>
