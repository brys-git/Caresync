<?php
/**
 * Status Badge Component
 * 
 * Renders a standardized status badge with consistent colors
 * 
 * @param string $status Status value (active, pending, inactive, approved, rejected, etc.)
 * @param string $label Optional label (if different from status)
 * @param string $class Optional additional CSS classes
 * 
 * Usage:
 * <?= view('components/status_badge', ['status' => 'active']) ?>
 * <?= view('components/status_badge', ['status' => 'pending', 'label' => 'Under Review']) ?>
 */

$status = strtolower(trim((string) ($status ?? 'inactive')));
$label = (string) ($label ?? ucfirst($status));
$class = (string) ($class ?? '');

// Standard status color mapping
$statusColorMap = [
    // Positive states
    'active' => 'success',
    'approved' => 'success',
    'completed' => 'success',
    'paid' => 'success',
    'verified' => 'success',
    'available' => 'success',
    
    // Warning states
    'pending' => 'warning',
    'review' => 'warning',
    'delinquent' => 'warning',
    'overdue' => 'warning',
    'processing' => 'warning',
    
    // Error states
    'inactive' => 'danger',
    'rejected' => 'danger',
    'cancelled' => 'danger',
    'suspended' => 'danger',
    'expired' => 'danger',
    'failed' => 'danger',
    
    // Info states
    'new' => 'info',
    'draft' => 'info',
    'unknown' => 'secondary',
];

$bgClass = $statusColorMap[$status] ?? 'secondary';
$fullClass = "badge text-bg-{$bgClass} {$class}";
?>
<span class="<?= esc($fullClass) ?>"><?= esc($label) ?></span>
