<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Registration Approvals</h1>
        <p class="text-muted mb-0">Pending initial payments requiring verification before full access activation.</p>
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
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Client</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Government ID</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>#<?= esc((string) $row['payment_id']) ?></td>
                                <td>
                                    <?= esc((string) $row['first_name'] . ' ' . $row['last_name']) ?><br>
                                    <small class="text-muted"><?= esc((string) $row['email']) ?></small>
                                </td>
                                <td><?= esc((string) ($row['branch_name'] ?? '-')) ?></td>
                                <td><?= esc((string) $row['payment_date']) ?></td>
                                <td>P<?= esc(number_format((float) $row['amount'], 2)) ?></td>
                                <td><?= esc(strtoupper((string) $row['payment_method'])) ?></td>
                                <td><?= esc((string) ($row['reference_number'] ?: '-')) ?></td>
                                <td><span class="badge text-bg-warning">Pending</span></td>
                                <td>
                                    <?php $idv = $row['id_verification'] ?? null; ?>
                                    <?php if ($idv === null): ?>
                                        <span class="badge text-bg-secondary">No attempt</span>
                                    <?php elseif ((string) $idv['verification_status'] === 'verified'): ?>
                                        <span class="badge text-bg-success">Verified</span>
                                    <?php elseif ((string) $idv['verification_status'] === 'needs_review'): ?>
                                        <span class="badge text-bg-warning">ID needs manual review</span>
                                        <details class="small mt-1">
                                            <summary class="text-muted" style="cursor: pointer;">Extracted vs entered</summary>
                                            <div class="mt-1">
                                                <div><strong>On the ID:</strong>
                                                    <?= esc((string) ($idv['extracted_name'] ?? 'Not read')) ?>
                                                    <?php if (! empty($idv['extracted_birth_date'])): ?>, DOB <?= esc((string) $idv['extracted_birth_date']) ?><?php endif; ?>
                                                    <?php if (! empty($idv['extracted_gender'])): ?>, <?= esc((string) $idv['extracted_gender']) ?><?php endif; ?>
                                                </div>
                                                <div><strong>Entered:</strong>
                                                    <?= esc(trim((string) ($idv['claimed_first_name'] ?? '') . ' ' . (string) ($idv['claimed_middle_name'] ?? '') . ' ' . (string) ($idv['claimed_last_name'] ?? '')) ?: '-') ?>
                                                    <?php if (! empty($idv['claimed_date_of_birth'])): ?>, DOB <?= esc((string) $idv['claimed_date_of_birth']) ?><?php endif; ?>
                                                    <?php if (! empty($idv['claimed_gender'])): ?>, <?= esc((string) $idv['claimed_gender']) ?><?php endif; ?>
                                                </div>
                                                <?php if (! empty($idv['mismatch_reason'])): ?>
                                                    <div class="text-muted"><?= esc((string) $idv['mismatch_reason']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    <?php elseif ((string) $idv['verification_status'] === 'failed'): ?>
                                        <span class="badge text-bg-danger">Failed</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary"><?= esc(ucfirst((string) $idv['verification_status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (! empty($can_verify)): ?>
                                        <form method="post" action="<?= base_url('payments/verify-initial/' . (int) $row['payment_id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-primary">Verify</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">View only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No pending registrations for approval.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
