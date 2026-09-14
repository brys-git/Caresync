<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1c3a34">
    <title>Access denied · CareSync</title>
    <link rel="icon" href="<?= base_url('assets/favicon_io/favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/caresync.css') ?>">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cs-paper);
            padding: 1.25rem;
        }
    </style>
</head>
<body>
    <section class="cs-panel" style="max-width: 420px; width: 100%;">
        <div class="cs-panel__body text-center">
            <i class="ti ti-lock" style="font-size: 2.5rem; color: var(--cs-stop);" aria-hidden="true"></i>
            <h1 class="h4 mt-3 mb-2">Access Denied</h1>
            <p class="cs-muted mb-4">You do not have permission to access this page.</p>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">Go to Dashboard</a>
        </div>
    </section>
</body>
</html>
