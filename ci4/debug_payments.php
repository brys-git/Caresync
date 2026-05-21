<?php
require 'vendor/autoload.php';
require 'app/Config/Constants.php';
$_ENV['CI_ENVIRONMENT'] = 'development';

$db = \Config\Database::connect();
$result = $db->query('SELECT payment_id, plan_id, amount, months_covered, official_receipt_number, status FROM payments WHERE plan_id IN (SELECT plan_id FROM plans WHERE plan_holder_id = (SELECT plan_holder_id FROM plan_holders WHERE first_name = "Joey" LIMIT 1)) ORDER BY payment_id DESC LIMIT 5')->getResultArray();
echo "=== Payment Records for Joey ===\n";
foreach ($result as $row) {
    echo "ID: {$row['payment_id']}, Plan: {$row['plan_id']}, Amount: {$row['amount']}, Months: {$row['months_covered']}, OR: {$row['official_receipt_number']}, Status: {$row['status']}\n";
}
echo "\nTotal records: " . count($result) . "\n";
?>
