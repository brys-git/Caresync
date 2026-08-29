<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1"><?= esc((string) $module_label) ?></h1>
            <p class="text-muted mb-0"><?= esc((string) $role_label) ?> module shell</p>
        </div>
    </div>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
            <?php foreach (($breadcrumb ?? []) as $crumb): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= esc((string) $crumb) ?></li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="alert alert-info" role="alert">
        This module is currently under development.
    </div>

    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">Coming Soon</h5>
            <p class="text-muted mb-0">No data available. Functional logic is temporarily disabled in this reset phase.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
