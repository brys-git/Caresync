<?php
// Check what happened to payment statuses

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'kaagapay_db2.0';

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "=== DETAILED PAYMENT INSPECTION ===\n\n";
    
    $result = $mysqli->query("SELECT payment_id, status, LENGTH(status) as status_length FROM payments LIMIT 10");
    echo "Sample payments with status details:\n";
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? '[NULL]';
        echo "  Payment " . $row['payment_id'] . ": '" . $status . "' (length=" . $row['status_length'] . ")\n";
    }
    
    // Check if payment status column exists
    $result = $mysqli->query("DESCRIBE payments");
    echo "\n=== PAYMENTS TABLE COLUMNS ===\n";
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'status') {
            echo "  " . $row['Field'] . ": " . $row['Type'] . " (Null=" . $row['Null'] . ", Default=" . ($row['Default'] ?? 'NULL') . ")\n";
        }
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
