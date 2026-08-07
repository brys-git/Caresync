<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $state = (string) ($access['state'] ?? 'new');
    $canApply = (bool) ($can_apply ?? false);
    $applicationContext = $application_context ?? [];
    $planHolderName = (string) ($applicationContext['plan_holder_name'] ?? trim((string) (($access['user']['first_name'] ?? '') . ' ' . ($access['user']['middle_name'] ?? '') . ' ' . ($access['user']['last_name'] ?? ''))));
    $planHolderAddress = (string) ($applicationContext['plan_holder_address'] ?? '-');
    $deceasedNameOptions = (array) ($applicationContext['deceased_name_options'] ?? []);
    $selectedDeceasedName = (string) old('deceased_name', $planHolderName);
?>
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Apply for Package</h1>
            <p class="text-muted mb-0">Confirm your package request.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service/package/' . (int) ($package['package_id'] ?? 0)) ?>">Back</a>
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
            <h5 class="mb-2"><?= esc((string) ($package['package_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
            <div class="fw-semibold">Price: P<?= esc(number_format((float) ($package['base_price'] ?? 0), 2)) ?></div>
        </div>
    </div>

    <form class="mt-3" method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Deceased full name</label>
            <select name="deceased_name" class="form-select" required>
                <option value="">Select a name</option>
                <?php foreach ($deceasedNameOptions as $option): ?>
                    <option value="<?= esc($option) ?>" <?= $selectedDeceasedName === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Choose the plan holder or one of the registered beneficiaries.</div>
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
            <input type="text" name="deceased_address" class="form-control" value="<?= esc(old('deceased_address', $planHolderAddress)) ?>" readonly />
            <div class="form-text">This now uses the plan holder's registered address to keep the process simple.</div>
        </div>
        <input type="hidden" name="beneficiary_name" value="<?= esc($planHolderName) ?>" />
        <div class="form-text mb-3">Applicant details are automatically filled from the plan holder profile.</div>
        <div class="mb-3">
            <label class="form-label">Beneficiary contact number</label>
            <input type="text" name="beneficiary_contact" class="form-control" value="<?= old('beneficiary_contact') ?>" />
        </div>
        <div class="mb-3">
            <label class="form-label">Upload supporting documents (IDs, death certificate)</label>
            <input type="file" name="documents[]" multiple class="form-control" accept="image/*,application/pdf" />
        </div>
        <button class="btn btn-primary" type="submit" <?= $canApply ? '' : 'disabled' ?>>Submit Application</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=packages') ?>">Cancel</a>
    </form>
</div>
<?= $this->endSection() ?>
