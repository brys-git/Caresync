<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= esc((string) ($page_title ?? 'Collector Dashboard')) ?></h1>
        <p class="text-muted mb-0">UI-only dashboard shell</p>
    </div>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </nav>

    <div class="alert alert-info" role="alert">
        Collection entry, remittance reports, and commission tracking for this role are being built next.
    </div>

    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">Coming Soon</h5>
            <p class="text-muted mb-0">No data available yet. This dashboard confirms the Collector role is wired up end-to-end (login, routing, layout) ahead of its real features.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
