<?php
$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Checking Payments for Plan 6 ===\n\n";

$result = $db->query("
    SELECT payment_id, plan_id, amount, status, payment_date
    FROM payments
    WHERE plan_id = 6
    ORDER BY payment_id ASC
");

echo "All payments for plan_id=6:\n";
while ($row = $result->fetch_assoc()) {
    echo "  Payment ID: " . $row['payment_id'] . " | Amount: " . $row['amount'] . " | Status: " . $row['status'] . " | Date: " . $row['payment_date'] . "\n";
}

echo "\nEarliest payment (first payment ever for this plan):\n";
$result = $db->query("SELECT payment_id FROM payments WHERE plan_id = 6 ORDER BY payment_id ASC LIMIT 1");
$earliest = $result->fetch_assoc();
echo "  Payment ID: " . $earliest['payment_id'] . "\n";

echo "\n✓ The issue is that plan 6 already has earlier payments (likely from when Jose made his first payment).\n";
echo "✓ The auto-approval only triggers on the FIRST payment for an inactive holder.\n";
echo "✓ Since payment ID 9 was the first payment, payments 7, 8, 10, 11, 12 won't trigger auto-approval.\n\n";

echo "=== Finding an Inactive Plan Holder With No Payments ===\n\n";

// Find an inactive plan holder
$result = $db->query("
    SELECT ph.plan_holder_id, ph.user_id, u.first_name, u.last_name
    FROM plan_holders ph
    LEFT JOIN users u ON u.user_id = ph.user_id
    WHERE ph.status = 'inactive'
    LIMIT 10
");

$inactiveHolders = [];
while ($row = $result->fetch_assoc()) {
    $inactiveHolders[] = $row;
}

echo "Inactive plan holders:\n";
foreach ($inactiveHolders as $holder) {
    // Check if they have any plans
    $planResult = $db->query("SELECT plan_id FROM plans WHERE plan_holder_id = " . $holder['plan_holder_id']);
    $planCount = $planResult->num_rows;
    
    if ($planCount == 0) {
        echo "  ✓ Plan Holder ID: " . $holder['plan_holder_id'] . " | " . $holder['first_name'] . " " . $holder['last_name'] . " | Plans: " . $planCount . " (NO PLANS - AUTO-APPROVAL WILL NOT WORK)\n";
    } else {
        // Check if they have any payments
        $paymentResult = $db->query("
            SELECT COUNT(*) as count FROM payments p 
            WHERE p.plan_id IN (SELECT plan_id FROM plans WHERE plan_holder_id = " . $holder['plan_holder_id'] . ")
        ");
        $paymentRow = $paymentResult->fetch_assoc();
        $paymentCount = $paymentRow['count'];
        
        if ($paymentCount == 0) {
            echo "  ✓ Plan Holder ID: " . $holder['plan_holder_id'] . " | " . $holder['first_name'] . " " . $holder['last_name'] . " | Plans: " . $planCount . " | Payments: " . $paymentCount . " (GOOD FOR TESTING)\n";
        } else {
            echo "  - Plan Holder ID: " . $holder['plan_holder_id'] . " | " . $holder['first_name'] . " " . $holder['last_name'] . " | Plans: " . $planCount . " | Payments: " . $paymentCount . "\n";
        }
    }
}

$db->close();
?>
