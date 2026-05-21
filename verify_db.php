<?php
// Direct database verification without CodeIgniter bootstrap

// Database connection details
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'kaagapay_db2.0';

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "=== PAYMENT STATUS VERIFICATION ===\n\n";
    
    // Check sample payment records
    $result = $mysqli->query("SELECT payment_id, status FROM payments LIMIT 5");
    echo "Sample payments:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  Payment " . $row['payment_id'] . ": " . $row['status'] . "\n";
    }
    
    // Check payment status distribution
    $result = $mysqli->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    echo "\n=== STATUS DISTRIBUTION ===\n";
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['status'] . ": " . $row['count'] . "\n";
    }
    
    // Check if activity_logs table has expected columns
    echo "\n=== ACTIVITY_LOGS TABLE COLUMNS ===\n";
    $result = $mysqli->query("DESCRIBE activity_logs");
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n=== VERIFICATION COMPLETE ===\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
