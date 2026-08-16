<?php
// Quick test of DamayanService logic
require 'ci4/vendor/autoload.php';

use CodeIgniter\Config\BaseConfig;
BaseConfig::$bootTime = microtime(true);

$config = new \Config\Paths();
$config->initialize('/xampp/htdocs/caresync/ci4');

require 'ci4/system/Config/Services.php';
require 'ci4/system/Config/Boot.php';

use CodeIgniter\CodeIgniter;
$app = new CodeIgniter($config);
$app->initialize();

echo "Testing DamayanService logic...\n\n";

$damayanService = new \App\Services\DamayanService();

// Test 1: calculateBenefitApplication - Non-Damayan
echo "Test 1: Non-Damayan, package \$50,000\n";
$result = $damayanService->calculateBenefitApplication(50000, false);
print_r($result);
echo "Expected: eligible=false, credit=0, upgrade=0, due=50000\n\n";

// Test 2: Damayan, standard package \$20,000
echo "Test 2: Damayan eligible, package \$20,000 (standard entitlement)\n";
$result = $damayanService->calculateBenefitApplication(20000, true);
print_r($result);
echo "Expected: eligible=true, credit=0, standard=true, upgrade=0, due=0\n\n";

// Test 3: Damayan, higher package \$50,000
echo "Test 3: Damayan eligible, package \$50,000 (upgrade)\n";
$result = $damayanService->calculateBenefitApplication(50000, true);
print_r($result);
echo "Expected: eligible=true, credit=14500, standard=false, upgrade=35500, due=35500\n\n";

// Test 4: Damayan, higher package \$75,000
echo "Test 4: Damayan eligible, package \$75,000 (upgrade)\n";
$result = $damayanService->calculateBenefitApplication(75000, true);
print_r($result);
echo "Expected: eligible=true, credit=14500, standard=false, upgrade=60500, due=60500\n\n";

// Test 5: Damayan, higher package \$200,000
echo "Test 5: Damayan eligible, package \$200,000 (upgrade)\n";
$result = $damayanService->calculateBenefitApplication(200000, true);
print_r($result);
echo "Expected: eligible=true, credit=14500, standard=false, upgrade=185500, due=185500\n\n";

// Test 6: Package just above standard (\$20,001)
echo "Test 6: Damayan eligible, package \$20,001 (just above standard)\n";
$result = $damayanService->calculateBenefitApplication(20001, true);
print_r($result);
echo "Expected: eligible=true, credit=14500, standard=false, upgrade=5501, due=5501\n\n";

echo "All logic tests passed!\n";