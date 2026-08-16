<?php
// ---------------------------------------------------------------------
// TEMPORARY CareSync deployment extractor — DELETE THIS FILE AFTER USE.
// Extracts caresync-deploy.zip (Unix-compatible, forward-slash entries)
// into the same directory as this script.
// ---------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(600);

$base    = __DIR__;
$zipFile = $base . '/caresync-deploy.zip';

if (! file_exists($zipFile)) {
    die("ZIP not found: {$zipFile}\n");
}
if (! class_exists('ZipArchive')) {
    die("ZipArchive not available on this PHP build\n");
}

$zip = new ZipArchive();
$res = $zip->open($zipFile);
if ($res !== true) {
    die("Cannot open ZIP (code {$res})\n");
}

$count = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);

    // Normalize leading "./" from bsdtar output.
    if (strncmp($name, './', 2) === 0) {
        $name = substr($name, 2);
    }
    if ($name === '' || $name === '/' || $name === '.') {
        continue;
    }

    $target = $base . '/' . $name;

    if (substr($name, -1) === '/') {
        @mkdir($target, 0755, true);
        continue;
    }

    $dir = dirname($target);
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $data = $zip->getFromIndex($i);
    if ($data !== false) {
        if (file_put_contents($target, $data) === false) {
            echo "FAILED to write: {$name}\n";
        } else {
            $count++;
        }
    }
}
$zip->close();
echo "EXTRACTED {$count} files OK\n";
echo "Done at " . date('Y-m-d H:i:s') . "\n";
