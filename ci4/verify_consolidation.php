<?php
$db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');

echo "=== COMPREHENSIVE VERIFICATION ===" . PHP_EOL . PHP_EOL;

$criticalTables = [
    'activity_logs', 'add_ons', 'api_keys', 'assignments', 'audit_logs', 
    'beneficiaries', 'cash_payment_records', 'deceased', 'email_logs',
    'notifications', 'package_items', 'payment_transactions', 'rate_limits',
    'service_applications', 'service_costs', 'service_logs',
    'user_sessions'
];

echo "1. VERIFY EMPTY TABLES & FOREIGN KEY DEPENDENCIES:" . PHP_EOL;
foreach ($criticalTables as $table) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Check for foreign keys pointing TO this table
    $inbound = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_NAME='$table' AND TABLE_SCHEMA='kaagapay_db'
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Check for triggers
    $triggers = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TRIGGERS
        WHERE EVENT_OBJECT_TABLE='$table' AND TRIGGER_SCHEMA='kaagapay_db'
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    echo "\n   $table: rows=$count, inbound_fk=$inbound, triggers=$triggers";
    
    if ($inbound > 0) {
        echo " [⚠ REFERENCED]";
        $refs = $db->query("
            SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME='$table' AND TABLE_SCHEMA='kaagapay_db'
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo " by: " . implode(', ', array_column($refs, 'TABLE_NAME'));
    }
    if ($triggers > 0) {
        echo " [⚠ HAS TRIGGERS]";
    }
    echo "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "2. SAFE TO REMOVE (0 rows, no FK references, no triggers):\n";

$safe = [];
$risky = [];

foreach ($criticalTables as $table) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $inbound = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_NAME='$table' AND TABLE_SCHEMA='kaagapay_db'
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $triggers = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TRIGGERS
        WHERE EVENT_OBJECT_TABLE='$table' AND TRIGGER_SCHEMA='kaagapay_db'
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($count == 0 && $inbound == 0 && $triggers == 0) {
        $safe[] = $table;
        echo "   ✓ $table\n";
    } else {
        $risky[] = $table;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "3. NOT SAFE TO REMOVE (has dependencies):\n";

foreach ($risky as $table) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $inbound = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_NAME='$table' AND TABLE_SCHEMA='kaagapay_db'
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    echo "\n   ✗ $table (rows=$count, referenced=$inbound times)\n";
    
    if ($inbound > 0) {
        $refs = $db->query("
            SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME='$table' AND TABLE_SCHEMA='kaagapay_db'
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($refs as $ref) {
            echo "      ← {$ref['TABLE_NAME']}.{$ref['COLUMN_NAME']}\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY:\n";
echo "Safe to remove: " . count($safe) . " tables\n";
echo "Must keep: " . count($risky) . " tables\n";
echo "\nRevised consolidation: 30 → " . (30 - count($safe)) . " tables\n";
?>
