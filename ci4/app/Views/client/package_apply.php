<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $state = (string) ($access['state'] ?? 'new');
    $canApply = (bool) ($can_apply ?? false);
    $isEntitlement = (bool) ($is_entitlement ?? false);
    $benefitCredit = (float) ($benefit_credit ?? 0);
    $attirePrice = (float) ($attire_price ?? 0);
    $basePrice = (float) ($package['base_price'] ?? 0);
    $baseDue = $isEntitlement ? 0.0 : max(0, $basePrice - $benefitCredit);
?>
<style>
    .summary-box { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem 1.25rem; background: #f8fafc; }
    .summary-line { display: flex; justify-content: space-between; padding: .35rem 0; }
    .summary-total { border-top: 1px solid #cbd5e1; margin-top: .5rem; padding-top: .5rem; font-weight: 700; }
</style>
<div class="container-fluid" style="max-width: 760px;">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $isEntitlement ? 'Claim Regular Casket' : 'Avail Package' ?></h1>
            <p class="text-muted mb-0">
                <?= $isEntitlement
                    ? 'Claim your Damayan entitlement. Staff or an Encoder will review and process your claim.'
                    : 'Confirm your package selection. Staff or an Encoder will review and process your application.' ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/package/' . (int) ($package['package_id'] ?? 0)) ?>">Back</a>
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

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-2"><?= esc((string) ($package['package_name'] ?? '-')) ?></h5>
            <p class="text-muted mb-3"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
            <div class="fw-semibold">Package Price: P<?= esc(number_format($basePrice, 2)) ?></div>
        </div>
    </div>

    <form class="mt-3" method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">
        <?= csrf_field() ?>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="burial_attire" name="burial_attire" value="1" <?= old('burial_attire') ? 'checked' : '' ?>>
            <label class="form-check-label" for="burial_attire">
                Add Burial Attire Package (Optional Add-on) - ₱<?= number_format($attirePrice, 2) ?>
            </label>
        </div>

        <div class="summary-box mb-4">
            <?php if ($isEntitlement): ?>
                <div class="summary-line"><span>Regular Wood Casket (Damayan Entitlement)</span><span>₱<?= number_format($basePrice, 2) ?></span></div>
                <div class="summary-line text-success"><span>Damayan Benefit Applied</span><span>- ₱<?= number_format($basePrice, 2) ?></span></div>
            <?php else: ?>
                <div class="summary-line"><span>Package Price</span><span>₱<?= number_format($basePrice, 2) ?></span></div>
                <?php if ($benefitCredit > 0): ?>
                    <div class="summary-line text-success"><span>Damayan Benefit Credit</span><span>- ₱<?= number_format($benefitCredit, 2) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="summary-line" id="attire-line" style="display:none;"><span>Burial Attire Add-on</span><span>₱<?= number_format($attirePrice, 2) ?></span></div>
            <div class="summary-line summary-total"><span>Amount Due</span><span id="amount-due">₱<?= number_format($baseDue, 2) ?></span></div>
        </div>

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
        <button class="btn <?= $isEntitlement ? 'btn-claim-big' : 'btn-primary' ?>" style="<?= $isEntitlement ? 'background-color:#b45309;border-color:#b45309;' : '' ?>" type="submit" <?= $canApply ? '' : 'disabled' ?>>
            <?= $isEntitlement ? '[ CLAIM REGULAR CASKET ]' : '[ AVAIL PACKAGE ]' ?>
        </button>
        <a class="btn btn-outline-secondary" href="<?= site_url('/client/service?tab=packages') ?>">Cancel</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('burial_attire');
    var attireLine = document.getElementById('attire-line');
    var amountDue = document.getElementById('amount-due');
    var baseDue = <?= json_encode($baseDue) ?>;
    var attirePrice = <?= json_encode($attirePrice) ?>;

    function format(n) {
        return '₱' + n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function recalc() {
        var selected = checkbox.checked;
        attireLine.style.display = selected ? 'flex' : 'none';
        amountDue.textContent = format(baseDue + (selected ? attirePrice : 0));
    }

    if (checkbox) {
        checkbox.addEventListener('change', recalc);
        recalc();
    }
});
</script>
<?= $this->endSection() ?>
