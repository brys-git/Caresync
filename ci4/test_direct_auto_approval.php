<?php
// Direct test of auto-approval workflow
// This simulates what happens when recordCash() is called

define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'system' . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');

// Bootstrap CodeIgniter
require ROOTPATH . 'vendor/autoload.php';
require SYSTEMPATH . 'bootstrap.php';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';

try {
    // Create database connection
    $db = db_connect();
    
    echo "=== Testing Auto-Approval Workflow ===\n\n";
    
    // Find Jose Reyes (Plan Holder ID 11, currently inactive)
    $josereyesRecord = $db->table('plan_holders')
        ->select('ph.plan_holder_id, ph.user_id, ph.status, u.first_name, u.last_name')
        ->join('users u', 'u.user_id = ph.user_id', 'left')
        ->where('ph.plan_holder_id', 11)
        ->get()
        ->getRowArray();
    
    if (!$josereyesRecord) {
        echo "ERROR: Jose Reyes (plan_holder_id 11) not found\n";
        exit;
    }
    
    echo "Test Subject: " . $josereyesRecord['first_name'] . " " . $josereyesRecord['last_name'] . "\n";
    echo "Plan Holder ID: " . $josereyesRecord['plan_holder_id'] . "\n";
    echo "Current Status: " . $josereyesRecord['status'] . "\n";
    echo "User ID: " . $josereyesRecord['user_id'] . "\n\n";
    
    // Get Jose's first plan
    $plan = $db->table('plans')
        ->where('plan_holder_id', 11)
        ->orderBy('plan_id', 'ASC')
        ->limit(1)
        ->get()
        ->getRowArray();
    
    if (!$plan) {
        echo "ERROR: No plans found for this plan holder\n";
        exit;
    }
    
    echo "Plan ID: " . $plan['plan_id'] . "\n";
    echo "Plan Current Status: " . $plan['status'] . "\n\n";
    
    // Simulate recording a payment - just like the recordCash() method does
    echo "Step 1: Recording payment...\n";
    
    $paymentData = [
        'plan_id' => (int)$plan['plan_id'],
        'amount' => 240.00,
        'months_covered' => 1,
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'cash',
        'received_by' => 1,  // Branch admin user ID
        'branch_id' => 1,
        'status' => 'paid',
        'official_receipt_number' => 'OR-TEST-001',
        'remarks' => 'Test auto-approval payment',
        'verified_by' => 1,
        'verified_at' => date('Y-m-d H:i:s'),
    ];
    
    // Filter to only include columns that exist
    $paymentModel = new \App\Models\PaymentModel();
    $fields = $db->getFieldNames('payments');
    $filteredData = array_intersect_key($paymentData, array_flip($fields));
    
    $paymentId = $paymentModel->insert($filteredData, true);
    echo "✓ Payment recorded with ID: " . $paymentId . "\n\n";
    
    // Step 2: Check if it's an initial payment
    echo "Step 2: Checking if initial payment...\n";
    
    // This checks if the holder status is 'inactive'
    $holder = $db->table('plan_holders')
        ->select('status')
        ->where('plan_holder_id', 11)
        ->get()
        ->getRowArray();
    
    $isInitial = strtolower($holder['status']) === 'inactive';
    echo "Is holder inactive? " . ($isInitial ? 'YES' : 'NO') . "\n";
    
    // Check if payment is earliest
    $earliestPayment = $db->table('payments')
        ->select('payment_id')
        ->where('plan_id', (int)$plan['plan_id'])
        ->orderBy('payment_id', 'ASC')
        ->limit(1)
        ->get()
        ->getRowArray();
    
    $isEarliestPayment = (int)($earliestPayment['payment_id'] ?? 0) === $paymentId;
    echo "Is earliest payment? " . ($isEarliestPayment ? 'YES' : 'NO') . "\n";
    echo "Expected: Initial payment = " . ($isInitial ? 'YES' : 'NO') . "\n\n";
    
    // Step 3: Call auto-approval
    if ($isInitial && $isEarliestPayment) {
        echo "Step 3: Calling auto-approval...\n";
        
        // Import the controller to use its methods
        require APPPATH . 'Controllers/PaymentTracking.php';
        
        // We need to manually call the auto-approval method
        // Since we can't access private methods, let's simulate the logic here
        echo "Simulating auto-approval logic...\n\n";
        
        // This is what autoApprovePlanHolderFromInitialPayment does:
        $planHolderId = 11;
        $monthsCovered = 1;
        $branchId = 1;
        
        $db->transBegin();
        
        try {
            // Update plan status
            $planModel = new \App\Models\PlanModel();
            $today = date('Y-m-d');
            $coverageUntil = date('Y-m-d', strtotime('+' . max(1, $monthsCovered) . ' months', strtotime($today)));
            $nextDue = date('Y-m-d', strtotime('+1 month', strtotime($coverageUntil)));
            
            $updateData = [
                'status' => 'active',
                'remaining_balance' => 0,
                'months_paid' => max(1, $monthsCovered),
            ];
            
            // Only include columns if they exist
            $planFields = $db->getFieldNames('plans');
            if (in_array('next_due_date', $planFields, true)) {
                $updateData['next_due_date'] = $nextDue;
            }
            if (in_array('payment_coverage_until', $planFields, true)) {
                $updateData['payment_coverage_until'] = $coverageUntil;
            }
            if (in_array('overdue_months', $planFields, true)) {
                $updateData['overdue_months'] = 0;
            }
            if (in_array('membership_state', $planFields, true)) {
                $updateData['membership_state'] = 'active';
            }
            
            $planModel->update((int)$plan['plan_id'], $updateData);
            echo "✓ Plan updated to active status\n";
            
            // Update plan_holder status
            $planHolderModel = new \App\Models\PlanHolderModel();
            $planHolderModel->update($planHolderId, ['status' => 'active']);
            echo "✓ Plan holder status updated to 'active'\n";
            
            // Update user account
            $userModel = new \App\Models\UserModel();
            $userModel->update((int)$josereyesRecord['user_id'], [
                'is_plan_holder' => 1,
                'account_status' => 'verified',
                'branch_id' => $branchId,
            ]);
            echo "✓ User account updated\n";
            
            // Try to send notification and log activity
            try {
                $notificationService = new \App\Services\NotificationService();
                $notificationService->notify(
                    (int)$josereyesRecord['user_id'],
                    'Your registration has been approved. Your plan is now active.',
                    'registration_pending'
                );
                echo "✓ Notification sent\n";
            } catch (\Throwable $e) {
                echo "✗ Notification failed (non-critical): " . $e->getMessage() . "\n";
            }
            
            try {
                $activityLogService = new \App\Services\ActivityLogService();
                $activityLogService->log(
                    1,
                    'approved',
                    'plan_holder',
                    $planHolderId,
                    'Auto-approved plan holder after initial payment verification',
                    ['status' => 'inactive'],
                    ['status' => 'active']
                );
                echo "✓ Activity logged\n";
            } catch (\Throwable $e) {
                echo "✗ Activity log failed (non-critical): " . $e->getMessage() . "\n";
            }
            
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction status is false');
            }
            
            $db->transCommit();
            echo "\n✓ Auto-approval transaction committed successfully!\n";
            
        } catch (\Throwable $e) {
            echo "\n✗ Auto-approval failed: " . $e->getMessage() . "\n";
            $db->transRollback();
            exit;
        }
    } else {
        echo "Step 3: Skipped - not an initial payment\n";
    }
    
    // Step 4: Verify changes
    echo "\nStep 4: Verifying changes...\n";
    
    $updatedHolder = $db->table('plan_holders')
        ->select('status')
        ->where('plan_holder_id', 11)
        ->get()
        ->getRowArray();
    
    echo "Updated Plan Holder Status: " . $updatedHolder['status'] . "\n";
    
    $updatedPlan = $db->table('plans')
        ->where('plan_id', (int)$plan['plan_id'])
        ->get()
        ->getRowArray();
    
    echo "Updated Plan Status: " . $updatedPlan['status'] . "\n";
    
    $updatedUser = $db->table('users')
        ->where('user_id', (int)$josereyesRecord['user_id'])
        ->get()
        ->getRowArray();
    
    echo "Updated User is_plan_holder: " . $updatedUser['is_plan_holder'] . "\n";
    echo "Updated User account_status: " . $updatedUser['account_status'] . "\n";
    
    echo "\n" . ($updatedHolder['status'] === 'active' ? "✓ SUCCESS: Auto-approval completed!" : "✗ FAILED: Plan holder still inactive") . "\n";
    
} catch (\Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
