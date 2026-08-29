<?php
// Verify migration status and check payment records
require_once 'ci4/vendor/autoload.php';
require_once 'ci4/public/index.php';

$db = db_connect();

// Check sample payment records
$payments = $db->query("SELECT payment_id, status FROM payments LIMIT 5")->getResultArray();
echo "=== PAYMENT STATUS CHECK ===\n";
echo "Sample payments:\n";
foreach ($payments as $payment) {
    echo "Payment " . $payment['payment_id'] . ": " . $payment['status'] . "\n";
}

// Check payment status distribution
$statusCounts = $db->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status")->getResultArray();
echo "\n=== STATUS DISTRIBUTION ===\n";
foreach ($statusCounts as $stat) {
    echo $stat['status'] . ": " . $stat['count'] . "\n";
}

// Check if migration table exists
$hasMigrations = $db->tableExists('migrations');
echo "\n=== MIGRATION TABLE EXISTS: " . ($hasMigrations ? 'YES' : 'NO') . " ===\n";

if ($hasMigrations) {
    $migrations = $db->query("SELECT batch, migration FROM migrations ORDER BY batch DESC LIMIT 5")->getResultArray();
    echo "Recent migrations:\n";
    foreach ($migrations as $mig) {
        echo "  Batch " . $mig['batch'] . ": " . $mig['migration'] . "\n";
    }
}

echo "\n=== VERIFICATION COMPLETE ===\n";
