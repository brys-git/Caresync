<?php
/**
 * One-off script to add rejection_reason column to service_applications.
 * Delete this file after running it.
 */
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/Paths.php';

$paths = new \Config\Paths();
define('ROOTPATH', $paths->rootDirectory);
define('APPPATH', $paths->appDirectory);

$env = getenv('CI_ENVIRONMENT') ?: 'production';
define('ENVIRONMENT', $env);

require ROOTPATH . 'system/Boot.php';

$config = new \Config\Database();
$db = \Config\Database::connect($config);

$db->query("ALTER TABLE service_applications ADD COLUMN rejection_reason TEXT NULL AFTER application_notes");

echo "✅ Column 'rejection_reason' added to service_applications table.\n";
echo "⚠️  Delete this file after running.\n";
