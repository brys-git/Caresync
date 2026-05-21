<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $q = $db->query('DESCRIBE users');
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo implode(' | ', [$r['Field'], $r['Type'], $r['Null'], $r['Key'], $r['Default'], $r['Extra']]) . PHP_EOL;
    }
    echo '--- plan_holders ---' . PHP_EOL;
    $q2 = $db->query('DESCRIBE plan_holders');
    foreach ($q2->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo implode(' | ', [$r['Field'], $r['Type'], $r['Null'], $r['Key'], $r['Default'], $r['Extra']]) . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
