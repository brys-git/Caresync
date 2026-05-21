<?php
// Simple direct test without CodeIgniter bootstrap

$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Testing Auto-Approval Workflow (Direct) ===\n\n";

// Find Jose Reyes (Plan Holder ID 11, currently inactive)
$result = $db->query("
    SELECT ph.plan_holder_id, ph.user_id, ph.status, u.first_name, u.last_name
    FROM plan_holders ph
    LEFT JOIN users u ON u.user_id = ph.user_id
    WHERE ph.plan_holder_id = 11
");

$josereyesRecord = $result->fetch_assoc();

if (!$josereyesRecord) {
    echo "ERROR: Jose Reyes (plan_holder_id 11) not found\n";
    exit;
}

echo "Test Subject: " . $josereyesRecord['first_name'] . " " . $josereyesRecord['last_name'] . "\n";
echo "Plan Holder ID: " . $josereyesRecord['plan_holder_id'] . "\n";
echo "Current Status: " . $josereyesRecord['status'] . "\n";
echo "User ID: " . $josereyesRecord['user_id'] . "\n\n";

// Get Jose's first plan
$result = $db->query("
    SELECT plan_id, status, monthly_fee
    FROM plans
    WHERE plan_holder_id = 11
    ORDER BY plan_id ASC
    LIMIT 1
");

$plan = $result->fetch_assoc();

if (!$plan) {
    echo "ERROR: No plans found for this plan holder\n";
    exit;
}

echo "Plan ID: " . $plan['plan_id'] . "\n";
echo "Plan Current Status: " . $plan['status'] . "\n\n";

// Simulate recording a payment
echo "Step 1: Recording payment...\n";

$paymentDate = date('Y-m-d');
$verifiedAt = date('Y-m-d H:i:s');
$receipt = 'OR-TEST-' . date('YmdHis');
$branchAdminId = 6;  // regine's user_id

$sql = "
    INSERT INTO payments (plan_id, amount, months_covered, payment_date, payment_method, received_by, branch_id, status, official_receipt_number, remarks, verified_by, verified_at)
    VALUES (" . (int)$plan['plan_id'] . ", 240.00, 1, '" . $paymentDate . "', 'cash', " . $branchAdminId . ", 1, 'paid', '" . $db->escape_string($receipt) . "', 'Test auto-approval payment', " . $branchAdminId . ", '" . $verifiedAt . "')
";

if (!$db->query($sql)) {
    die("ERROR inserting payment: " . $db->error);
}

$paymentId = $db->insert_id;
echo "✓ Payment recorded with ID: " . $paymentId . "\n\n";

// Step 2: Check if it's an initial payment
echo "Step 2: Checking if initial payment...\n";

// Check if holder is inactive
$result = $db->query("SELECT status FROM plan_holders WHERE plan_holder_id = 11");
$holder = $result->fetch_assoc();
$isInitial = strtolower($holder['status']) === 'inactive';
echo "Is holder inactive? " . ($isInitial ? 'YES' : 'NO') . "\n";

// Check if payment is earliest
$result = $db->query("SELECT payment_id FROM payments WHERE plan_id = " . $plan['plan_id'] . " ORDER BY payment_id ASC LIMIT 1");
$earliestPayment = $result->fetch_assoc();
$isEarliestPayment = (int)($earliestPayment['payment_id'] ?? 0) === $paymentId;
echo "Is earliest payment? " . ($isEarliestPayment ? 'YES' : 'NO') . "\n";
echo "Is Initial Payment: " . ($isInitial ? 'YES' : 'NO') . "\n\n";

// Step 3: Perform auto-approval
if ($isInitial && $isEarliestPayment) {
    echo "Step 3: Performing auto-approval...\n";
    
    $db->begin_transaction();
    
    try {
        // Update plan status
        $today = date('Y-m-d');
        $coverageUntil = date('Y-m-d', strtotime('+1 months', strtotime($today)));
        $nextDue = date('Y-m-d', strtotime('+1 month', strtotime($coverageUntil)));
        
        $sql = "
            UPDATE plans
            SET status = 'active',
                remaining_balance = 0,
                months_paid = 1,
                next_due_date = '" . $nextDue . "',
                payment_coverage_until = '" . $coverageUntil . "',
                overdue_months = 0,
                membership_state = 'active'
            WHERE plan_id = " . (int)$plan['plan_id'] . "
        ";
        
        if (!$db->query($sql)) {
            throw new Exception("Failed to update plan: " . $db->error);
        }
        echo "✓ Plan updated to active status\n";
        
        // Update plan_holder status
        if (!$db->query("UPDATE plan_holders SET status = 'active' WHERE plan_holder_id = 11")) {
            throw new Exception("Failed to update plan holder: " . $db->error);
        }
        echo "✓ Plan holder status updated to 'active'\n";
        
        // Update user account
        if (!$db->query("UPDATE users SET is_plan_holder = 1, account_status = 'verified', branch_id = 1 WHERE user_id = " . (int)$josereyesRecord['user_id'])) {
            throw new Exception("Failed to update user: " . $db->error);
        }
        echo "✓ User account updated\n";
        
        // Try to send notification
        try {
            $title = 'Registration Approved';
            $message = 'Your registration has been approved. Your plan is now active.';
            $type = 'registration_pending';
            $sql = "
                INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                VALUES (" . (int)$josereyesRecord['user_id'] . ", '" . $db->escape_string($title) . "', '" . $db->escape_string($message) . "', '" . $db->escape_string($type) . "', 0, NOW())
            ";
            if ($db->query($sql)) {
                echo "✓ Notification sent\n";
            }
        } catch (Exception $e) {
            echo "✗ Notification failed (non-critical): " . $e->getMessage() . "\n";
        }
        
        // Try to log activity
        try {
            $description = 'Auto-approved plan holder after initial payment verification';
            $oldData = json_encode(['status' => 'inactive']);
            $newData = json_encode(['status' => 'active']);
            $sql = "
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, old_data, new_data, created_at)
                VALUES (6, 'approved', 'plan_holder', 11, '" . $db->escape_string($description) . "', '" . $db->escape_string($oldData) . "', '" . $db->escape_string($newData) . "', NOW())
            ";
            if ($db->query($sql)) {
                echo "✓ Activity logged\n";
            }
        } catch (Exception $e) {
            echo "✗ Activity log failed (non-critical): " . $e->getMessage() . "\n";
        }
        
        $db->commit();
        echo "\n✓ Auto-approval transaction committed successfully!\n";
        
    } catch (Exception $e) {
        echo "\n✗ Auto-approval failed: " . $e->getMessage() . "\n";
        $db->rollback();
        exit;
    }
} else {
    echo "Step 3: Skipped - not an initial payment\n";
}

// Step 4: Verify changes
echo "\nStep 4: Verifying changes...\n";

$result = $db->query("SELECT status FROM plan_holders WHERE plan_holder_id = 11");
$updatedHolder = $result->fetch_assoc();
echo "Updated Plan Holder Status: " . $updatedHolder['status'] . "\n";

$result = $db->query("SELECT status FROM plans WHERE plan_id = " . $plan['plan_id']);
$updatedPlan = $result->fetch_assoc();
echo "Updated Plan Status: " . $updatedPlan['status'] . "\n";

$result = $db->query("SELECT is_plan_holder, account_status FROM users WHERE user_id = " . $josereyesRecord['user_id']);
$updatedUser = $result->fetch_assoc();
echo "Updated User is_plan_holder: " . $updatedUser['is_plan_holder'] . "\n";
echo "Updated User account_status: " . $updatedUser['account_status'] . "\n";

echo "\n" . ($updatedHolder['status'] === 'active' ? "✓ SUCCESS: Auto-approval completed!" : "✗ FAILED: Plan holder still inactive") . "\n";

$db->close();
?>
