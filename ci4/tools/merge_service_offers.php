<?php
$host='localhost'; $user='root'; $pass=''; $db='kaagapay_db'; $port=3306;
$mysqli = new mysqli($host,$user,$pass,$db,$port);
if($mysqli->connect_error){
    echo "DB connect error: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

// Check if service_offers exists
$exists = $mysqli->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='". $mysqli->real_escape_string($db) ."' AND TABLE_NAME='service_offers'")->fetch_assoc()['c'] ?? 0;
if(!$exists){
    echo "service_offers not found, nothing to do" . PHP_EOL;
    exit(0);
}

// Copy rows
$insertSql = "INSERT INTO service_list (service_name, `description`, base_price, `status`, is_available, created_at, updated_at) SELECT service_name, `description`, COALESCE(base_price, 0.00), `status`, 1, created_at, created_at FROM service_offers";
if(!$mysqli->query($insertSql)){
    echo "INSERT ERROR: " . $mysqli->error . PHP_EOL;
    exit(1);
}

// Drop old table
if(!$mysqli->query('DROP TABLE IF EXISTS service_offers')){
    echo "DROP ERROR: " . $mysqli->error . PHP_EOL;
    exit(1);
}

echo "MERGE_OK" . PHP_EOL;
$mysqli->close();
