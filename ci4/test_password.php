<?php
$mysqli = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');
if ($mysqli->connect_error) die('Connection failed: ' . $mysqli->connect_error);

$result = $mysqli->query('SELECT username, password_hash FROM users WHERE username = "regine"');
$row = $result->fetch_assoc();

echo "Username: " . $row['username'] . PHP_EOL;
echo "Password Hash: " . $row['password_hash'] . PHP_EOL;

// Test if password_verify works with the hash
$test_pass = 'password123';
$verify_result = password_verify($test_pass, $row['password_hash']);
echo "Password Verify Result: " . ($verify_result ? 'TRUE' : 'FALSE') . PHP_EOL;

$mysqli->close();
?>
