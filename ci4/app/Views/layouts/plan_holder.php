<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Holder Dashboard</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom-style.css') ?>">
</head>
<body>
<div class="main-wrapper d-flex">
    <?= $this->include('partials/sidebar_plan_holder') ?>
    <main class="app-main p-4 w-100">
        <?= $this->renderSection('content') ?>
    </main>
</div>
<script src="<?= base_url('assets/js/ui-consistency.js') ?>" defer></script>
<?php 
// Load main.js only on dashboard and other interactive pages, not on registration/payment forms
$currentRoute = service('router')->getMatchedRoute()[0] ?? '';
$excludeMainJS = strpos($currentRoute, 'plan-registration') !== false || 
                 strpos($currentRoute, 'initial-payment') !== false ||
                 strpos($currentRoute, 'plan-info') !== false;
if (!$excludeMainJS): 
?>
<script src="<?= base_url('assets/js/main.js') ?>" type="module"></script>
<?php endif; ?>
</body>
</html>
