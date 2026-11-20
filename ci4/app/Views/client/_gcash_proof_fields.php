<?php
/**
 * The extra things a client gives with a GCash payment, so the verifier can check it against their own GCash app:
 * a screenshot (required), when they paid, the amount GCash shows and the name they paid from.
 */
?>
<div class="cs-gcash-proof" style="margin-top:1rem">
    <div class="mb-3">
        <label class="form-label" for="gcash_screenshot">Screenshot of your GCash payment <span class="text-danger">*</span></label>
        <input class="form-control" type="file" id="gcash_screenshot" name="gcash_screenshot" accept="image/jpeg,image/png,image/webp" required>
        <div class="form-text">An image (JPG, PNG or WebP), 5 MB at most. It must show the reference number, amount, date and time.</div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="paid_at">Date and time you paid <span class="text-danger">*</span></label>
            <input class="form-control" type="datetime-local" id="paid_at" name="paid_at" max="<?= esc(date('Y-m-d\TH:i'), 'attr') ?>" required value="<?= esc((string) old('paid_at', ''), 'attr') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="amount_sent">Amount your GCash receipt shows <span class="text-danger">*</span></label>
            <input class="form-control" type="number" step="0.01" min="0.01" id="amount_sent" name="amount_sent" required value="<?= esc((string) old('amount_sent', ''), 'attr') ?>">
        </div>
        <div class="col-12">
            <label class="form-label" for="sender_name">Name of the GCash account you paid from <span class="text-danger">*</span></label>
            <input class="form-control" id="sender_name" name="sender_name" maxlength="120" required value="<?= esc((string) old('sender_name', ''), 'attr') ?>">
        </div>
    </div>
</div>
