<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<?php
$blocking = count(array_filter($p['warnings'], static fn ($w): bool => $w['blocking'])) > 0;
?>
<p class="mb-3"><a href="<?= site_url('gcash-review') ?>"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to the GCash review list</a></p>

<?php if ($p['warnings'] !== []): ?>
    <div class="alert alert-warning" role="alert" id="gcashWarnings">
        <strong>Check before you verify:</strong>
        <ul class="mb-0">
            <?php foreach ($p['warnings'] as $w): ?><li data-warning="<?= esc($w['code'], 'attr') ?>"><?= esc($w['message']) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if (! empty($p['proof_requested_at'])): ?>
    <div class="alert alert-info">You asked the client for a clearer screenshot on <?= esc(date('M j, g:ia', strtotime((string) $p['proof_requested_at']))) ?>. It is still waiting for them.</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="cs-panel h-100">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Screenshot</h2><p class="cs-panel__note">Click the picture to zoom in or out.</p></div>
            <div class="cs-panel__body">
                <?php if ($p['has_proof']): ?>
                    <div id="shotWrap" style="max-height:75vh;overflow:auto;border:1px solid var(--bs-border-color,#ddd);border-radius:6px">
                        <img id="shot" src="<?= site_url('gcash-review/' . (int) $p['payment_id'] . '/image') ?>" alt="GCash screenshot sent by <?= esc($p['client_name'], 'attr') ?>" style="width:100%;cursor:zoom-in;display:block">
                    </div>
                    <p class="small mt-2 mb-0"><a href="<?= site_url('gcash-review/' . (int) $p['payment_id'] . '/image') ?>" target="_blank" rel="noopener">Open full size</a></p>
                    <script>
                    (function () {
                        var img = document.getElementById('shot'); var zoomed = false;
                        img.addEventListener('click', function () { zoomed = !zoomed; img.style.width = zoomed ? '220%' : '100%'; img.style.cursor = zoomed ? 'zoom-out' : 'zoom-in'; });
                    })();
                    </script>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">No screenshot was uploaded.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="cs-panel mb-3">
            <div class="cs-panel__head"><h2 class="cs-panel__title">Payment #<?= (int) $p['payment_id'] ?></h2></div>
            <div class="cs-panel__body">
                <dl class="row mb-0">
                    <dt class="col-5">Client</dt><dd class="col-7"><?= esc($p['client_name']) ?></dd>
                    <dt class="col-5">Plan no.</dt><dd class="col-7"><?= esc((string) ($p['unique_identifier'] ?: '-')) ?> (plan #<?= (int) $p['plan_id'] ?>)</dd>
                    <dt class="col-5">Expected amount</dt><dd class="col-7"><strong><?= cs_peso($p['amount']) ?></strong><?= $p['amount_sent'] !== null ? ' <span class="cs-muted">· client says they sent ' . cs_peso($p['amount_sent']) . '</span>' : '' ?></dd>
                    <dt class="col-5">Months covered</dt><dd class="col-7"><?= (int) $p['months_covered'] ?></dd>
                    <dt class="col-5">Reference number</dt><dd class="col-7"><span class="fs-5" style="letter-spacing:.05em"><?= esc($p['reference_grouped']) ?></span></dd>
                    <dt class="col-5">Paid on</dt><dd class="col-7"><?= ! empty($p['paid_at']) ? esc(date('M j, Y, g:ia', strtotime((string) $p['paid_at']))) : esc(date('M j, Y', strtotime((string) $p['payment_date']))) ?></dd>
                    <dt class="col-5">Sender</dt><dd class="col-7"><?= esc((string) ($p['sender_name'] ?: '-')) ?></dd>
                    <dt class="col-5">Paid to</dt><dd class="col-7"><?= esc($p['receiving_label']) ?></dd>
                    <dt class="col-5">Sent to us</dt><dd class="col-7"><?= esc(date('M j, g:ia', strtotime((string) $p['created_at']))) ?></dd>
                </dl>
            </div>
        </section>

        <form method="post" action="<?= site_url('gcash-review/' . (int) $p['payment_id'] . '/decide') ?>" class="cs-panel mb-3" id="decideForm">
            <?= csrf_field() ?>
            <div class="cs-panel__head"><h2 class="cs-panel__title">Checklist</h2><p class="cs-panel__note">Open your own GCash app and check each line.</p></div>
            <div class="cs-panel__body">
                <?php foreach ($checklist as $key => $label): ?>
                    <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="checklist[]" value="<?= esc($key, 'attr') ?>" id="ck_<?= esc($key, 'attr') ?>"><label class="form-check-label" for="ck_<?= esc($key, 'attr') ?>"><?= esc($label) ?></label></div>
                <?php endforeach; ?>
                <div class="mt-3">
                    <label class="form-label" for="last4">Type the last 4 digits of the reference from <u>your</u> GCash app</label>
                    <input class="form-control" style="max-width:9rem" id="last4" name="last4" inputmode="numeric" maxlength="4" pattern="\d{4}" autocomplete="off">
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" name="decision" value="verify" class="btn btn-primary" <?= $blocking ? 'disabled' : '' ?>>Verify</button>
                </div>

                <hr>
                <div class="row g-2 align-items-end">
                    <div class="col-sm-5"><label class="form-label" for="actual_amount">Received a different amount</label><input class="form-control" type="number" step="0.01" min="0.01" id="actual_amount" name="actual_amount" placeholder="Amount you actually received"></div>
                    <div class="col-sm-7"><button type="submit" name="decision" value="different_amount" class="btn btn-outline-primary">Verify with this amount (the rest stays due)</button></div>
                </div>

                <hr>
                <div class="row g-2 align-items-end">
                    <div class="col-12"><label class="form-label" for="reason_code">If you reject, or ask for a better screenshot</label></div>
                    <div class="col-sm-5"><select class="form-select" id="reason_code" name="reason_code"><option value="">Reason...</option><?php foreach ($reasons as $code => $label): ?><option value="<?= esc($code, 'attr') ?>"><?= esc($label) ?></option><?php endforeach; ?></select></div>
                    <div class="col-sm-7"><input class="form-control" name="reason_note" maxlength="255" placeholder="Note to the client (needed for Other)" aria-label="Note to the client"></div>
                    <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                        <button type="submit" name="decision" value="proof_requested" class="btn btn-outline-secondary">Ask for clearer proof (stays pending)</button>
                        <button type="submit" name="decision" value="reject" class="btn btn-outline-danger">Reject</button>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($p['reviews'] !== []): ?>
            <section class="cs-panel">
                <div class="cs-panel__head"><h2 class="cs-panel__title">History of this payment</h2></div>
                <div class="cs-panel__body">
                    <?php foreach ($p['reviews'] as $rv): ?>
                        <p class="mb-1 small"><?= esc(date('M j, g:ia', strtotime((string) $rv['decided_at']))) ?> · <?= esc(trim((string) $rv['first_name'] . ' ' . (string) $rv['last_name'])) ?> · <strong><?= esc(str_replace('_', ' ', (string) $rv['decision'])) ?></strong><?= $rv['reason_note'] ? ' - ' . esc((string) $rv['reason_note']) : '' ?></p>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
