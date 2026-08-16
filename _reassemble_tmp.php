<?php
// ---------------------------------------------------------------------
// TEMPORARY CareSync deployment helper — DELETE AFTER USE.
// 1) Concatenates part_00..part_07 into caresync-deploy.zip
// 2) Extracts the ZIP into this directory (htdocs)
// 3) Deletes the parts and this script is removed manually afterwards.
// ---------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(1200);
$base = __DIR__;

// --- Step 1: reassemble ---
$parts = [];
for ($i = 0; $i < 8; $i++) {
    $parts[] = $base . '/part_' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
}
$zipOut = $base . '/caresync-deploy.zip';

if (! file_exists($zipOut)) {
    $fh = fopen($zipOut, 'wb');
    $total = 0;
    foreach ($parts as $p) {
        if (! file_exists($p)) { die("Missing part: {$p}\n"); }
        $size = filesize($p);
        $in = fopen($p, 'rb');
        while (! feof($in)) {
            $chunk = fread($in, 1048576); // 1MB chunks
            if ($chunk === false || fwrite($fh, $chunk) === false) {
                die("Write failed while reassembling\n");
            }
        }
        fclose($in);
        $total += $size;
        echo "Merged {$p} ({$size} bytes)\n";
    }
    fclose($fh);
    echo "Reassembled ZIP: {$zipOut} ({$total} bytes)\n";
} else {
    echo "ZIP already exists, skipping reassembly.\n";
}

// --- Step 2: extract ---
if (! class_exists('ZipArchive')) { die("ZipArchive not available\n"); }
$zip = new ZipArchive();
$res = $zip->open($zipOut);
if ($res !== true) { die("Cannot open ZIP (code {$res})\n"); }
$count = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (strncmp($name, './', 2) === 0) { $name = substr($name, 2); }
    if ($name === '' || $name === '/' || $name === '.') { continue; }
    $target = $base . '/' . $name;
    if (substr($name, -1) === '/') {
        @mkdir($target, 0755, true);
        continue;
    }
    $dir = dirname($target);
    if (! is_dir($dir)) { @mkdir($dir, 0755, true); }
    $data = $zip->getFromIndex($i);
    if ($data !== false) {
        if (file_put_contents($target, $data) === false) {
            echo "FAILED: {$name}\n";
        } else { $count++; }
    }
}
$zip->close();
echo "EXTRACTED {$count} files OK\n";

// --- Step 3: cleanup parts ---
foreach ($parts as $p) {
    if (file_exists($p)) { unlink($p); echo "Deleted {$p}\n"; }
}
echo "DONE\n";
