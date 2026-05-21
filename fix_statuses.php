<?php
// Direct database fix using PHP - set proper payment statuses

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'kaagapay_db2.0';

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "=== FIXING PAYMENT STATUSES ===\n\n";
    
    // First get count of empty statuses
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM payments WHERE status IS NULL OR status = ''");
    $row = $result->fetch_assoc();
    $emptyCount = $row['cnt'];
    
    echo "Found " . $emptyCount . " payments with empty/null status\n";
    
    // Set all empty statuses to 'verified' (assuming they were successful payments)
    $result = $mysqli->query("UPDATE payments SET status = 'verified' WHERE status IS NULL OR status = ''");
    if ($result) {
        echo "✓ Updated empty statuses to 'verified' (" . $mysqli->affected_rows . " rows)\n";
    } else {
        echo "✗ Failed: " . $mysqli->error . "\n";
    }
    
    // Verify the changes
    echo "\n=== VERIFICATION ===\n";
    $result = $mysqli->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? '[NULL]';
        echo "  " . $status . ": " . $row['count'] . "\n";
    }
    
    echo "\n=== COMPLETE ===\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
