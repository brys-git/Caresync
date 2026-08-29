<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Plan Holder Registration Approvals</h1>
            <p class="text-muted mb-0">Review pending account registration requests before granting plan holder access.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Branch</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Reviewed By</th>
                            <th style="min-width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <?php
                                $status = (string) ($registration['status'] ?? 'pending');
                                $badgeClass = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                $fullAddress = trim(
                                    (string) ($registration['address_no'] ?? '')
                                    . ' ' . (string) ($registration['address_street'] ?? '')
                                    . ', ' . (string) ($registration['address_barangay'] ?? '')
                                    . ', ' . (string) ($registration['address_city'] ?? '')
                                );
                                $reviewerName = trim((string) ($registration['reviewer_first_name'] ?? '') . ' ' . (string) ($registration['reviewer_last_name'] ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($registration['first_name'] ?? '')) ?> <?= esc((string) ($registration['last_name'] ?? '')) ?></div>
                                    <small class="text-muted"><?= esc((string) ($registration['email'] ?? '-')) ?></small>
                                </td>
                                <td><?= esc((string) ($registration['branch_name'] ?? '-')) ?></td>
                                <td><?= esc((string) ($registration['contact_number'] ?? '-')) ?></td>
                                <td>
                                    <div><?= esc($fullAddress !== '' ? $fullAddress : '-') ?></div>
                                    <small class="text-muted">DOB: <?= esc((string) ($registration['date_of_birth'] ?? '-')) ?></small>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= esc($badgeClass) ?> text-uppercase"><?= esc($status) ?></span>
                                    <?php if ($status === 'rejected' && ! empty($registration['rejection_notes'])): ?>
                                        <div class="small text-danger mt-1">Reason: <?= esc((string) $registration['rejection_notes']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string) ($registration['created_at'] ?? '-')) ?></td>
                                <td>
                                    <?= esc($reviewerName !== '' ? $reviewerName : '-') ?>
                                    <?php if (! empty($registration['reviewed_at'])): ?>
                                        <div class="small text-muted"><?= esc((string) $registration['reviewed_at']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status === 'pending'): ?>
                                        <div class="d-flex flex-column gap-2">
                                            <form method="post" action="<?= base_url('plan-holders/approvals/approve/' . (int) $registration['pending_registration_id']) ?>" class="m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-success btn-sm w-100">Approve Registration</button>
                                            </form>
                                            <form method="post" action="<?= base_url('plan-holders/approvals/reject/' . (int) $registration['pending_registration_id']) ?>" class="m-0">
                                                <?= csrf_field() ?>
                                                <textarea name="rejection_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional rejection reason"></textarea>
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Reject Registration</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Already reviewed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($registrations)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No registration requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
