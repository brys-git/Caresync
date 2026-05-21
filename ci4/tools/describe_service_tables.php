<?php
$host='localhost'; $user='root'; $pass=''; $db='kaagapay_db'; $port=3306;
$tables = ['services','service_applications','service_costs','service_list'];
$mysqli = new mysqli($host,$user,$pass,$db,$port);
if($mysqli->connect_error){echo "DB connect error: ".$mysqli->connect_error.PHP_EOL; exit(1);} 

foreach($tables as $t){
    echo "\n=== TABLE: {$t} ===\n";
    // columns
    $colRes = $mysqli->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".$mysqli->real_escape_string($db)."' AND TABLE_NAME='".$mysqli->real_escape_string($t)."' ORDER BY ORDINAL_POSITION");
    if(! $colRes || $colRes->num_rows === 0){
        echo "[Table not found or no columns]\n";
        continue;
    }
    echo "Columns:\n";
    while($c = $colRes->fetch_assoc()){
        printf(" - %-30s %-20s %-8s %-10s\n", $c['COLUMN_NAME'], $c['COLUMN_TYPE'], $c['IS_NULLABLE'], $c['COLUMN_DEFAULT']);
    }
    // row count
    $cntRes = $mysqli->query("SELECT COUNT(*) AS c FROM `".$mysqli->real_escape_string($t)."`");
    $cnt = $cntRes ? (int)$cntRes->fetch_assoc()['c'] : 0;
    echo "Row count: {$cnt}\n";
    // sample rows
    if($cnt>0){
        $rows = $mysqli->query("SELECT * FROM `".$mysqli->real_escape_string($t)."` LIMIT 5");
        echo "Sample rows:\n";
        while($r = $rows->fetch_assoc()){
            print_r($r);
        }
    }
}
$mysqli->close();
