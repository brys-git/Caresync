<?php
require_once 'vendor/autoload.php';
$db = new \mysqli('127.0.0.1', 'root', '', 'kaagapay_db2');
if ($db->connect_error) {
    die('Database error: ' . $db->connect_error);
}
$result = $db->query('SELECT user_id, username, email FROM users WHERE role_id = 1 LIMIT 5');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['username'] . ' (' . $row['email'] . ') - ID: ' . $row['user_id'] . "\n";
    }
} else {
    echo 'Query error: ' . $db->error . "\n";
}
$db->close();
?>