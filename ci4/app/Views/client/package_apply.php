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
<div class="cs-narrow">
    <?php if (! $canApply): ?>
        <?php if ($state === 'pending'): ?>
            <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'message' => 'Approval required before requesting services.']) ?>
        <?php else: ?>
            <?= view('components/alert', ['type' => 'info', 'dismissible' => false, 'message' => 'You must register as a Plan Holder to apply.']) ?>
            <a class="btn btn-primary mb-3" href="<?= site_url('/plan-info') ?>">Register now</a>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)) ?>">
        <?= csrf_field() ?>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <div>
                    <h2 class="cs-panel__title"><?= esc((string) ($package['package_name'] ?? '—')) ?></h2>
                    <p class="cs-panel__note"><?= esc((string) ($package['description'] ?? 'No description available.')) ?></p>
                </div>
                <span class="fw-semibold"><?= cs_money($basePrice) ?></span>
            </div>
            <div class="cs-panel__body">
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="burial_attire" name="burial_attire" value="1" <?= old('burial_attire') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="burial_attire">
                        Add Burial Attire Package (optional add-on) — <?= cs_money($attirePrice) ?>
                    </label>
                </div>

                <?php if ($isEntitlement): ?>
                    <div class="cs-routerow"><span>Regular Wood Casket (Damayan entitlement)</span><?= cs_money($basePrice) ?></div>
                    <div class="cs-routerow cs-routerow--credit"><span>Damayan benefit applied</span><span>− <?= cs_money($basePrice) ?></span></div>
                <?php else: ?>
                    <div class="cs-routerow"><span>Package price</span><?= cs_money($basePrice) ?></div>
                    <?php if ($benefitCredit > 0): ?>
                        <div class="cs-routerow cs-routerow--credit"><span>Damayan benefit credit</span><span>− <?= cs_money($benefitCredit) ?></span></div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="cs-routerow d-none" id="attire-line"><span>Burial attire add-on</span><?= cs_money($attirePrice) ?></div>
                <div class="cs-routerow cs-routerow--total"><span>Amount due</span><span id="amount-due" class="cs-money"><?= esc(number_format($baseDue, 2)) ?></span></div>
            </div>
        </section>

        <section class="cs-panel">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Deceased and beneficiary details</h2></div>
            <div class="cs-panel__body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="deceased_name">Deceased full name</label>
                        <input type="text" id="deceased_name" name="deceased_name" class="form-control" value="<?= old('deceased_name') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="deceased_date_of_death">Date of death</label>
                        <input type="date" id="deceased_date_of_death" name="deceased_date_of_death" class="form-control" value="<?= old('deceased_date_of_death') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="relationship_to_deceased">Relationship to deceased</label>
                        <input type="text" id="relationship_to_deceased" name="relationship_to_deceased" class="form-control" value="<?= old('relationship_to_deceased') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="deceased_address">Deceased address</label>
                        <input type="text" id="deceased_address" name="deceased_address" class="form-control" value="<?= old('deceased_address') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="beneficiary_name">Beneficiary name</label>
                        <input type="text" id="beneficiary_name" name="beneficiary_name" class="form-control" value="<?= old('beneficiary_name') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="beneficiary_contact">Beneficiary contact number</label>
                        <input type="text" id="beneficiary_contact" name="beneficiary_contact" class="form-control" value="<?= old('beneficiary_contact') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="documents">Upload supporting documents (IDs, death certificate)</label>
                        <input type="file" id="documents" name="documents[]" multiple class="form-control" accept="image/*,application/pdf">
                    </div>
                </div>
            </div>
            <div class="cs-panel__foot d-flex justify-content-end gap-2">
                <a class="btn btn-ghost" href="<?= site_url('/client/service?tab=packages') ?>">Cancel</a>
                <button class="btn btn-primary" type="submit" <?= $canApply ? '' : 'disabled' ?>><?= $isEntitlement ? 'Claim regular casket' : 'Avail package' ?></button>
            </div>
        </section>
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
        return n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function recalc() {
        var selected = checkbox.checked;
        attireLine.classList.toggle('d-none', !selected);
        amountDue.textContent = format(baseDue + (selected ? attirePrice : 0));
    }

    if (checkbox) {
        checkbox.addEventListener('change', recalc);
        recalc();
    }
});
</script>
<?= $this->endSection() ?>
