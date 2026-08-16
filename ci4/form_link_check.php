<?php
// Audit form actions and links in views vs routes
$viewsDir = 'C:\xampp\htdocs\caresync\ci4\app\Views';

// Load all routes
$routesRaw = file_get_contents('C:\xampp\htdocs\caresync\ci4\route_dump.txt');
preg_match_all('#^\s*\| (GET|POST|PUT|PATCH|DELETE) \s+\| ([^|]+) \s+\|#m', $routesRaw, $m);
$routePatterns = [];
foreach ($m[2] as $idx => $uri) {
    $method = $m[1][$idx];
    $uri = trim($uri);
    // Convert (:num) (:any) (:segment) etc to regex
    $pattern = preg_replace('#\(\:([a-z]+)\)#', '(?<$1>[^/]+)', $uri);
    $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';
    $routePatterns[$pattern] = $method;
}

// Check views for form actions and links
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
$badActions = [];
$badLinks = [];
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    $src = file_get_contents($f->getPathname());
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($viewsDir) + 1));

    // form action="..."
    preg_match_all('/<form[^>]+action\s*=\s*["\']([^"\']+)["\']/i', $src, $m);
    foreach ($m[1] as $action) {
        // resolve site_url/base_url
        if (strpos($action, 'site_url(') === 0 || strpos($action, 'base_url(') === 0) {
            preg_match('/\(["\']([^"\']+)["\']\)/', $action, $mm);
            if ($mm) $action = $mm[1];
            else continue;
        }
        $action = ltrim($action, '/');
        // Check if any route matches
        $matched = false;
        foreach ($routePatterns as $pat => $mth) {
            if (preg_match($pat, $action)) { $matched = true; break; }
        }
        if (! $matched && $action !== '') {
            $badActions[] = "$rel -> $action";
        }
    }

    // Links with href starting with / or site_url/base_url
    preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\']+)["\']/i', $src, $m);
    foreach ($m[1] as $href) {
        if (strpos($href, 'site_url(') === 0 || strpos($href, 'base_url(') === 0) {
            preg_match('/\(["\']([^"\']+)["\']\)/', $href, $mm);
            if ($mm) $href = $mm[1];
            else continue;
        }
        if ($href === '#' || strpos($href, 'http') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) continue;
        $href = ltrim($href, '/');
        $matched = false;
        foreach ($routePatterns as $pat => $mth) {
            if (preg_match($pat, $href)) { $matched = true; break; }
        }
        if (! $matched) {
            $badLinks[] = "$rel -> $href";
        }
    }
}

echo '=== FORM ACTIONS NOT MATCHING ANY ROUTE ===' . PHP_EOL;
echo empty($badActions) ? "(none)" . PHP_EOL : implode(PHP_EOL, $badActions) . PHP_EOL;
echo PHP_EOL . '=== LINKS NOT MATCHING ANY ROUTE ===' . PHP_EOL;
echo empty($badLinks) ? "(none)" . PHP_EOL : implode(PHP_EOL, $badLinks) . PHP_EOL;