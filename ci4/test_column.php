<?php
$mysqli = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

// Test 1: Select just one column
echo "Test 1: SELECT payments.months_covered FROM payments LIMIT 1" . PHP_EOL;
$result = $mysqli->query('SELECT payments.months_covered FROM payments LIMIT 1');
if ($result) {
    echo "SUCCESS: Column exists and can be selected" . PHP_EOL;
} else {
    echo "ERROR: " . $mysqli->error . PHP_EOL;
}

// Test 2: Select all columns
echo PHP_EOL . "Test 2: SHOW COLUMNS FROM payments" . PHP_EOL;
$result = $mysqli->query('SHOW COLUMNS FROM payments');
$found_months_covered = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'months_covered') {
        $found_months_covered = true;
        echo "Found months_covered: Type=" . $row['Type'] . PHP_EOL;
    }
}
if (!$found_months_covered) {
    echo "WARNING: months_covered column NOT FOUND" . PHP_EOL;
}

// Test 3: Try the exact select from the controller
echo PHP_EOL . "Test 3: Complex SELECT with joins" . PHP_EOL;
$query = 'SELECT payments.payment_id, payments.plan_id, payments.amount, payments.months_covered, payments.payment_date, payments.payment_method
FROM payments
INNER JOIN plans ON plans.plan_id = payments.plan_id
INNER JOIN plan_holders ON plan_holders.plan_holder_id = plans.plan_holder_id
LIMIT 1';
$result = $mysqli->query($query);
if ($result) {
    echo "SUCCESS: Complex query works" . PHP_EOL;
} else {
    echo "ERROR: " . $mysqli->error . PHP_EOL;
}

$mysqli->close();
?>
