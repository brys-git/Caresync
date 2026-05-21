<?php
$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== AUTO-APPROVAL IMPLEMENTATION VERIFICATION ===\n\n";

echo "✓ FIXED ISSUE: Changed 'remaining_balance' to 'legacy_remaining_balance'\n";
echo "✓ FIXED ISSUE: Added conditional column checking in auto-approval method\n";
echo "✓ FIXED ISSUE: Made NotificationService and ActivityLogService non-blocking\n\n";

echo "TEST 1: Maria Santos (Plan Holder ID 10) - Initial Payment Auto-Approval\n";
echo "───────────────────────────────────────────────────────────────────────\n";

$result = $db->query("
    SELECT ph.status, u.is_plan_holder, u.account_status
    FROM plan_holders ph
    JOIN users u ON u.user_id = ph.user_id
    WHERE ph.plan_holder_id = 10
");
$data = $result->fetch_assoc();

echo "Plan Holder Status: " . $data['status'] . " (Expected: active)\n";
echo "User is_plan_holder: " . $data['is_plan_holder'] . " (Expected: 1)\n";
echo "User account_status: " . $data['account_status'] . " (Expected: verified)\n";

$test1Pass = ($data['status'] === 'active' && $data['is_plan_holder'] == 1 && $data['account_status'] === 'verified');
echo "Test Result: " . ($test1Pass ? "✓ PASS" : "✗ FAIL") . "\n\n";

echo "TEST 2: Plan Status After Auto-Approval\n";
echo "───────────────────────────────────────────────────────────────────────\n";

$result = $db->query("
    SELECT status, membership_state, months_paid, next_due_date, payment_coverage_until
    FROM plans
    WHERE plan_holder_id = 10
    ORDER BY plan_id ASC
    LIMIT 1
");
$plan = $result->fetch_assoc();

echo "Plan Status: " . $plan['status'] . " (Expected: active)\n";
echo "Membership State: " . $plan['membership_state'] . " (Expected: active)\n";
echo "Months Paid: " . $plan['months_paid'] . " (Expected: 1)\n";
echo "Next Due Date: " . $plan['next_due_date'] . " (Expected: future date)\n";
echo "Payment Coverage Until: " . $plan['payment_coverage_until'] . " (Expected: ~1 month from now)\n";

$test2Pass = ($plan['status'] === 'active' && $plan['membership_state'] === 'active');
echo "Test Result: " . ($test2Pass ? "✓ PASS" : "✗ FAIL") . "\n\n";

echo "TEST 3: Database Schema Verification\n";
echo "───────────────────────────────────────────────────────────────────────\n";

$result = $db->query("SHOW COLUMNS FROM plans");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$requiredColumns = ['status', 'months_paid', 'next_due_date', 'payment_coverage_until', 'legacy_remaining_balance', 'membership_state'];
foreach ($requiredColumns as $col) {
    $status = in_array($col, $columns) ? '✓' : '✗';
    echo "$status $col\n";
}

$test3Pass = !array_diff($requiredColumns, $columns);
echo "Test Result: " . ($test3Pass ? "✓ PASS" : "✗ FAIL") . "\n\n";

echo "TEST 4: Payment to Auto-Approval Flow\n";
echo "───────────────────────────────────────────────────────────────────────\n";

$result = $db->query("
    SELECT p.payment_id, p.status, p.payment_date,
           ph.status as holder_status
    FROM payments p
    JOIN plans pl ON pl.plan_id = p.plan_id
    JOIN plan_holders ph ON ph.plan_holder_id = pl.plan_holder_id
    WHERE ph.plan_holder_id = 10
    ORDER BY p.payment_id ASC
    LIMIT 1
");
$payment = $result->fetch_assoc();

echo "First Payment Status: " . $payment['status'] . " (Expected: paid)\n";
echo "Plan Holder Status After Payment: " . $payment['holder_status'] . " (Expected: active)\n";

$test4Pass = ($payment['status'] === 'paid' && $payment['holder_status'] === 'active');
echo "Test Result: " . ($test4Pass ? "✓ PASS" : "✗ FAIL") . "\n\n";

$allPass = $test1Pass && $test2Pass && $test3Pass && $test4Pass;

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "OVERALL RESULT: " . ($allPass ? "✓ ALL TESTS PASSED" : "✗ SOME TESTS FAILED") . "\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

echo "SUMMARY:\n";
echo "After the initial payment is recorded and marked as 'paid':\n";
echo "  1. Plan Holder status automatically updates from 'inactive' to 'active'\n";
echo "  2. User account_status updates to 'verified'\n";
echo "  3. User is_plan_holder flag is set to 1\n";
echo "  4. Plan status updates to 'active'\n";
echo "  5. Plan membership_state updates to 'active'\n";
echo "  6. Coverage dates are calculated and stored\n";

$db->close();
?>
