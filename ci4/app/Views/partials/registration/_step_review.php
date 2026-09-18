<?php
/** Step 5: Review & Certification. See _wizard.php for the $wizard contract. */
$certifyLabel = (string) ($wizard['certify_label'] ?? 'I certify that the information I provided is true and correct to the best of my knowledge.');
$submitLabel = (string) ($wizard['submit_label'] ?? 'Submit Registration');
?>
<!-- ============ STEP 5: Review & Certification ============ -->
<div class="wizard-panel d-none" data-step-panel="5">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Applicant Information</span>
            <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="1">Edit</button>
        </div>
        <div class="card-body small" id="reviewApplicant"></div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Government ID Verification</span>
            <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="2">Edit</button>
        </div>
        <div class="card-body small" id="reviewIdVerification"></div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Beneficiaries</span>
            <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="3">Edit</button>
        </div>
        <div class="card-body small" id="reviewBeneficiaries"></div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Emergency Contact</span>
            <button type="button" class="btn btn-link btn-sm p-0 wizard-edit" data-goto-step="4">Edit</button>
        </div>
        <div class="card-body small" id="reviewEmergency"></div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="h6 text-primary mb-2">Certification</h3>
            <p class="text-muted small mb-2">This is to certify that the above information is TRUE AND CORRECT to the best of my knowledge.</p>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="certify" name="certify" value="1" <?= old('certify') ? 'checked' : '' ?> required>
                <label class="form-check-label" for="certify">
                    <?= esc($certifyLabel) ?>
                </label>
            </div>
            <div class="wizard-nav mb-0">
                <button type="button" class="btn btn-outline-secondary wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
                <button type="submit" class="btn btn-primary"><?= esc($submitLabel) ?></button>
            </div>
        </div>
    </div>
</div>
