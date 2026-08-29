<?php
$db = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($db->connect_error) {
    die('Connection failed: ' . $db->connect_error);
}

echo "=== Checking Users ===\n";

$result = $db->query("SELECT user_id, username, email, role_id FROM users WHERE role_id = 2 LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "User ID: " . $row['user_id'] . " | Username: " . $row['username'] . " | Role: " . $row['role_id'] . "\n";
}

$db->close();
?>
