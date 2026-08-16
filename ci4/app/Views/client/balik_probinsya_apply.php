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
$rates = $rates ?? [];
$origins = array_unique(array_column($rates, 'origin'));
$destinations = array_unique(array_column($rates, 'destination'));
?>
<link rel="stylesheet" href="<?= base_url('assets/css/damayan-calc.css') ?>">
<div class="container-fluid">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Apply for Balik Probinsya</h1>
            <p class="text-muted mb-0">Transportation of deceased to Mindoro and other provinces.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=services') ?>">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (! $canApply): ?>
        <?php if ($state === 'awaiting_activation'): ?>
            <div class="alert alert-warning">Your membership is pending activation. Complete your initial payment before requesting services.</div>
        <?php else: ?>
            <div class="alert alert-info">You must register as a Plan Holder to apply.</div>
            <a class="btn btn-primary" href="<?= site_url('/plan-info') ?>">Register Now</a>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2"><?= esc((string) ($service['service_name'] ?? 'Balik Probinsya')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($service['description'] ?? 'Transportation of deceased from Metro Manila/nearby provinces to Mindoro and other provinces')) ?></p>
        </div>
    </div>

    <form class="mt-3" method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="service_type" value="balik_probinsya">

        <!-- Origin/Destination Selection with Real-time Price -->
        <div class="card mb-3">
            <div class="card-header"><strong>Route Selection & Pricing</strong></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Origin <span class="text-danger">*</span></label>
                        <select name="origin" id="origin" class="form-select" required>
                            <option value="">Select origin</option>
                            <?php foreach ($origins as $o): ?>
                                <option value="<?= esc($o) ?>" <?= old('origin') === $o ? 'selected' : '' ?>><?= esc($o) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destination <span class="text-danger">*</span></label>
                        <select name="destination" id="destination" class="form-select" required>
                            <option value="">Select destination</option>
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?= esc($d) ?>" <?= old('destination') === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Price Display -->
                <div class="alert alert-info d-flex justify-content-between align-items-center" id="price-display" style="display:none;">
                    <div>
                        <strong>Route Price:</strong>
                        <span id="route-price-label">₱0.00</span>
                        <small class="text-muted d-block" id="rate-description"></small>
                    </div>
                </div>

                <!-- Damayan Summary -->
                <div class="mt-3 border rounded p-3 bg-light">
                    <h6 class="mb-2">Price Summary</h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Balik Probinsya (<span id="route-label">Select route</span>)</span>
                        <strong>₱<span id="service-price-label">0.00</span></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span>
                        <strong>₱<span id="subtotal-label">0.00</span></strong>
                    </div>
                    <?php if ($isDamayanMember ?? false): ?>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Damayan Benefit (₱14,500 credit)</span>
                            <strong>-₱<span id="damayan-label">0.00</span></strong>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold">Final Amount</span>
                        <strong class="text-primary">₱<span id="final-label">0.00</span></strong>
                    </div>
                    <?php if ($isDamayanMember ?? false): ?>
                        <div class="alert alert-success mt-2 mb-0 py-2 small">
                            <i class="mdi mdi-shield-check"></i> You are a qualified Damayan member. The ₱14,500 Damayan benefit is applied as a credit. Remaining contributions will be waived.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Deceased Information -->
        <div class="card mb-3">
            <div class="card-header"><strong>Deceased Information</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Deceased full name <span class="text-danger">*</span></label>
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
                        <label class="form-label">Date of death <span class="text-danger">*</span></label>
                        <input type="date" name="deceased_date_of_death" class="form-control" value="<?= old('deceased_date_of_death') ?>" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relationship to deceased <span class="text-danger">*</span></label>
                        <input type="text" name="relationship_to_deceased" class="form-control" value="<?= old('relationship_to_deceased') ?>" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deceased address</label>
                    <input type="text" name="deceased_address" class="form-control" value="<?= esc(old('deceased_address', $planHolderAddress)) ?>" readonly />
                    <div class="form-text">This now uses the plan holder's registered address to keep the process simple.</div>
                </div>
                <input type="hidden" name="beneficiary_name" value="<?= esc($planHolderName) ?>" />
            </div>
        </div>

        <!-- Applicant Details -->
        <div class="card mb-3">
            <div class="card-header"><strong>Applicant Details (Auto-filled from Plan Holder)</strong></div>
            <div class="card-body">
                <div class="form-text mb-3">Applicant details are automatically filled from the plan holder profile.</div>
                <div class="mb-3">
                    <label class="form-label">Beneficiary contact number <span class="text-danger">*</span></label>
                    <input type="text" name="beneficiary_contact" class="form-control" value="<?= old('beneficiary_contact') ?>" required />
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card mb-3">
            <div class="card-header"><strong>Supporting Documents</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Upload supporting documents (IDs, death certificate) <span class="text-danger">*</span></label>
                    <input type="file" name="documents[]" multiple class="form-control" accept="image/*,application/pdf" required />
                    <div class="form-text">Required: Death certificate, valid ID of deceased, valid ID of applicant.</div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary" type="submit" <?= $canApply ? '' : 'disabled' ?>>Submit Application</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=services') ?>">Cancel</a>
    </form>
