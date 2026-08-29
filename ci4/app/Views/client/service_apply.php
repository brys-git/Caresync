<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $state = (string) ($access['state'] ?? 'new');
    $canApply = (bool) ($can_apply ?? false);
?>
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Apply for Service</h1>
            <p class="text-muted mb-0">Confirm your service request.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service/' . (int) ($service['service_list_id'] ?? 0)) ?>">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (! $canApply): ?>
        <?php if ($state === 'pending'): ?>
            <div class="alert alert-warning">Approval required before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-2"><?= esc((string) ($service['service_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($service['description'] ?? 'No description available.')) ?></p>
            <div class="fw-semibold">Price: P<?= esc(number_format((float) ($service['base_price'] ?? 0), 2)) ?></div>
        </div>
    </div>

    <form class="mt-3" method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Deceased full name</label>
            <input type="text" name="deceased_name" class="form-control" value="<?= old('deceased_name') ?>" required />
        </div>
        <div class="mb-3 row">
            <div class="col-md-6">
                <label class="form-label">Date of death</label>
                <input type="date" name="deceased_date_of_death" class="form-control" value="<?= old('deceased_date_of_death') ?>" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Relationship to deceased</label>
                <input type="text" name="relationship_to_deceased" class="form-control" value="<?= old('relationship_to_deceased') ?>" />
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Deceased address</label>
            <input type="text" name="deceased_address" class="form-control" value="<?= old('deceased_address') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Beneficiary name</label>
            <input type="text" name="beneficiary_name" class="form-control" value="<?= old('beneficiary_name') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Beneficiary contact number</label>
            <input type="text" name="beneficiary_contact" class="form-control" value="<?= old('beneficiary_contact') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Upload supporting documents (IDs, death certificate)</label>
            <input type="file" name="documents[]" multiple class="form-control" accept="image/*,application/pdf" />
        </div>
        <button class="btn btn-primary" type="submit" <?= $canApply ? '' : 'disabled' ?>>Submit Application</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=services') ?>">Cancel</a>
    </form>
</div>
<?= $this->endSection() ?>
