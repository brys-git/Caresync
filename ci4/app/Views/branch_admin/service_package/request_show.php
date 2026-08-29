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
                <div class="col-md-6"><strong>Price:</strong> P<?= esc(number_format((float) (($request['service_price'] ?? $request['package_price']) ?? 0), 2)) ?></div>
            </div>
        </div>
    </div>

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
                            <button type="submit" class="btn btn-success">Approve</button>
                        </form>
                        <form action="<?= site_url('/branch-admin/service-package/requests/reject/' . (int) $request['application_id']) ?>" method="post" class="d-inline-block">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </form>
                    <?php else: ?>
                        <div class="text-muted">This request is <?= esc(ucfirst((string) ($request['status'] ?? 'unknown'))) ?>.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
