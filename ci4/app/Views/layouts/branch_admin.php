<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Admin Dashboard</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom-style.css') ?>">
</head>
<body>
<div class="main-wrapper d-flex">
    <?= $this->include('partials/sidebar_branch_admin') ?>
    <main class="app-main p-4 w-100">
        <?= $this->renderSection('content') ?>
    </main>
</div>
<script src="<?= base_url('assets/js/ui-consistency.js') ?>" defer></script>
<script src="<?= base_url('assets/js/main.js') ?>" type="module"></script>
</body>
</html>
