<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=kaagapay_db', 'root', '');
    $patterns = ['%balance%', 'payments', 'users', 'branches', 'beneficiaries', 'service_balance_payments', 'service_balances'];
    foreach ($patterns as $pattern) {
        if (strpos($pattern, '%') !== false) {
            $stmt = $db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' AND table_name LIKE ?");
            $stmt->execute([$pattern]);
            echo "PATTERN: $pattern\n";
        } else {
            $stmt = $db->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema='kaagapay_db' AND table_name = ?");
            $stmt->execute([$pattern]);
            echo "TABLE: $pattern\n";
        }
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo $row['table_name'] . PHP_EOL;
        }
        echo PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
