<?php
$tables = [
    'activity_logs', 'add_ons', 'api_keys', 'assignments', 'audit_logs', 
    'beneficiaries', 'branches', 'cash_payment_records', 'deceased', 'email_logs',
    'migrations', 'notifications', 'packages', 'package_items', 'package_versions',
    'payments', 'payment_transactions', 'plans', 'plan_holders', 'rate_limits',
    'roles', 'services', 'service_applications', 'service_costs', 'service_list',
    'service_logs', 'system_settings', 'users', 'user_sessions'
];

$codeDir = __DIR__ . '/app';
$usedTables = [];
$filesChecked = 0;

function scanDirectory($dir, $extension = '.php') {
    $files = [];
    try {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $files = array_merge($files, scanDirectory($path, $extension));
            } elseif (substr($item, -4) === $extension) {
                $files[] = $path;
            }
        }
    } catch (Exception $e) {
        echo "Error scanning $dir: " . $e->getMessage() . PHP_EOL;
    }
    return $files;
}

$files = scanDirectory($codeDir);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $filesChecked++;
    foreach ($tables as $table) {
        if (strpos($content, $table) !== false) {
            if (!isset($usedTables[$table])) {
                $usedTables[$table] = 0;
            }
            $usedTables[$table]++;
        }
    }
}

echo "=== Table Usage Analysis ===" . PHP_EOL;
echo "Files checked: $filesChecked" . PHP_EOL;
echo "Tables found in database: " . count($tables) . PHP_EOL;
echo "Tables used in code: " . count($usedTables) . PHP_EOL . PHP_EOL;

echo "=== USED TABLES ===" . PHP_EOL;
arsort($usedTables);
foreach ($usedTables as $table => $count) {
    echo "$table ($count mentions)" . PHP_EOL;
}

echo PHP_EOL . "=== UNUSED TABLES ===" . PHP_EOL;
$unused = array_diff($tables, array_keys($usedTables));
if (empty($unused)) {
    echo "All tables are referenced in the code." . PHP_EOL;
} else {
    foreach ($unused as $table) {
        echo "- $table" . PHP_EOL;
    }
}
