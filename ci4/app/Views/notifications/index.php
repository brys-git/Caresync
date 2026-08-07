<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <h1 class="h4 mb-3">Notifications</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form class="row g-2 mb-3" method="get" action="<?= base_url('notifications') ?>">
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach (['general','registration_pending','payment_approved','payment_rejected','service_approved','service_rejected','service_completed'] as $typeOption): ?>
                            <option value="<?= esc($typeOption) ?>" <?= ($selected_type ?? '') === $typeOption ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $typeOption))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="unread" <?= ($selected_status ?? '') === 'unread' ? 'selected' : '' ?>>Unread</option>
                        <option value="read" <?= ($selected_status ?? '') === 'read' ? 'selected' : '' ?>>Read</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                </div>
            </form>

            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <div class="fw-semibold">
                                <?= esc($notification['message']) ?>
                                <span class="badge text-bg-light ms-2"><?= esc(ucwords(str_replace('_', ' ', (string) ($notification['type'] ?? 'general')))) ?></span>
                            </div>
                            <small class="text-muted"><?= esc((string) $notification['created_at']) ?></small>
                        </div>
                        <div>
                            <?php if ((int) ($notification['is_read'] ?? 0) === 0): ?>
                                <form method="post" action="<?= base_url('client/notification/read') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="notification_id" value="<?= esc((string) $notification['notification_id']) ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Mark Read</button>
                                </form>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
