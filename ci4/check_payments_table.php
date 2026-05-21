<?php
$mysqli = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');
$result = $mysqli->query('SHOW COLUMNS FROM payments');
echo "Columns in payments table:" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . PHP_EOL;
}
?>
