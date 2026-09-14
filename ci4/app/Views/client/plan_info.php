<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$program = $program ?? ['name' => 'Damayan Burial Program'];
$monthlyFee = (float) ($monthly_fee ?? 240.0);
$totalContribution = (float) ($total_contribution ?? 14500.0);
$entitlementPackage = $entitlement_package ?? null;
$otherPackages = $other_packages ?? [];
$services = $services ?? [];
$minMonths = (int) ceil($monthlyFee > 0 ? 480 / $monthlyFee : 2);
?>
<?php /* Entitlement panel has no cs-panel equivalent (gold "your benefit" call-out, same shape as package_details.php's). Plain info tiles kept as scoped .plan-fact boxes - close enough to caresync.css's own bordered-tile convention already used elsewhere (membership.php etc) that duplicating it as a "component" isn't worth it for one page. */ ?>
<style>
    .plan-fact { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem; height: 100%; }
    .entitlement-panel { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 2px solid #fcd34d; border-radius: 14px; padding: 1.5rem; }
    .other-package-row { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px dashed #e2e8f0; }
    .other-package-row:last-child { border-bottom: none; }
</style>

<div style="max-width: 900px;">
    <section class="cs-panel mb-3">
        <div class="cs-panel__body">
            <h5 class="mb-3"><?= esc((string) ($program['name'] ?? 'Damayan Burial Program')) ?></h5>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="plan-fact">
                        <small class="text-muted d-block">Monthly Contribution</small>
                        <strong class="fs-5"><?= cs_money($monthlyFee) ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="plan-fact">
                        <small class="text-muted d-block">Initial Payment (to activate)</small>
                        <strong class="fs-5"><?= cs_money($monthlyFee * max(2, $minMonths)) ?></strong>
                        <div class="text-muted small">Minimum <?= esc((string) max(2, $minMonths)) ?> months up front</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="plan-fact">
                        <small class="text-muted d-block">Full Contribution Target</small>
                        <strong class="fs-5"><?= cs_money($totalContribution) ?></strong>
                        <div class="text-muted small">Unlocks your free casket benefit</div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-0">
                Once you've completed at least 2 months of contributions, you can already avail or claim from the
                Services &amp; Packages catalog. Once your total contributions reach <?= cs_money($totalContribution) ?>,
                your standard casket benefit below becomes free to claim.
            </p>
        </div>
    </section>

    <?php if ($entitlementPackage): ?>
        <div class="entitlement-panel mb-3">
            <h5 class="mb-2"><i class="ti ti-award me-2"></i>Your Damayan Entitlement</h5>
            <p class="mb-3">
                Every Damayan Plan Holder is entitled to claim a
                <strong><?= esc((string) $entitlementPackage['package_name']) ?></strong>
                (worth <?= cs_money($entitlementPackage['base_price']) ?>) at
                <strong>no additional cost</strong> once your <?= cs_money($totalContribution) ?> contribution
                target is fully paid.
            </p>

            <p class="fw-semibold mb-2">What's Included</p>
            <?php $this->setData(['inclusions' => $inclusions ?? [], 'compact' => true]); ?>
            <?= $this->include('client/partials/inclusions_list') ?>
        </div>
    <?php endif; ?>

    <?php if (! empty($otherPackages)): ?>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Other Casket Options</h2></div>
            <div class="cs-panel__body">
                <p class="text-muted small mb-2">
                    Prefer a different casket? These are also available - as an active Damayan Plan Holder in good
                    standing, a <?= cs_money($totalContribution) ?> benefit credit is applied toward any of
                    them, waiving the remaining unpaid contribution rather than adding it on top.
                </p>
                <?php foreach ($otherPackages as $pkg): ?>
                    <div class="other-package-row">
                        <span><?= esc((string) $pkg['package_name']) ?></span>
                        <span class="fw-semibold"><?= cs_money($pkg['base_price']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (! empty($services)): ?>
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Other Services</h2></div>
            <div class="cs-panel__body">
                <?php foreach ($services as $svc): ?>
                    <div class="mb-2">
                        <div class="fw-semibold"><?= esc((string) $svc['service_name']) ?></div>
                        <div class="text-muted small"><?= esc((string) ($svc['description'] ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="cs-panel">
        <div class="cs-panel__body">
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" id="agreePlan">
                <label class="form-check-label" for="agreePlan">I agree to the terms and conditions</label>
            </div>

            <a id="proceedBtn" href="<?= base_url('plan-registration') ?>" class="btn btn-primary mt-3 disabled" aria-disabled="true">Proceed to Registration</a>
        </div>
    </section>
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
