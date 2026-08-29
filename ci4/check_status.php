<?php
require 'vendor/autoload.php';
require 'app/Config/Constants.php';
$_ENV['CI_ENVIRONMENT'] = 'development';

$db = \Config\Database::connect();

// Get Joey's plan holder info
$holder = $db->query("
    SELECT ph.plan_holder_id, ph.status, ph.unique_identifier, u.first_name, u.last_name, u.account_status
    FROM plan_holders ph
    INNER JOIN users u ON u.user_id = ph.user_id
    WHERE u.first_name = 'Joey'
    LIMIT 1
")->getRowArray();

echo "=== Joey's Current Plan Holder Status ===\n";
if ($holder) {
    echo "Plan Holder ID: {$holder['plan_holder_id']}\n";
    echo "Status: {$holder['status']}\n";
    echo "Account Status: {$holder['account_status']}\n";
    echo "Unique ID: {$holder['unique_identifier']}\n";
} else {
    echo "Plan holder not found\n";
}

// Get plans for Joey
$plans = $db->query("
    SELECT p.plan_id, p.status, p.membership_state, p.start_date, p.next_due_date
    FROM plans p
    INNER JOIN plan_holders ph ON ph.plan_holder_id = p.plan_holder_id
    INNER JOIN users u ON u.user_id = ph.user_id
    WHERE u.first_name = 'Joey'
    ORDER BY p.plan_id DESC
")->getResultArray();

echo "\n=== Plans ===\n";
foreach ($plans as $plan) {
    echo "Plan ID {$plan['plan_id']}: status={$plan['status']}, membership_state={$plan['membership_state']}, start={$plan['start_date']}\n";
}

// Get payments for Joey
$payments = $db->query("
    SELECT payment_id, status, amount, payment_date, created_at
    FROM payments
    WHERE plan_id IN (
        SELECT plan_id FROM plans 
        WHERE plan_holder_id = (
            SELECT plan_holder_id FROM plan_holders 
            WHERE unique_identifier IS NULL LIMIT 1
        )
    )
    ORDER BY payment_id DESC
")->getResultArray();

echo "\n=== Payments ===\n";
foreach ($payments as $payment) {
    echo "Payment ID {$payment['payment_id']}: status={$payment['status']}, amount={$payment['amount']}, date={$payment['payment_date']}\n";
}
?>
