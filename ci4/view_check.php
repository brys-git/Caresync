<?php
// Temporary audit script: verify every view('...') call maps to an existing file,
// and that form actions / links in views point at routes that exist.
$appDir = 'C:\xampp\htdocs\caresync\ci4\app';
$viewsDir = $appDir . '\Views';

// 1) Collect all view() calls across controllers.
$viewCalls = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir . '\Controllers'));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') {
        continue;
    }
    $src = file_get_contents($f->getPathname());
    preg_match_all("/view\(\s*['\"]([^'\"]+)['\"]/", $src, $m);
    foreach ($m[1] as $v) {
        $viewCalls[$v][] = str_replace('\\', '/', substr($f->getPathname(), strlen('C:\xampp\htdocs\caresync\ci4') + 1));
    }
}

$missing = [];
foreach ($viewCalls as $view => $callers) {
    $rel = str_replace('\\', '/', $view);
    // A view like 'client/payment' -> Views/client/payment.php
    if (! is_file($viewsDir . '\\' . str_replace('/', '\\', $rel) . '.php')) {
        $missing[] = sprintf('%-55s (from %s)', $rel, implode(', ', $callers));
    }
}
echo '=== MISSING VIEW FILES ===' . PHP_EOL;
echo empty($missing) ? "(none)" . PHP_EOL : implode(PHP_EOL, $missing) . PHP_EOL;

// 2) Unused views? List view files never referenced by any controller.
$referenced = [];
foreach ($viewCalls as $view => $callers) {
    $referenced[strtolower($view . '.php')] = true;
}
$orphans = [];
$vit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($vit as $f) {
    if ($f->getExtension() !== 'php') {
        continue;
    }
    $rel = strtolower(substr($f->getPathname(), strlen($viewsDir) + 1));
    $rel = str_replace('\\', '/', $rel);
    if (! isset($referenced[$rel])) {
        $orphans[] = $rel;
    }
}
echo PHP_EOL . '=== VIEW FILES NEVER REFERENCED BY view() (candidates for dead code) ===' . PHP_EOL;
echo empty($orphans) ? "(none)" . PHP_EOL : implode(PHP_EOL, $orphans) . PHP_EOL;
