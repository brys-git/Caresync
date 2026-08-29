<?php
// Alter payment status ENUM and update values

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'kaagapay_db2.0';

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "=== UPDATING PAYMENT STATUS ENUM AND VALUES ===\n\n";
    
    // First, save existing values as temporary backup
    $mysqli->query("ALTER TABLE payments ADD COLUMN status_backup VARCHAR(50) DEFAULT NULL AFTER status");
    $mysqli->query("UPDATE payments SET status_backup = status");
    echo "✓ Created status backup\n";
    
    // Alter the ENUM column to include both old and new values
    $result = $mysqli->query("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'awaiting_verification', 'verified', 'rejected') DEFAULT 'pending'");
    if ($result) {
        echo "✓ Altered ENUM to include new values\n";
    } else {
        echo "✗ Failed to alter ENUM: " . $mysqli->error . "\n";
    }
    
    // Now update the values (from backup since they got cleared)
    // pending -> awaiting_verification
    $mysqli->query("UPDATE payments SET status = 'awaiting_verification' WHERE status_backup = 'pending'");
    echo "✓ Updated 'pending' -> 'awaiting_verification' (" . $mysqli->affected_rows . " rows)\n";
    
    // paid -> verified
    $mysqli->query("UPDATE payments SET status = 'verified' WHERE status_backup = 'paid'");
    echo "✓ Updated 'paid' -> 'verified' (" . $mysqli->affected_rows . " rows)\n";
    
    // cancelled -> rejected
    $mysqli->query("UPDATE payments SET status = 'rejected' WHERE status_backup = 'cancelled'");
    echo "✓ Updated 'cancelled' -> 'rejected' (" . $mysqli->affected_rows . " rows)\n";
    
    // Drop backup column
    $mysqli->query("ALTER TABLE payments DROP COLUMN status_backup");
    echo "✓ Removed backup column\n";
    
    // Verify the changes
    echo "\n=== VERIFICATION AFTER UPDATE ===\n";
    $result = $mysqli->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? '[NULL]';
        echo "  " . $status . ": " . $row['count'] . "\n";
    }
    
    echo "\n=== MIGRATION COMPLETE ===\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
