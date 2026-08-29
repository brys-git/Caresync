<?php
// Manually test the updated autoApprovePlanHolderFromInitialPayment logic

$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Manually Triggering Auto-Approval for Maria Santos ===\n\n";

$planHolderId = 10;
$branchId = 1;

// Get Maria's data
$holderResult = $db->query("
    SELECT plan_holder_id, user_id, status
    FROM plan_holders
    WHERE plan_holder_id = " . $planHolderId . "
");
$holder = $holderResult->fetch_assoc();

if (!$holder) {
    die("ERROR: Plan holder not found");
}

echo "Starting auto-approval...\n";
echo "  Holder Status: " . $holder['status'] . "\n";
echo "  User ID: " . $holder['user_id'] . "\n\n";

// Check if holder is inactive
if (strtolower($holder['status']) !== 'inactive') {
    die("Holder is not inactive, skipping auto-approval");
}

// Get the first plan
$planResult = $db->query("
    SELECT plan_id, monthly_fee
    FROM plans
    WHERE plan_holder_id = " . $planHolderId . "
    ORDER BY plan_id ASC
    LIMIT 1
");
$plan = $planResult->fetch_assoc();

if (!$plan) {
    die("ERROR: Plan not found");
}

echo "Plan to update:\n";
echo "  Plan ID: " . $plan['plan_id'] . "\n";
echo "  Monthly Fee: " . $plan['monthly_fee'] . "\n\n";

// Simulate the auto-approval logic from the updated method
echo "Executing auto-approval transaction...\n\n";

$db->begin_transaction();

try {
    $monthsCovered = 1;
    $today = date('Y-m-d');
    $coverageUntil = date('Y-m-d', strtotime('+' . max(1, $monthsCovered) . ' months', strtotime($today)));
    $nextDue = date('Y-m-d', strtotime('+1 month', strtotime($coverageUntil)));
    
    // Get all fields in plans table
    $fieldsResult = $db->query("SHOW COLUMNS FROM plans");
    $planFields = [];
    while ($row = $fieldsResult->fetch_assoc()) {
        $planFields[] = $row['Field'];
    }
    
    echo "Available columns in plans table:\n";
    foreach ($planFields as $field) {
        echo "  - " . $field . "\n";
    }
    echo "\n";
    
    // Build the update data, only including fields that exist
    $updateData = [];
    $updateData['status'] = 'active';
    $updateData['months_paid'] = 1;
    
    // Optional fields
    if (in_array('version_id', $planFields)) {
        $updateData['version_id'] = 1;  // Default value
    }
    if (in_array('next_due_date', $planFields)) {
        $updateData['next_due_date'] = $nextDue;
    }
    if (in_array('payment_coverage_until', $planFields)) {
        $updateData['payment_coverage_until'] = $coverageUntil;
    }
    if (in_array('overdue_months', $planFields)) {
        $updateData['overdue_months'] = 0;
    }
    if (in_array('membership_state', $planFields)) {
        $updateData['membership_state'] = 'active';
    }
    if (in_array('legacy_remaining_balance', $planFields)) {
        $updateData['legacy_remaining_balance'] = 0;
    }
    
    // Build the UPDATE SQL
    $setClause = [];
    foreach ($updateData as $field => $value) {
        if (is_null($value)) {
            $setClause[] = "`" . $field . "` = NULL";
        } else if (is_numeric($value)) {
            $setClause[] = "`" . $field . "` = " . $value;
        } else {
            $setClause[] = "`" . $field . "` = '" . $db->escape_string($value) . "'";
        }
    }
    
    $sql = "UPDATE plans SET " . implode(", ", $setClause) . " WHERE plan_id = " . $plan['plan_id'];
    
    echo "Update Plan SQL:\n";
    echo "  " . substr($sql, 0, 100) . "...\n\n";
    
    if (!$db->query($sql)) {
        throw new Exception("Failed to update plan: " . $db->error);
    }
    echo "✓ Plan updated to active\n";
    
    // Update plan_holder status
    if (!$db->query("UPDATE plan_holders SET status = 'active' WHERE plan_holder_id = " . $planHolderId)) {
        throw new Exception("Failed to update plan_holder: " . $db->error);
    }
    echo "✓ Plan holder status updated to 'active'\n";
    
    // Update user
    if (!$db->query("UPDATE users SET is_plan_holder = 1, account_status = 'verified', branch_id = " . $branchId . " WHERE user_id = " . $holder['user_id'])) {
        throw new Exception("Failed to update user: " . $db->error);
    }
    echo "✓ User updated (is_plan_holder=1, account_status='verified')\n";
    
    // Commit transaction
    $db->commit();
    echo "\n✓ Transaction committed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    $db->rollback();
    exit;
}

// Verify changes
echo "\nVerifying changes...\n";

$holderResult = $db->query("SELECT status FROM plan_holders WHERE plan_holder_id = " . $planHolderId);
$updated = $holderResult->fetch_assoc();
echo "  Plan Holder Status: " . $updated['status'] . " (should be 'active')\n";

$planResult = $db->query("SELECT status FROM plans WHERE plan_id = " . $plan['plan_id']);
$updatedPlan = $planResult->fetch_assoc();
echo "  Plan Status: " . $updatedPlan['status'] . " (should be 'active')\n";

$userResult = $db->query("SELECT is_plan_holder, account_status FROM users WHERE user_id = " . $holder['user_id']);
$updatedUser = $userResult->fetch_assoc();
echo "  User is_plan_holder: " . $updatedUser['is_plan_holder'] . " (should be 1)\n";
echo "  User account_status: " . $updatedUser['account_status'] . " (should be 'verified')\n";

echo "\n" . ($updated['status'] === 'active' ? "✓ SUCCESS!" : "✗ FAILED") . "\n";

$db->close();
?>
