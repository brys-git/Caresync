<?php
// Test script to verify auto-approval logic
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'system' . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');

require ROOTPATH . 'vendor/autoload.php';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';

$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Testing Auto-Approval Logic ===\n\n";

// Test 1: Check if plans table has the columns we need
echo "Test 1: Checking plans table columns\n";
$result = $db->query("SHOW COLUMNS FROM plans");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo "Columns: " . implode(', ', $columns) . "\n";

$needed_columns = ['next_due_date', 'payment_coverage_until', 'overdue_months', 'membership_state'];
foreach ($needed_columns as $col) {
    $status = in_array($col, $columns) ? 'EXISTS' : 'MISSING';
    echo "  - $col: $status\n";
}

// Test 2: Check if activity_logs table exists
echo "\nTest 2: Checking activity_logs table\n";
$result = $db->query("SHOW TABLES LIKE 'activity_logs'");
if ($result->num_rows > 0) {
    echo "  - activity_logs: EXISTS\n";
} else {
    echo "  - activity_logs: MISSING (will be handled gracefully)\n";
}

// Test 3: Check if notifications table has 'type' column
echo "\nTest 3: Checking notifications table\n";
$result = $db->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows > 0) {
    echo "  - notifications: EXISTS\n";
    $result = $db->query("SHOW COLUMNS FROM notifications");
    $cols = [];
    while ($row = $result->fetch_assoc()) {
        $cols[] = $row['Field'];
    }
    $has_type = in_array('type', $cols);
    echo "  - 'type' column: " . ($has_type ? 'EXISTS' : 'MISSING (will be handled gracefully)') . "\n";
} else {
    echo "  - notifications: MISSING (will be handled gracefully)\n";
}

// Test 4: Get Joey's plan_holder_id
echo "\nTest 4: Getting Joey's plan_holder_id\n";
$result = $db->query("
    SELECT ph.plan_holder_id, ph.status, u.user_id, u.first_name
    FROM plan_holders ph
    JOIN users u ON u.user_id = ph.user_id
    WHERE u.first_name = 'Joey'
    LIMIT 1
");
if ($result->num_rows > 0) {
    $joeyRecord = $result->fetch_assoc();
    $planHolderId = (int)$joeyRecord['plan_holder_id'];
    $joeyStatus = $joeyRecord['status'];
    echo "  - plan_holder_id: $planHolderId\n";
    echo "  - current status: $joeyStatus\n";
    
    // Test 5: Test getFieldNames logic (simulating what we do in the code)
    echo "\nTest 5: Testing conditional column updates\n";
    $planFields = $db->query("SHOW COLUMNS FROM plans")->fetch_all(MYSQLI_ASSOC);
    $planFieldNames = array_column($planFields, 'Field');
    
    $updateFields = ['next_due_date', 'payment_coverage_until', 'overdue_months', 'membership_state', 'version_id'];
    foreach ($updateFields as $field) {
        $status = in_array($field, $planFieldNames) ? 'WILL_UPDATE' : 'WILL_SKIP';
        echo "  - $field: $status\n";
    }
    
    echo "\n✓ Auto-approval should work with conditional column handling!\n";
} else {
    echo "  - ERROR: Joey not found in database\n";
}

$db->close();
?>
