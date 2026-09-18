<?php
/** Step 4: Emergency Contact. See _wizard.php for the $wizard contract. */
$planHolder = $wizard['plan_holder'] ?? [];
?>
<!-- ============ STEP 4: Emergency Contact ============ -->
<div class="wizard-panel d-none" data-step-panel="4">
    <div class="card">
        <div class="card-body">
            <h3 class="h6 text-primary mb-3">Emergency Contact</h3>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="emergency_contact_name">Name</label>
                    <input id="emergency_contact_name" name="emergency_contact_name" class="form-control" value="<?= esc(old('emergency_contact_name', (string) ($planHolder['emergency_contact_name'] ?? ''))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="emergency_contact_number">Contact #</label>
                    <input id="emergency_contact_number" name="emergency_contact_number" class="form-control" value="<?= esc(old('emergency_contact_number', (string) ($planHolder['emergency_contact_number'] ?? ''))) ?>">
                    <small class="text-danger d-none" id="emergencyContactError">Please enter a valid contact number (at least 10 digits).</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="emergency_contact_address">Address</label>
                    <input id="emergency_contact_address" name="emergency_contact_address" class="form-control" value="<?= esc(old('emergency_contact_address', (string) ($planHolder['emergency_contact_address'] ?? ''))) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="wizard-nav">
        <button type="button" class="btn btn-outline-secondary wizard-back"><i class="ti ti-arrow-left me-1"></i>Back</button>
        <button type="button" class="btn btn-primary wizard-next">Next: Review <i class="ti ti-arrow-right ms-1"></i></button>
    </div>
</div>