</div>

<script>
(function() {
    const originSelect = document.getElementById('origin');
    const destinationSelect = document.getElementById('destination');
    const priceDisplay = document.getElementById('price-display');
    const routePriceLabel = document.getElementById('route-price-label');
    const rateDescription = document.getElementById('rate-description');
    const routeLabel = document.getElementById('route-label');
    const servicePriceLabel = document.getElementById('service-price-label');
    const subtotalLabel = document.getElementById('subtotal-label');
    const finalLabel = document.getElementById('final-label');
    const damayanLabel = document.getElementById('damayan-label');

    const isDamayan = <?= isset($isDamayanMember) ? 'true' : 'false' ?>;
    const DAMAYAN_CREDIT = 14500;

    // Rate data embedded from PHP
    const ratesData = <?= json_encode($rates) ?>;

    function recalc() {
        const origin = originSelect.value;
        const destination = destinationSelect.value;

        if (!origin || !destination) {
            priceDisplay.style.display = 'none';
            routeLabel.textContent = 'Select route';
            servicePriceLabel.textContent = '0.00';
            subtotalLabel.textContent = '0.00';
            finalLabel.textContent = '0.00';
            if (damayanLabel) damayanLabel.textContent = '0.00';
            return;
        }

        // Find matching rate
        const rate = ratesData.find(r => r.origin === origin && r.destination === destination);
        if (!rate) {
            priceDisplay.style.display = 'none';
            routeLabel.textContent = 'No rate for this route';
            servicePriceLabel.textContent = '0.00';
            subtotalLabel.textContent = '0.00';
            finalLabel.textContent = '0.00';
            if (damayanLabel) damayanLabel.textContent = '0.00';
            return;
        }

        const price = parseFloat(rate.rate || 0);
        priceDisplay.style.display = 'flex';
        routePriceLabel.textContent = '₱' + price.toFixed(2);
        rateDescription.textContent = rate.description || '';
        routeLabel.textContent = origin + ' → ' + destination;
        servicePriceLabel.textContent = price.toFixed(2);
        subtotalLabel.textContent = price.toFixed(2);

        let final = price;
        if (isDamayan && price > 20000) {
            final = price - DAMAYAN_CREDIT;
            if (damayanLabel) damayanLabel.textContent = DAMAYAN_CREDIT.toFixed(2);
        } else if (isDamayan && damayanLabel) {
            damayanLabel.textContent = '0.00';
        }

        finalLabel.textContent = Math.max(0, final).toFixed(2);
    }

    originSelect.addEventListener('change', recalc);
    destinationSelect.addEventListener('change', recalc);

    // Initial calculation if values are pre-filled
    if (originSelect.value && destinationSelect.value) {
        recalc();
    }
})();
</script>
<?= $this->endSection() ?>