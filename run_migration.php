<?php
// Manually run migration to update payment statuses

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'kaagapay_db2.0';

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "=== RUNNING PAYMENT STATUS MIGRATION ===\n\n";
    
    // Update pending -> awaiting_verification
    $result = $mysqli->query("UPDATE payments SET status = 'awaiting_verification' WHERE status = 'pending'");
    if ($result) {
        echo "✓ Updated 'pending' -> 'awaiting_verification' (" . $mysqli->affected_rows . " rows)\n";
    } else {
        echo "✗ Failed: " . $mysqli->error . "\n";
    }
    
    // Update paid -> verified
    $result = $mysqli->query("UPDATE payments SET status = 'verified' WHERE status = 'paid'");
    if ($result) {
        echo "✓ Updated 'paid' -> 'verified' (" . $mysqli->affected_rows . " rows)\n";
    } else {
        echo "✗ Failed: " . $mysqli->error . "\n";
    }
    
    // Update cancelled -> rejected
    $result = $mysqli->query("UPDATE payments SET status = 'rejected' WHERE status = 'cancelled'");
    if ($result) {
        echo "✓ Updated 'cancelled' -> 'rejected' (" . $mysqli->affected_rows . " rows)\n";
    } else {
        echo "✗ Failed: " . $mysqli->error . "\n";
    }
    
    // Verify the changes
    echo "\n=== VERIFICATION AFTER UPDATE ===\n";
    $result = $mysqli->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['status'] . ": " . $row['count'] . "\n";
    }
    
    echo "\n=== MIGRATION COMPLETE ===\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
