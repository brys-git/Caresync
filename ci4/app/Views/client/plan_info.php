<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$program = $program ?? [
    'name' => 'Damayan Burial Program',
    'monthly_fee' => 240.0,
];
?>
<div class="container-fluid">
    <div class="mb-3">
        <h1 class="h3 mb-1">Plan Information</h1>
        <p class="text-muted mb-0">Review the Damayan program details before proceeding.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <h5><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></h5>
            <p class="mb-2"><strong>Monthly Contribution:</strong> P<?= number_format((float) ($program['monthly_fee'] ?? 240), 2) ?></p>
            <p class="mb-2"><strong>Benefits:</strong></p>
            <ul>
                <li>Body retrieval &amp; preparation</li>
                <li>Documentation assistance</li>
                <li>Funeral setup (flowers, tarpaulin, etc.)</li>
                <li>Hearse service</li>
            </ul>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="agreePlan">
                <label class="form-check-label" for="agreePlan">I agree to the terms and conditions</label>
            </div>

            <a id="proceedBtn" href="<?= base_url('plan-registration') ?>" class="btn btn-primary mt-3 disabled" aria-disabled="true">Proceed to Registration</a>
        </div>
    </div>
</div>

<script>
    (function () {
        const checkbox = document.getElementById('agreePlan');
        const button = document.getElementById('proceedBtn');

        if (!checkbox || !button) {
            return;
        }

        checkbox.addEventListener('change', function () {
            if (checkbox.checked) {
                button.classList.remove('disabled');
                button.setAttribute('aria-disabled', 'false');
            } else {
                button.classList.add('disabled');
                button.setAttribute('aria-disabled', 'true');
            }
        });
    })();
</script>
<?= $this->endSection() ?>
