<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<?php
$totalServices = (int) ($statistics['total_services'] ?? 0);
$pendingApplications = (int) ($statistics['pending_applications'] ?? 0);
$approvedToday = (int) ($statistics['approved_today'] ?? 0);
$ongoingServices = (int) ($statistics['ongoing_services'] ?? 0);
?>

<style>
    .stat-card {
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: white;
    }
    .stat-content h6 {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
    }
    .stat-content .number {
        font-size: 2rem;
        font-weight: bold;
        color: #1f2937;
    }
    .table-hover tbody tr:hover {
        background-color: #f3f4f6;
    }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-heart-handshake me-2"></i> Service Management
            </h4>
            <p class="text-muted small mb-0">Monitor and manage funeral services and applications</p>
        </div>
        <div class="btn-group" role="group">
            <a href="<?= base_url('admin/services/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Service
            </a>
            <a href="<?= base_url('admin/packages') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-box2 me-1"></i> Manage Packages
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i> <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #3b82f6;">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <div class="stat-content">
                    <h6>Total Services</h6>
                    <div class="number"><?= $totalServices ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #f59e0b;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-content">
                    <h6>Pending Applications</h6>
                    <div class="number"><?= $pendingApplications ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #10b981;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h6>Approved Today</h6>
                    <div class="number"><?= $approvedToday ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #6366f1;">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h6>Ongoing Services</h6>
                    <div class="number"><?= $ongoingServices ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="btn-group flex-wrap gap-2" role="group">
                        <a href="<?= base_url('admin/services') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul me-1"></i> View All Services
                        </a>
                        <a href="<?= base_url('admin/services/pending-applications') ?>" class="btn btn-outline-warning">
                            <i class="bi bi-hourglass-split me-1"></i> View Pending Applications
                        </a>
                        <a href="<?= base_url('admin/services/approvals') ?>" class="btn btn-outline-success">
                            <i class="bi bi-check-lg me-1"></i> Process Approvals
                        </a>
                        <a href="<?= base_url('admin/services/report') ?>" class="btn btn-outline-info">
                            <i class="bi bi-graph-up me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Management Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Services</h6>
                    <small class="text-muted">Total: <?= count($services ?? []) ?></small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Availability</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services ?? [] as $service): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($service['service_name'] ?? 'N/A') ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= esc($service['category'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>₱<?= number_format((float) ($service['base_price'] ?? 0), 2) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($service['is_available'] ?? false): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Unavailable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= (int) ($service['application_count'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $status = strtolower($service['status'] ?? 'active');
                                                $badgeClass = $status === 'active' ? 'bg-success' : ($status === 'pending' ? 'bg-warning' : 'bg-secondary');
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?= base_url('admin/services/' . (int) ($service['service_list_id'] ?? 0)) ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?= base_url('admin/services/' . (int) ($service['service_list_id'] ?? 0) . '/edit') ?>" 
                                                   class="btn btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" title="Disable"
                                                        onclick="confirmDisable(<?= (int) ($service['service_list_id'] ?? 0) ?>)">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($services)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No services found. <a href="<?= base_url('admin/services/create') ?>">Create one</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Applications Panel -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-circle me-2" style="color: #f59e0b;"></i>
                        Pending Applications
                    </h6>
                    <small class="text-muted">Total: <?= count($pending_applications ?? []) ?></small>
                </div>
                <div class="card-body p-0">
                    <?php if (! empty($pending_applications)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Client</th>
                                        <th>Service</th>
                                        <th>Date Applied</th>
                                        <th>Eligibility</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_applications as $app): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= esc(trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''))) ?>
                                                </strong><br>
                                                <small class="text-muted"><?= esc($app['unique_identifier'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <?= esc($app['service_name'] ?? 'N/A') ?>
                                            </td>
                                            <td>
                                                <?= ! empty($app['created_at']) ? date('M d, Y', strtotime((string) $app['created_at'])) : 'N/A' ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $eligible = (int) ($app['months_paid'] ?? 0) >= 2;
                                                ?>
                                                <?php if ($eligible): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i> Eligible
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle me-1"></i> Ineligible
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">Pending</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= base_url('admin/services/applications/' . (int) ($app['application_id'] ?? 0)) ?>" 
                                                       class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success"
                                                            onclick="approveApplication(<?= (int) ($app['application_id'] ?? 0) ?>)">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                            onclick="rejectApplication(<?= (int) ($app['application_id'] ?? 0) ?>)">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i> No pending applications at the moment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDisable(serviceId) {
    if (confirm('Are you sure you want to disable this service?')) {
        // Implement disable service functionality
        window.location.href = `<?= base_url('admin/services') ?>/${serviceId}/disable`;
    }
}

function approveApplication(applicationId) {
    if (confirm('Approve this service application?')) {
        // Implement approve functionality
        window.location.href = `<?= base_url('admin/services/applications') ?>/${applicationId}/approve`;
    }
}

function rejectApplication(applicationId) {
    const reason = prompt('Enter rejection reason:');
    if (reason) {
        // Implement reject functionality
        // window.location.href = `<?= base_url('admin/services/applications') ?>/${applicationId}/reject?reason=${reason}`;
    }
}
</script>

<?= $this->endSection() ?>
