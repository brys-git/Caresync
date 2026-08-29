<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $stmt = $db->prepare('SELECT ph.plan_holder_id, u.first_name, u.last_name, pl.plan_id AS plan_id, p.payment_id, p.status, p.payment_date FROM plan_holders ph JOIN users u ON u.user_id = ph.user_id JOIN plans pl ON pl.plan_holder_id = ph.plan_holder_id LEFT JOIN payments p ON p.plan_id = pl.plan_id WHERE u.first_name = ? AND u.last_name = ? ORDER BY p.payment_date ASC');
    $stmt->execute(['Anne', 'Collins']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo 'No rows found' . PHP_EOL;
    } else {
        foreach ($rows as $r) {
            echo implode(' | ', [
                $r['plan_holder_id'] ?? 'NULL',
                $r['first_name'] ?? 'NULL',
                $r['last_name'] ?? 'NULL',
                $r['plan_id'] ?? 'NULL',
                $r['payment_id'] ?? 'NULL',
                $r['status'] ?? 'NULL',
                $r['payment_date'] ?? 'NULL'
            ]) . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
