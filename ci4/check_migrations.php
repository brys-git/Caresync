<?php
try {
    \ = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    \ = \->query('SELECT migration FROM migrations ORDER BY migration');
    \ = \->fetchAll();
    echo "Total applied migrations: " . count(\) . PHP_EOL;
    \ = false;
    foreach (\ as \) {
        echo "  - " . \[0] . PHP_EOL;
        if (strpos(\[0], 'CreateActivityLogsTable') !== false) {
            \ = true;
        }
    }
    echo PHP_EOL;
    if (\) {
        echo "SUCCESS: Activity Logs migration WAS successfully applied!" . PHP_EOL;
    } else {
        echo "INFO: Activity Logs migration was NOT applied." . PHP_EOL;
    }
} catch (Exception \) {
    echo "Error: " . \->getMessage() . PHP_EOL;
}
