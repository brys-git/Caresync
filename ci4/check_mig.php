<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=kaagapay_db", "root", "");
    $query = $db->query("SELECT migration FROM migrations ORDER BY migration");
    $migrations = $query->fetchAll();
    echo "Total applied migrations: " . count($migrations) . PHP_EOL;
    $activityLogsFound = false;
    foreach ($migrations as $row) {
        echo "  - " . $row[0] . PHP_EOL;
        if (strpos($row[0], "CreateActivityLogsTable") !== false) {
            $activityLogsFound = true;
        }
    }
    echo PHP_EOL;
    if ($activityLogsFound) {
        echo "SUCCESS: Activity Logs migration WAS successfully applied!" . PHP_EOL;
    } else {
        echo "INFO: Activity Logs migration was NOT applied." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

