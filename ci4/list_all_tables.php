<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' ORDER BY table_name ASC";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== Database Tables for kaagapay_db ===" . PHP_EOL;
    echo "Total tables: " . count($tables) . PHP_EOL . PHP_EOL;
    
    foreach ($tables as $index => $table) {
        echo ($index + 1) . ". " . $table . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
