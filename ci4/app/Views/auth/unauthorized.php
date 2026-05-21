<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
</head>
<body>
    <main class="container py-5">
        <div class="card shadow-sm" style="max-width: 560px; margin: 0 auto;">
            <div class="card-body p-4 text-center">
                <h1 class="h4 mb-3">Access Denied</h1>
                <p class="text-muted mb-4">You do not have permission to access this page.</p>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </main>
</body>
</html>
