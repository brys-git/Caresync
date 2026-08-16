<?php
require 'vendor/autoload.php';
$db = \Config\Database::connect();
$rows = $db->query("SHOW COLUMNS FROM packages")->getResult();
foreach ($rows as $r) {
    echo $r->Field . ' | ' . $r->Type . ' | ' . $r->Null . ' | ' . $r->Key . PHP_EOL;
}
echo "--- service_list ---\n";
$rows = $db->query("SHOW COLUMNS FROM service_list")->getResult();
foreach ($rows as $r) {
    echo $r->Field . ' | ' . $r->Type . ' | ' . $r->Null . ' | ' . $r->Key . PHP_EOL;
}
