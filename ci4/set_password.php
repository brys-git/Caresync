<?php
// Simple password reset script
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: " . $password . PHP_EOL;
echo "Hash: " . $hash . PHP_EOL;

// Connect to database
$mysqli = new mysqli('localhost', 'root', '', 'kaagapay_db2.0');

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$query = "UPDATE users SET password_hash = ? WHERE username = 'regine'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $hash);

if ($stmt->execute()) {
    echo "Password updated successfully for regine" . PHP_EOL;
} else {
    echo "Error: " . $stmt->error . PHP_EOL;
}

$stmt->close();
$mysqli->close();
?>
