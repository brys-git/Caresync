<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $queries = [
        "SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' AND table_name LIKE '%service%'",
        "SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' AND table_name LIKE '%application%'",
        "SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' AND table_name LIKE '%beneficiary%'",
        "SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' AND table_name LIKE '%plan%'",
    ];
    foreach ($queries as $query) {
        echo "QUERY: $query\n";
        foreach ($db->query($query) as $row) {
            echo $row['table_name'] . PHP_EOL;
        }
        echo PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
