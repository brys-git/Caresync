<?php
$db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');

$candidates = [
    ['activity_logs', 'audit_logs'],
    ['payments', 'payment_transactions'],
    ['services', 'service_list'],
    ['api_keys', 'rate_limits'],
    ['notifications', 'activity_logs']
];

foreach ($candidates as [$table1, $table2]) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Comparing: $table1 vs $table2\n";
    echo str_repeat("=", 60) . "\n";
    
    // Get columns for table1
    $result = $db->query("DESCRIBE $table1");
    $cols1 = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "\n$table1 columns (" . count($cols1) . "):\n";
    foreach ($cols1 as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    
    // Get columns for table2
    $result = $db->query("DESCRIBE $table2");
    $cols2 = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "\n$table2 columns (" . count($cols2) . "):\n";
    foreach ($cols2 as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    
    // Get row counts
    $count1 = $db->query("SELECT COUNT(*) as cnt FROM $table1")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $count2 = $db->query("SELECT COUNT(*) as cnt FROM $table2")->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "\nRow counts: $table1 = $count1, $table2 = $count2\n";
    
    // Check for foreign keys
    echo "\nForeign Keys in $table1:\n";
    $fk = $db->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME='$table1' AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fkData = $fk->fetchAll(PDO::FETCH_ASSOC);
    if (empty($fkData)) echo "  None\n";
    foreach ($fkData as $fk) {
        echo "  {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
    
    echo "\nForeign Keys in $table2:\n";
    $fk = $db->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME='$table2' AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fkData = $fk->fetchAll(PDO::FETCH_ASSOC);
    if (empty($fkData)) echo "  None\n";
    foreach ($fkData as $fk) {
        echo "  {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
}

// Also check deceased table
echo "\n" . str_repeat("=", 60) . "\n";
echo "Table: deceased\n";
echo str_repeat("=", 60) . "\n";
$result = $db->query("DESCRIBE deceased");
$cols = $result->fetchAll(PDO::FETCH_ASSOC);
echo "\nColumns (" . count($cols) . "):\n";
foreach ($cols as $col) {
    echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
}
$count = $db->query("SELECT COUNT(*) as cnt FROM deceased")->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "\nRow count: $count\n";

// Check if plan_holders has a status column
echo "\n" . str_repeat("=", 60) . "\n";
echo "Checking plan_holders for potential status field:\n";
$result = $db->query("DESCRIBE plan_holders");
$cols = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    if (stripos($col['Field'], 'status') !== false || stripos($col['Field'], 'active') !== false) {
        echo "  Found: {$col['Field']} ({$col['Type']})\n";
    }
}
?>
