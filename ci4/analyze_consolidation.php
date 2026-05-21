<?php
$db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');

$tables = [
    'activity_logs', 'add_ons', 'api_keys', 'assignments', 'audit_logs', 
    'beneficiaries', 'branches', 'cash_payment_records', 'deceased', 'email_logs',
    'migrations', 'notifications', 'packages', 'package_items', 'package_versions',
    'payments', 'payment_transactions', 'plans', 'plan_holders', 'rate_limits',
    'roles', 'services', 'service_applications', 'service_costs', 'service_list',
    'service_logs', 'system_settings', 'users', 'user_sessions'
];

echo "=== CONSOLIDATION ANALYSIS ===" . PHP_EOL . PHP_EOL;

// 1. Find tables with 0 rows
echo "1. EMPTY TABLES (0 rows - candidates for removal):" . PHP_EOL;
$emptyTables = [];
foreach ($tables as $table) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    if ($count == 0) {
        $emptyTables[] = $table;
        echo "   - $table\n";
    }
}
if (empty($emptyTables)) echo "   None\n";

// 2. Find tables that are referenced by foreign keys (important)
echo "\n2. TABLES WITH FOREIGN KEY DEPENDENCIES (referenced by other tables):" . PHP_EOL;
$referencedTables = [];
$fkQuery = $db->query("
    SELECT DISTINCT REFERENCED_TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA='kaagapay_db' AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY REFERENCED_TABLE_NAME
");
while ($row = $fkQuery->fetch(PDO::FETCH_ASSOC)) {
    $refTable = $row['REFERENCED_TABLE_NAME'];
    $referencedTables[] = $refTable;
    
    // Get tables that reference this one
    $referrers = $db->query("
        SELECT DISTINCT TABLE_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA='kaagapay_db' 
        AND REFERENCED_TABLE_NAME='$refTable'
    ");
    $refs = $referrers->fetchAll(PDO::FETCH_ASSOC);
    echo "   $refTable (referenced by " . count($refs) . " tables):\n";
    foreach ($refs as $ref) {
        echo "      <- {$ref['TABLE_NAME']}\n";
    }
}

// 3. Find tables with very few columns (candidates for consolidation)
echo "\n3. TABLES WITH FEWER THAN 5 COLUMNS (lookup/config tables):" . PHP_EOL;
foreach ($tables as $table) {
    $cols = $db->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$table' AND TABLE_SCHEMA='kaagapay_db'")->fetch(PDO::FETCH_ASSOC)['cnt'];
    if ($cols < 5) {
        $rowCount = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "   - $table ($cols columns, $rowCount rows)\n";
    }
}

// 4. Find tables with no foreign keys pointing FROM them
echo "\n4. ISOLATED TABLES (no foreign keys to other tables - candidates for removal):" . PHP_EOL;
$isolated = [];
foreach ($tables as $table) {
    $fk = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME='$table' 
        AND TABLE_SCHEMA='kaagapay_db' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($fk == 0) {
        $isolated[] = $table;
        $rowCount = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "   - $table ($rowCount rows)\n";
    }
}

// 5. Suggest consolidations
echo "\n5. CONSOLIDATION SUGGESTIONS:" . PHP_EOL;
echo "\nA) REMOVE COMPLETELY EMPTY TABLES:" . PHP_EOL;
foreach ($emptyTables as $table) {
    if (!in_array($table, $referencedTables)) {
        echo "   ✓ Remove: $table (empty + not referenced)\n";
    } else {
        echo "   ⚠ Keep: $table (empty but referenced by other tables)\n";
    }
}

echo "\nB) CONSOLIDATE LOGGING TABLES INTO 'activity_logs':" . PHP_EOL;
$logTables = ['audit_logs', 'email_logs', 'service_logs'];
foreach ($logTables as $table) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   - Merge $table ($count rows) into activity_logs with log_type field\n";
}

echo "\nC) CONSOLIDATE CONFIG/SETTINGS:" . PHP_EOL;
$configTables = ['system_settings', 'roles', 'add_ons'];
foreach ($configTables as $table) {
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   - Could store $table ($count rows) in system_settings as JSON config\n";
}

echo "\nD) CONSOLIDATE API MANAGEMENT:" . PHP_EOL;
$apiTables = ['api_keys', 'rate_limits'];
foreach ($apiTables as $table) {
    $fk = $db->query("
        SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME='$table' AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   - $table ($count rows, $fk FKs): consider api_management table\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "REALISTIC REDUCTION: 30 → ~20-22 tables\n";
echo "Without breaking relationships or audit trails\n";
?>
