<?= $this->extend($role_layout ?? 'layouts/branch_admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Service Request Details</h4>
            <p class="text-muted mb-0">Review the submitted application and supporting documents before approving or rejecting.</p>
        </div>
        <a href="<?= site_url('/branch-admin/service-package/requests') ?>" class="btn btn-outline-secondary">Back to Requests</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-4"><strong>Applicant:</strong> <?= esc((string) ($request['first_name'] ?? '') . ' ' . (string) ($request['last_name'] ?? '')) ?></div>
                <div class="col-md-4"><strong>Request ID:</strong> <?= esc((string) ($request['application_id'] ?? '-')) ?></div>
                <div class="col-md-4"><strong>Status:</strong> <?= esc(ucfirst((string) ($request['status'] ?? '-'))) ?></div>
                <div class="col-md-6"><strong>Requested on:</strong> <?= esc((string) ($request['created_at'] ?? '-')) ?></div>
                <div class="col-md-6"><strong>Branch ID:</strong> <?= esc((string) ($request['branch_id'] ?? '-')) ?></div>
                <div class="col-md-6"><strong>Type:</strong> <?= esc((string) (($request['service_list_id'] ?? 0) > 0 ? 'Service' : 'Package')) ?></div>
                <div class="col-md-6"><strong>Selected:</strong> <?= esc((string) ($request['service_name'] ?? $request['package_name'] ?? '-')) ?></div>
                <div class="col-md-6"><strong>Base Price:</strong> P<?= esc(number_format((float) ($packagePrice ?? 0), 2)) ?></div>
            </div>
        </div>
    </div>

    <!-- Damayan Benefit Calculation -->
    <?php if (isset($benefitCalc) && isset($membershipSummary)): ?>
    <div class="card mb-3 border-info">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="mdi mdi-account-heart-outline me-2"></i>Damayan Burial Program - Benefit Calculation</h5>
        </div>
        <div class="card-body">
            <div class="row gy-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded">
                        <div class="small text-muted">Member Status</div>
                        <div class="fw-bold">
                            <?= $benefitCalc['is_damayan_eligible']
                                ? '<span class="badge bg-success">Qualified Damayan Member</span>'
                                : '<span class="badge bg-warning text-dark">Non-Damayan / Not Qualified</span>' ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded">
                        <div class="small text-muted">Standard Package Value</div>
                        <div class="fw-bold">₱<?= number_format(\App\Services\DamayanService::STANDARD_PACKAGE_VALUE, 2) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded">
                        <div class="small text-muted">Damayan Benefit Value</div>
                        <div class="fw-bold">₱<?= number_format(\App\Services\DamayanService::BENEFIT_VALUE, 2) ?></div>
                    </div>
                </div>
            </div>

            <?php if ($benefitCalc['is_damayan_eligible']): ?>
            <div class="alert alert-success mb-3">
                <h6 class="alert-heading"><i class="mdi mdi-check-circle-outline me-2"></i>Member is eligible for Damayan benefits!</h6>
                <hr>
                <?php if ($benefitCalc['standard_entitlement']): ?>
                <div class="mb-2">
                    <strong>Standard Entitlement Applied:</strong> Package price (₱<?= number_format($packagePrice, 2) ?>) ≤ Standard Package Value (₱<?= number_format(\App\Services\DamayanService::STANDARD_PACKAGE_VALUE, 2) ?>)
                </div>
                <div class="mb-0">
                    <strong>Family Pays:</strong> <span class="text-success fw-bold fs-5">₱0.00</span>
                </div>
                <div class="small text-muted mt-2">Note: Remaining contributions will be waived upon approval.</div>
                <?php else: ?>
                <div class="mb-2">
                    <strong>Upgrade Package:</strong> Package price (₱<?= number_format($packagePrice, 2) ?>) > Standard Package Value (₱<?= number_format(\App\Services\DamayanService::STANDARD_PACKAGE_VALUE, 2) ?>)
                </div>
                <div class="row gy-2">
                    <div class="col-md-6">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Damayan Benefit Credit</div>
                            <div class="fw-bold text-success">- ₱<?= number_format($benefitCalc['damayan_benefit_credit'], 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-light rounded">
                            <div class="small text-muted">Upgrade Amount (Family Pays)</div>
                            <div class="fw-bold text-danger">₱<?= number_format($benefitCalc['upgrade_amount'], 2) ?></div>
                        </div>
                    </div>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <strong>Final Amount Due:</strong>
                    <strong class="fs-5 text-primary">₱<?= number_format($benefitCalc['final_amount_due'], 2) ?></strong>
                </div>
                <div class="small text-muted mt-2">Note: Remaining contributions will be waived upon approval.</div>
                <?php endif; ?>
            </div>

            <?php if ($membershipSummary): ?>
            <div class="card mb-0">
                <div class="card-header">
                    <h6 class="mb-0">Membership Contribution Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-3">
                            <div class="small text-muted">Monthly Fee</div>
                            <div class="fw-bold">₱<?= number_format($membershipSummary['monthly_fee'], 2) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Months Paid</div>
                            <div class="fw-bold"><?= (int) $membershipSummary['months_paid'] ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Total Paid</div>
                            <div class="fw-bold">₱<?= number_format($membershipSummary['amount_paid'], 2) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Remaining Balance</div>
                            <div class="fw-bold text-warning">₱<?= number_format($membershipSummary['remaining_balance'], 2) ?></div>
                        </div>
                    </div>
                    <?php if ($membershipSummary['remaining_balance'] > 0): ?>
                    <div class="alert alert-warning py-2 mt-3 mb-0">
                        <i class="mdi mdi-alert-outline me-2"></i>
                        <strong>Upon approval:</strong> The remaining balance of <strong>₱<?= number_format($membershipSummary['remaining_balance'], 2) ?></strong> will be <strong>waived</strong> per KAAGAPAY Damayan policy.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="alert alert-warning mb-3">
                <h6 class="alert-heading"><i class="mdi mdi-alert-circle-outline me-2"></i>Member is NOT eligible for Damayan benefits</h6>
                <p class="mb-0">Full package price applies. No benefit credit or contribution waiver.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Deceased Information</div>
                <div class="card-body">
                    <div class="mb-3"><strong>Name:</strong> <?= esc((string) ($request['deceased_name'] ?? '-')) ?></div>
                    <div class="mb-3"><strong>Date of death:</strong> <?= esc((string) ($request['deceased_date_of_death'] ?? '-')) ?></div>
                    <div class="mb-3"><strong>Relationship:</strong> <?= esc((string) ($request['relationship_to_deceased'] ?? '-')) ?></div>
                    <div class="mb-3"><strong>Address:</strong> <?= esc((string) ($request['deceased_address'] ?? '-')) ?></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">Beneficiary Details</div>
                <div class="card-body">
                    <div class="mb-3"><strong>Name:</strong> <?= esc((string) ($request['beneficiary_name'] ?? '-')) ?></div>
                    <div class="mb-3"><strong>Contact:</strong> <?= esc((string) ($request['beneficiary_contact'] ?? '-')) ?></div>
                    <div class="mb-3"><strong>Notes:</strong> <?= esc((string) ($request['application_notes'] ?? '-')) ?></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Supporting Documents</div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <div class="text-muted">No documents uploaded yet.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($documents as $document): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= esc((string) ($document['original_name'] ?? 'Unnamed document')) ?></span>
                                    <a href="<?= site_url('/branch-admin/service-package/requests/document/' . (int) $document['document_id']) ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Actions</div>
                <div class="card-body">
                    <?php if (($request['status'] ?? '') === 'pending'): ?>
                        <form action="<?= site_url('/branch-admin/service-package/requests/approve/' . (int) $request['application_id']) ?>" method="post" class="d-inline-block me-2">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this request?')">Approve</button>
                        </form>
                        <div class="mt-3">
                            <form action="<?= site_url('/branch-admin/service-package/requests/reject/' . (int) $request['application_id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Rejection Reason (optional)</label>
                                    <textarea name="rejection_reason" class="form-control form-control-sm" rows="2" placeholder="Provide a reason for rejection..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this request?')">Reject</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">This request is <?= esc(ucfirst((string) ($request['status'] ?? 'unknown'))) ?>.</div>
                        <?php if (! empty($request['rejection_reason'] ?? '')): ?>
                            <div class="alert alert-warning mt-2 mb-0"><strong>Rejection reason:</strong> <?= esc((string) $request['rejection_reason']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
