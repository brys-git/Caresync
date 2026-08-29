<?php
$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Analyzing Maria Santos Auto-Approval ===\n\n";

// Maria is plan_holder_id 10
$holderResult = $db->query("
    SELECT ph.plan_holder_id, ph.status, ph.user_id, u.first_name, u.last_name, u.is_plan_holder, u.account_status
    FROM plan_holders ph
    JOIN users u ON u.user_id = ph.user_id
    WHERE ph.plan_holder_id = 10
");
$maria = $holderResult->fetch_assoc();

echo "Current Status:\n";
echo "  Plan Holder Status: " . $maria['status'] . "\n";
echo "  User is_plan_holder: " . $maria['is_plan_holder'] . "\n";
echo "  User account_status: " . $maria['account_status'] . "\n\n";

// Get Maria's plan
$planResult = $db->query("
    SELECT plan_id, status, months_paid, membership_state, start_date
    FROM plans
    WHERE plan_holder_id = 10
    ORDER BY plan_id ASC
    LIMIT 1
");
$plan = $planResult->fetch_assoc();

echo "Maria's First Plan:\n";
echo "  Plan ID: " . $plan['plan_id'] . "\n";
echo "  Plan Status: " . $plan['status'] . "\n";
echo "  Months Paid: " . $plan['months_paid'] . "\n";
echo "  Membership State: " . $plan['membership_state'] . "\n";
echo "  Start Date: " . $plan['start_date'] . "\n\n";

// Get Maria's first payment
$paymentResult = $db->query("
    SELECT payment_id, amount, status, payment_date, verified_at, verified_by
    FROM payments
    WHERE plan_id = " . $plan['plan_id'] . "
    ORDER BY payment_id ASC
    LIMIT 1
");
$firstPayment = $paymentResult->fetch_assoc();

echo "Maria's First Payment:\n";
echo "  Payment ID: " . $firstPayment['payment_id'] . "\n";
echo "  Amount: " . $firstPayment['amount'] . "\n";
echo "  Status: " . $firstPayment['status'] . "\n";
echo "  Payment Date: " . $firstPayment['payment_date'] . "\n";
echo "  Verified At: " . $firstPayment['verified_at'] . "\n";
echo "  Verified By: " . $firstPayment['verified_by'] . "\n\n";

echo "ANALYSIS:\n";
echo "✓ Maria's first payment ID " . $firstPayment['payment_id'] . " has status '" . $firstPayment['status'] . "'\n";

if ($firstPayment['status'] === 'paid' && $maria['status'] === 'inactive') {
    echo "✗ AUTO-APPROVAL DID NOT WORK: Payment is 'paid' but plan_holder status is still 'inactive'\n";
    echo "✗ This means autoApprovePlanHolderFromInitialPayment() was not called or failed silently\n";
} elseif ($maria['status'] === 'active') {
    echo "✓ AUTO-APPROVAL WORKED: Plan holder status updated to 'active'\n";
} else {
    echo "? UNKNOWN STATE: Plan holder status is '" . $maria['status'] . "'\n";
}

$db->close();
?>
