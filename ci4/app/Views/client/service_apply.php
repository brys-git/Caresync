<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
    $state = (string) ($access['state'] ?? 'new');
    $canApply = (bool) ($can_apply ?? false);
    $routes = $routes ?? [];
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

    <form method="post" enctype="multipart/form-data" action="<?= site_url('/client/apply-service/' . (int) ($service['service_list_id'] ?? 0)) ?>">
        <?= csrf_field() ?>

        <section class="cs-panel mb-3">
            <div class="cs-panel__head">
                <div>
                    <h2 class="cs-panel__title"><?= esc((string) ($service['service_name'] ?? '—')) ?></h2>
                    <p class="cs-panel__note"><?= esc((string) ($service['description'] ?? 'No description available.')) ?></p>
                </div>
                <?php if (empty($routes)): ?>
                    <span class="fw-semibold"><?= cs_money($service['base_price'] ?? 0) ?></span>
                <?php endif; ?>
            </div>
            <?php if (! empty($routes)): ?>
                <div class="cs-panel__body">
                    <label class="form-label">Select route</label>
                    <?php foreach ($routes as $i => $route): ?>
                        <div class="form-check border rounded p-2 mb-2">
                            <input class="form-check-input route-option" type="radio" name="route_id" id="route-<?= (int) $route['route_id'] ?>" value="<?= (int) $route['route_id'] ?>" data-price="<?= (float) $route['price'] ?>" <?= (int) ($selected_route_id ?? 0) === (int) $route['route_id'] || (empty($selected_route_id) && $i === 0) ? 'checked' : '' ?> required>
                            <label class="form-check-label d-flex justify-content-between" for="route-<?= (int) $route['route_id'] ?>">
                                <span><?= esc((string) $route['route_name']) ?></span>
                                <?= cs_money($route['price']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <div class="cs-routerow cs-routerow--total">
                        <span>Total amount</span>
                        <span class="cs-money" id="route-total"><?= esc(number_format((float) (array_values(array_filter($routes, static fn ($r) => (int) $r['route_id'] === (int) ($selected_route_id ?? 0)))[0]['price'] ?? $routes[0]['price'] ?? 0), 2)) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <?php
        $sq = $service_quotes ?? ['eligibility' => ['allowed' => true], 'by_route' => []];
        $sqBlocked = isset($sq['eligibility']['allowed']) && ! $sq['eligibility']['allowed'];
        ?>
        <?php if ($sqBlocked): ?>
            <?= view('components/alert', ['type' => 'warning', 'dismissible' => false, 'message' => (string) ($sq['eligibility']['message'] ?? 'You cannot request this service right now.')]) ?>
        <?php endif; ?>
        <section class="cs-panel mb-3" aria-live="polite">
            <div class="cs-panel__head"><h2 class="cs-panel__title">What this means for your balance</h2><p class="cs-panel__note">A single service is added to your balance: a down payment first, then monthly instalments, on top of your plan contribution. Your branch chooses the term when it approves. Nothing is charged now.</p></div>
            <div class="cs-panel__body" id="svcQuote"></div>
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
                <a class="btn btn-ghost" href="<?= site_url('/client/service?tab=services') ?>">Cancel</a>
                <button class="btn btn-primary" type="submit" <?= $canApply && ! $sqBlocked ? '' : 'disabled' ?>>Submit application</button>
            </div>
        </section>
    </form>
</div>

<script>
(function () {
    var Q = <?= json_encode($service_quotes['by_route'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var box = document.getElementById('svcQuote');
    function money(v) { return '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function row(a, b) { return '<div class="d-flex justify-content-between border-bottom py-1"><span>' + a + '</span><strong>' + b + '</strong></div>'; }
    function draw() {
        var checked = document.querySelector('.route-option:checked');
        var key = checked ? checked.value : '0';
        var q = Q[key] || Q[Object.keys(Q)[0]];
        if (!q || q.added === undefined) { box.textContent = ''; return; }
        var h = row(q.name, money(q.item_price));
        if (q.upfront) {
            h += row('Paid in full up front', money(q.down));
        } else {
            h += row('Down payment (' + Math.round(q.rate * 100) + '%)', money(q.down));
            h += row('Then paid in instalments', money(q.financed));
            (q.terms || []).forEach(function (t) { h += row('&nbsp;&nbsp;' + t.term + ' months', money(t.monthly) + ' a month'); });
        }
        h += row('Plan balance (unchanged)', money(q.plan_balance));
        h += row('Total balance after approval', money(q.total_after));
        box.innerHTML = h + '<p class="cs-muted small mt-2 mb-0">The down payment is due within ' + q.down_days + ' days of approval, or the request is cancelled.</p>';
    }
    document.querySelectorAll('.route-option').forEach(function (r) { r.addEventListener('change', draw); });
    document.addEventListener('DOMContentLoaded', draw);
    draw();
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var total = document.getElementById('route-total');
    if (! total) { return; }
    document.querySelectorAll('.route-option').forEach(function (input) {
        input.addEventListener('change', function () {
            var price = parseFloat(this.dataset.price || '0');
            total.textContent = price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
    });
});
</script>
<?= $this->endSection() ?>
