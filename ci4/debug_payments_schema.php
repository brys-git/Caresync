<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $q = $db->query('DESCRIBE payments');
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo implode(' | ', [ $r['Field'], $r['Type'], $r['Null'], $r['Key'], $r['Default'], $r['Extra'] ]) . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
