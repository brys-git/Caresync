<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'kaagapay_db';
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_error) {
    echo "DB connect error: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($dbname) . "' ORDER BY TABLE_NAME";
$res = $mysqli->query($sql);
if (! $res) {
    echo "Query failed: " . $mysqli->error . PHP_EOL;
    exit(1);
}

while ($row = $res->fetch_assoc()) {
    echo $row['TABLE_NAME'] . PHP_EOL;
}

$mysqli->close();
