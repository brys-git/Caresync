<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>

<style>
    .task-card {
        border: none;
        border-left: 4px solid #3b82f6;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
    }
    .task-card:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .task-card.in-progress {
        border-left-color: #f59e0b;
    }
    .task-card.completed {
        border-left-color: #10b981;
    }
    .summary-stat {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    .summary-stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    .status-timeline {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }
    .status-timeline .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #d1d5db;
    }
    .status-timeline .dot.active {
        background-color: #3b82f6;
        width: 12px;
        height: 12px;
    }
    .location-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #f3f4f6;
        padding: 0.5rem 1rem;
        border-radius: 24px;
        font-size: 0.875rem;
    }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-0">
                <i class="bi bi-clipboard-check me-2"></i> Assigned Services
            </h4>
            <p class="text-muted small mb-0">Today's tasks and operations</p>
        </div>
        <div class="btn-group">
            <a href="<?= base_url('staff/services?view=today') ?>" class="btn btn-outline-primary active">
                <i class="bi bi-calendar-today me-1"></i> Today
            </a>
            <a href="<?= base_url('staff/services?view=all') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-list-ul me-1"></i> All
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

    <!-- Summary Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-6 col-lg-3">
            <div class="summary-stat">
                <div class="summary-stat-icon" style="background-color: #3b82f6;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="text-muted small">Assigned Today</div>
                    <div class="h5 mb-0"><?= (int) ($summary['assigned_today'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="summary-stat">
                <div class="summary-stat-icon" style="background-color: #f59e0b;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending Tasks</div>
                    <div class="h5 mb-0"><?= (int) ($summary['pending_tasks'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="summary-stat">
                <div class="summary-stat-icon" style="background-color: #10b981;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Completed</div>
                    <div class="h5 mb-0"><?= (int) ($summary['completed'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="summary-stat">
                <div class="summary-stat-icon" style="background-color: #6366f1;">
                    <i class="bi bi-play-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">In Progress</div>
                    <div class="h5 mb-0"><?= (int) ($summary['in_progress'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Services List -->
    <div class="row">
        <div class="col-12">
            <h5 class="mb-3">Service Assignments</h5>
            <div class="row g-3">
                <?php foreach ($assigned_services ?? [] as $service): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="task-card <?= strtolower($service['status'] ?? 'assigned') ?>">
                            <!-- Card Header -->
                            <div class="p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= esc($service['service_name'] ?? 'Service') ?>
                                        </h6>
                                        <small class="text-muted">
                                            ID: <?= esc($service['application_id'] ?? 'N/A') ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?= 
                                        strtolower($service['status'] ?? 'assigned') === 'completed' ? 'success' :
                                        (strtolower($service['status'] ?? 'assigned') === 'in_progress' ? 'warning' : 'info')
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $service['status'] ?? 'Assigned')) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 border-bottom">
                                <!-- Client Info -->
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Client</small>
                                    <strong><?= esc(trim(($service['first_name'] ?? '') . ' ' . ($service['last_name'] ?? ''))) ?></strong><br>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i>
                                        <?= esc($service['contact_number'] ?? 'N/A') ?>
                                    </small>
                                </div>

                                <!-- Schedule -->
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Schedule</small>
                                    <strong>
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?= ! empty($service['schedule']) ? date('M d, Y h:i A', strtotime((string) $service['schedule'])) : 'TBD' ?>
                                    </strong>
                                </div>

                                <!-- Location -->
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Location</small>
                                    <div class="location-badge">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= esc($service['location'] ?? 'Not specified') ?>
                                    </div>
                                </div>

                                <!-- Status Timeline -->
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">Timeline</small>
                                    <div class="status-timeline">
                                        <span class="dot <?= in_array(strtolower($service['status'] ?? ''), ['assigned', 'in_progress', 'completed']) ? 'active' : '' ?>"></span>
                                        <span class="text-muted">Assigned</span>
                                        <span class="dot <?= in_array(strtolower($service['status'] ?? ''), ['in_progress', 'completed']) ? 'active' : '' ?>"></span>
                                        <span class="text-muted">In Progress</span>
                                        <span class="dot <?= strtolower($service['status'] ?? '') === 'completed' ? 'active' : '' ?>"></span>
                                        <span class="text-muted">Completed</span>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <?php if (! empty($service['notes'])): ?>
                                    <div class="alert alert-light alert-sm">
                                        <small><strong>Notes:</strong></small><br>
                                        <small><?= esc(substr((string) $service['notes'], 0, 100)) ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Footer - Action Buttons -->
                            <div class="p-4">
                                <div class="d-grid gap-2">
                                    <a href="<?= base_url('staff/services/' . (int) ($service['application_id'] ?? 0)) ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                    
                                    <?php if (strtolower($service['status'] ?? 'assigned') === 'assigned'): ?>
                                        <button type="button" class="btn btn-primary btn-sm"
                                                onclick="updateStatus(<?= (int) ($service['application_id'] ?? 0) ?>, 'in_progress')">
                                            <i class="bi bi-play-circle me-1"></i> Start
                                        </button>
                                    <?php elseif (strtolower($service['status'] ?? 'assigned') === 'in_progress'): ?>
                                        <button type="button" class="btn btn-success btn-sm"
                                                onclick="updateStatus(<?= (int) ($service['application_id'] ?? 0) ?>, 'completed')">
                                            <i class="bi bi-check-circle me-1"></i> Mark Complete
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm"
                                                onclick="requestAssistance(<?= (int) ($service['application_id'] ?? 0) ?>)">
                                            <i class="bi bi-exclamation-circle me-1"></i> Need Help
                                        </button>
                                    <?php elseif (strtolower($service['status'] ?? 'assigned') === 'completed'): ?>
                                        <div class="alert alert-success mb-0 py-2">
                                            <i class="bi bi-check-circle me-1"></i> Completed
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($assigned_services)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 2.5rem;"></i>
                            <p class="mt-2 mb-0">No assigned services for today.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(applicationId, newStatus) {
    if (confirm(`Are you sure you want to mark this as ${newStatus.replace('_', ' ')}?`)) {
        // Submit status update via form or fetch
        window.location.href = `<?= base_url('staff/services') ?>/${applicationId}/status?status=${newStatus}`;
    }
}

function requestAssistance(applicationId) {
    const message = prompt('Describe what assistance you need:');
    if (message) {
        // Submit assistance request
        console.log(`Requesting assistance for ${applicationId}: ${message}`);
        // window.location.href = `<?= base_url('staff/services') ?>/${applicationId}/request-assistance?message=${encodeURIComponent(message)}`;
    }
}
</script>

<?= $this->endSection() ?>
