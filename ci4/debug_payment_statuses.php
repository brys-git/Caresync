<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $q = $db->query('SELECT DISTINCT status FROM payments ORDER BY status');
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['status'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
