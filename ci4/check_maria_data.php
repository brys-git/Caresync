<?php
$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Checking Maria Santos (Plan Holder ID 10) ===\n\n";

// Get Maria's details
$result = $db->query("
    SELECT ph.plan_holder_id, ph.status, u.user_id, u.first_name, u.last_name, u.is_plan_holder, u.account_status
    FROM plan_holders ph
    JOIN users u ON u.user_id = ph.user_id
    WHERE ph.plan_holder_id = 10
");
$maria = $result->fetch_assoc();
echo "Maria Santos Details:\n";
echo "  Plan Holder ID: " . $maria['plan_holder_id'] . "\n";
echo "  Plan Holder Status: " . $maria['status'] . "\n";
echo "  User ID: " . $maria['user_id'] . "\n";
echo "  is_plan_holder: " . $maria['is_plan_holder'] . "\n";
echo "  account_status: " . $maria['account_status'] . "\n";

// Check her plans
echo "\nMaria's Plans:\n";
$result = $db->query("
    SELECT plan_id, status, start_date, months_paid, membership_state
    FROM plans
    WHERE plan_holder_id = 10
");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Plan ID: " . $row['plan_id'] . " | Status: " . $row['status'] . " | Membership State: " . $row['membership_state'] . " | Months Paid: " . $row['months_paid'] . "\n";
    }
} else {
    echo "  No plans found\n";
}

// Check her payments
echo "\nMaria's Payments:\n";
$result = $db->query("
    SELECT payment_id, amount, status, payment_date, created_at
    FROM payments
    WHERE plan_id IN (SELECT plan_id FROM plans WHERE plan_holder_id = 10)
    ORDER BY payment_id DESC
");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  Payment ID: " . $row['payment_id'] . " | Amount: " . $row['amount'] . " | Status: " . $row['status'] . " | Date: " . $row['payment_date'] . "\n";
    }
} else {
    echo "  No payments found\n";
}

echo "\n✓ If Maria's plan_holder status is 'inactive' but has 'paid' payments, auto-approval didn't work.\n";
echo "✓ If Maria's user.is_plan_holder is 0 or account_status is not 'verified', auto-approval didn't complete.\n";

$db->close();
?>
