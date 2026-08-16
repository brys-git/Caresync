<?php
// Temporary audit script: verify every route handler (class::method) exists.
require 'vendor/autoload.php';
require 'app/Config/Paths.php';
$paths = new Config\Paths();
CodeIgniter\Boot::preload($paths);

$raw = file_get_contents('C:\xampp\htdocs\caresync\ci4\route_dump.txt');
preg_match_all('#\\\\App\\\\Controllers\\\\([A-Za-z_\\\\]+)::([A-Za-z0-9_]+)#', $raw, $m, PREG_SET_ORDER);

$missing = [];
$checked = [];
foreach ($m as $hit) {
    $class = 'App\\Controllers\\' . $hit[1];
    $method = $hit[2];
    $key = $class . '::' . $method;
    if (isset($checked[$key])) {
        continue;
    }
    $checked[$key] = true;
    if (! class_exists($class)) {
        $missing[] = 'NO CLASS: ' . $class;
        continue;
    }
    if (! method_exists($class, $method)) {
        $missing[] = 'NO METHOD: ' . $key;
    }
}
echo 'Handlers checked: ' . count($checked) . PHP_EOL;
echo '=== MISSING HANDLERS ===' . PHP_EOL;
echo empty($missing) ? '(none)' . PHP_EOL : implode(PHP_EOL, $missing) . PHP_EOL;
