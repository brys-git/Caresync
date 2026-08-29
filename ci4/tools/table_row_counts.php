<?php
$host='localhost'; $user='root'; $pass=''; $db='kaagapay_db'; $port=3306;
$mysqli=new mysqli($host,$user,$pass,$db,$port);
if($mysqli->connect_error){echo "DB connect error: ".$mysqli->connect_error.PHP_EOL; exit(1);} 

$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='".$mysqli->real_escape_string($db)."' ORDER BY TABLE_NAME";
$res = $mysqli->query($sql);
if(! $res){ echo "Failed: ".$mysqli->error.PHP_EOL; exit(1); }

$rows = [];
while($r = $res->fetch_assoc()){
    $t = $r['TABLE_NAME'];
    $cntRes = $mysqli->query("SELECT COUNT(*) AS c FROM `".$mysqli->real_escape_string($t)."`");
    $cnt = $cntRes ? (int)$cntRes->fetch_assoc()['c'] : -1;
    $rows[$t] = $cnt;
}

foreach($rows as $t=>$c){ printf("% -30s %10d\n", $t, $c); }
$mysqli->close();
