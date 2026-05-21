<?php
$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Finding Joey Data ===\n\n";

// Search for Joey in users table
echo "Step 1: Search users table for 'Joey'\n";
$result = $db->query("SELECT user_id, first_name, last_name, email, role_id FROM users WHERE first_name LIKE '%Joey%' OR last_name LIKE '%Joey%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Found: " . $row['first_name'] . " " . $row['last_name'] . " (ID: " . $row['user_id'] . ", Role: " . $row['role_id'] . ")\n";
    }
} else {
    echo "  No Joey found in users table\n";
}

// Check all plan_holders
echo "\nStep 2: Check plan_holders table\n";
$result = $db->query("SELECT ph.plan_holder_id, ph.user_id, ph.status, ph.unique_identifier, u.first_name, u.last_name FROM plan_holders ph LEFT JOIN users u ON u.user_id = ph.user_id LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Plan Holder ID: " . $row['plan_holder_id'] . " | User: " . ($row['first_name'] ? $row['first_name'] . " " . $row['last_name'] : 'N/A') . " | Status: " . $row['status'] . "\n";
    }
} else {
    echo "  No plan_holders found\n";
}

// Check recent payments
echo "\nStep 3: Check recent payments\n";
$result = $db->query("
    SELECT p.payment_id, p.plan_id, p.amount, p.status, pl.plan_holder_id
    FROM payments p
    LEFT JOIN plans pl ON pl.plan_id = p.plan_id
    ORDER BY p.payment_id DESC LIMIT 5
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Payment ID: " . $row['payment_id'] . " | Amount: " . $row['amount'] . " | Status: " . $row['status'] . " | Plan Holder ID: " . ($row['plan_holder_id'] ?? 'NULL') . "\n";
    }
} else {
    echo "  No payments found\n";
}

echo "\n";
$db->close();
?>
