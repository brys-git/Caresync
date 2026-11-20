<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$peso = static fn ($v): string => '₱' . number_format((float) $v, 2);
?>
<link rel="stylesheet" href="<?= cs_asset_url('assets/css/client-payments.css') ?>">

<div class="cp-page">
    <a class="cp-back" href="<?= base_url('client/payment') ?>">← Back to payments</a>
    <header class="cp-head">
        <h1 class="cp-title">Pay with GCash</h1>
        <p class="cp-sub">You have a package to pay as well as your plan. Enter the amount you sent; we show where it goes. Your collector confirms it against their GCash history.</p>
    </header>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">What you owe</h2></div>
        <div class="cs-panel__body">
            <div class="d-flex justify-content-between border-bottom py-2"><span>Plan contribution</span><strong><?= $peso($balances['plan_monthly']) ?></strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Package instalment</span><strong><?= $peso($balances['package_monthly']) ?></strong></div>
            <div class="d-flex justify-content-between py-2"><span>Total due each month</span><strong><?= $peso($balances['monthly_total_due']) ?></strong></div>
            <?php if (! empty($has_down_due)): ?>
                <p class="mt-2 mb-0 text-warning-emphasis">Your package down payment of <strong><?= $peso($down_left) ?></strong> is still due. Your service can start once it is paid and verified.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="cs-panel mb-3">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Send it to</h2></div>
        <div class="cs-panel__body">
            <?php if ($gcash !== null): ?>
                <p class="mb-1"><strong><?= esc((string) $gcash['collector_name']) ?></strong> (GCash)</p>
                <p class="mb-2 fs-5"><?= esc((string) $gcash['formatted']) ?></p>
                <?php if (! empty($gcash['qr_url'])): ?><img src="<?= esc((string) $gcash['qr_url'], 'attr') ?>" alt="GCash QR" class="cs-qr"><?php endif; ?>
            <?php else: ?>
                <p class="cs-muted mb-0">No GCash account is set up for your area yet. Please pay your collector or the branch.</p>
            <?php endif; ?>
        </div>
    </section>

    <form id="makeAmountForm" method="post" action="<?= base_url('client/payment/make') ?>" class="cs-panel" enctype="multipart/form-data" data-cs-once>
        <?= csrf_field() ?>
        <input type="hidden" name="entry_mode" value="amount">
        <div class="cs-panel__head"><h2 class="cs-panel__title">Your payment</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="payment_kind">This payment is for</label>
                    <select id="payment_kind" name="payment_kind" class="form-select">
                        <option value="regular">Plan and package instalments</option>
                        <?php if (! empty($has_down_due)): ?><option value="down_payment" <?= old('payment_kind') === 'down_payment' ? 'selected' : '' ?>>Package down payment (<?= $peso($down_left) ?> due)</option><?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="amount">Amount you sent</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="form-control" required value="<?= esc((string) old('amount', '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="reference_number">GCash reference number</label>
                    <input id="reference_number" name="reference_number" class="form-control" inputmode="numeric" autocomplete="off" maxlength="13" pattern="\d{13}" placeholder="13 digits" required value="<?= esc((string) old('reference_number', '')) ?>">
                </div>
                <div class="col-12"><?= view('client/_gcash_proof_fields') ?></div>
                <div class="col-12"><div class="alert alert-info mb-0 d-none" id="allocationPreview" role="status"></div></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Submit payment</button>
            <p class="cs-muted small mt-2 mb-0">Your payment shows as Pending until your collector approves it. The receipt then says what it was applied to.</p>
        </div>
    </form>
</div>

<script src="<?= base_url('assets/js/allocation-preview.js') ?>"></script>
<script>
    (function () {
        var amount = document.getElementById('amount');
        var kind = document.getElementById('payment_kind');
        var box = document.getElementById('allocationPreview');
        var p = csAllocationPreview({
            url: '<?= base_url('payments/allocation-preview') ?>',
            plan: function () { return '<?= (int) $plan['plan_id'] ?>'; },
            amount: amount, date: null,
            kind: function () { return kind.value; },
            out: box,
        });
        kind.addEventListener('change', function () { p.refresh(); });
    })();
</script>
<?= $this->endSection() ?>
