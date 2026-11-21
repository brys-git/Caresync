<?php
/**
 * Shared deceased / contact / documents form for "Claim Regular Casket" and
 * "Avail Package". $mode is 'claim' or 'avail' and only changes the package
 * card, the cost summary rows, the amount-due display and the submit label.
 *
 * Expects: $mode, $package, $base_price, $attire_price, $form_quote (ClaimAvailService::formQuote -
 * the exact quote the approval will use), $plan_name, $member_name, $member_address,
 * $beneficiary_prefill, $branch_name, $branch_phone, $form_errors.
 * There is no "benefit credit": a claim releases the plan's own package free; anything else is
 * added to the balance (down payment, then instalments) - the summary shows exactly that.
 */
$e = static fn ($v): string => esc((string) ($v ?? ''));
$money = static fn (float $v): string => '₱' . number_format($v, 2);
$isClaim = $mode === 'claim';
$attireOn = (bool) old('burial_attire');
$fq = is_array($form_quote ?? null) ? $form_quote : ['plain' => [], 'attire' => [], 'eligibility' => ['allowed' => true]];
$quoteNow = ($attireOn ? $fq['attire'] : $fq['plain']) ?: $fq['plain'];
$blocked = isset($fq['eligibility']['allowed']) && ! $fq['eligibility']['allowed'];
$errorFor = static fn (string $key): string => (string) ($form_errors[$key] ?? '');
$description = trim((string) ($package['description'] ?? ''));
if ($description === '') {
    $description = $isClaim ? 'The casket package included in your plan.' : 'Package details are confirmed by staff when they call.';
}
$relationships = ['Spouse', 'Child', 'Parent', 'Sibling', 'Other relative'];
$currentRelationship = (string) old('relationship_to_deceased', '');
if ($currentRelationship !== '' && ! in_array($currentRelationship, $relationships, true)) {
    array_unshift($relationships, $currentRelationship);
}
$today = date('Y-m-d');
// Optional: a Branch Admin filing on the plan holder's behalf passes form_action
// and on_behalf = true, which adds the "Who is making the claim" fields.
$formAction = (string) ($form_action ?? site_url('/client/apply-package/' . (int) ($package['package_id'] ?? 0)));
$onBehalf = (bool) ($on_behalf ?? false);
$claimantRelationships = ['Beneficiary', 'Spouse', 'Child', 'Parent', 'Sibling', 'Other relative', 'Friend / other'];
// "Who died?" (null = the migration for the claim policy has not run: the old free-text name is used)
$deceasedOptions = is_array($deceased_options ?? null) ? $deceased_options : null;
$chosenDeceased = (string) old('deceased_choice', '');
?>
<?php if ($blocked): ?>
    <section class="br-notice" role="alert">
        <h2 class="br-notice__title">This can't be submitted right now</h2>
        <p class="br-body"><?= $e($fq['eligibility']['message'] ?? 'Your plan does not allow this request at the moment.') ?></p>
    </section>
<?php endif; ?>
<form id="brForm" method="post" enctype="multipart/form-data" action="<?= $e($formAction) ?>" novalidate data-cs-once
      data-attire-price="<?= $e(number_format((float) $attire_price, 2, '.', '')) ?>">
    <?= csrf_field() ?>

    <div class="br-layout">
        <div class="br-main">
            <section class="br-card" aria-labelledby="br-pkg-title">
                <div class="br-pkg-head">
                    <div>
                        <p class="br-label"><?= $isClaim ? 'Your entitlement' : 'Package' ?></p>
                        <h2 id="br-pkg-title" class="br-pkg-name"><?= $e($package['package_name'] ?? '—') ?></h2>
                    </div>
                    <p class="br-pkg-value br-num"><?= $money((float) $base_price) ?></p>
                </div>
                <div class="br-pkg-image"><img src="<?= $e(cs_image_url($package['image_path'] ?? null)) ?>" alt="<?= $e($package['package_name'] ?? 'Package') ?>" onerror="this.onerror=null;this.src='<?= $e(base_url('assets/images/no-image.png')) ?>'"></div>
                <p class="br-muted"><?= $e($description) ?></p>

                <?php if ($isClaim): ?>
                    <div class="br-covered">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        <div>
                            <p class="br-covered__title">The casket in your plan is released at no extra charge</p>
                            <p class="br-covered__sub">Plan: <?= $e($plan_name) ?> · Member <?= $e($member_name) ?>. What is left of your plan balance is waived and a new cycle starts at 0 months.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="button" id="attireSwitch" role="switch" aria-checked="<?= $attireOn ? 'true' : 'false' ?>" aria-labelledby="attireTitle" class="br-addon<?= $attireOn ? ' is-on' : '' ?>">
                    <span class="br-addon__text">
                        <span id="attireTitle" class="br-addon__title">Add Burial Attire Package</span>
                        <span class="br-muted">Optional add-on · + <?= $money((float) $attire_price) ?></span>
                    </span>
                    <span class="br-switch" aria-hidden="true"></span>
                </button>
                <input type="checkbox" id="burialAttire" name="burial_attire" value="1" class="br-sr" tabindex="-1" aria-hidden="true" <?= $attireOn ? 'checked' : '' ?>>
            </section>

            <section class="br-card" aria-labelledby="br-deceased-title">
                <h2 id="br-deceased-title" class="br-card__title">About the deceased</h2>
                <div class="br-grid">
                    <?php if ($deceasedOptions !== null): ?>
                    <div class="br-field br-span-2">
                        <label for="deceased_choice">Who died? <span class="br-req">Required</span></label>
                        <select id="deceased_choice" name="deceased_choice" class="br-input" aria-describedby="err-deceased_choice" required>
                            <option value="">Select one</option>
                            <?php foreach ($deceasedOptions as $opt): ?>
                                <option value="<?= $e($opt['id']) ?>" data-kind="<?= $e($opt['kind']) ?>" data-rel="<?= $e($opt['relationship']) ?>" <?= $chosenDeceased === (string) $opt['id'] ? 'selected' : '' ?>><?= $e($opt['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (! $onBehalf): ?>
                            <p class="br-hint">Pick the registered beneficiary who passed away. If the plan holder has passed away, a registered beneficiary or the emergency contact files the claim at the branch.</p>
                        <?php endif; ?>
                        <p class="br-error" id="err-deceased_choice" <?= $errorFor('deceased_choice') === '' ? 'hidden' : '' ?>><?= $e($errorFor('deceased_choice')) ?></p>
                        <input type="hidden" id="deceased_name" name="deceased_name" value="<?= old('deceased_name') ?>">
                    </div>
                    <?php else: ?>
                    <div class="br-field br-span-2">
                        <label for="deceased_name">Full name <span class="br-req">Required</span></label>
                        <input id="deceased_name" name="deceased_name" class="br-input" type="text" autocomplete="off" value="<?= old('deceased_name') ?>" aria-describedby="err-deceased_name" required>
                        <p class="br-error" id="err-deceased_name" <?= $errorFor('deceased_name') === '' ? 'hidden' : '' ?>><?= $e($errorFor('deceased_name')) ?></p>
                    </div>
                    <div class="br-field br-span-2">
                        <label class="br-check">
                            <input type="checkbox" id="deceased_is_member" class="br-check__input">
                            <span>The deceased is the plan holder<?= $member_name !== '' ? ' (' . $e($member_name) . ')' : '' ?></span>
                        </label>
                    </div>
                    <?php endif; ?>
                    <div class="br-field">
                        <label for="deceased_date_of_death">Date of death</label>
                        <input id="deceased_date_of_death" name="deceased_date_of_death" class="br-input" type="date" max="<?= $today ?>" value="<?= old('deceased_date_of_death') ?>" aria-describedby="err-deceased_date_of_death">
                        <p class="br-error" id="err-deceased_date_of_death" <?= $errorFor('deceased_date_of_death') === '' ? 'hidden' : '' ?>><?= $e($errorFor('deceased_date_of_death')) ?></p>
                    </div>
                    <div class="br-field">
                        <label for="relationship_to_deceased">Your relationship to the deceased</label>
                        <select id="relationship_to_deceased" name="relationship_to_deceased" class="br-input">
                            <option value="">Select one</option>
                            <?php foreach ($relationships as $option): ?>
                                <option value="<?= $e($option) ?>" <?= $currentRelationship === $option ? 'selected' : '' ?>><?= $e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="br-field br-span-2">
                        <label for="deceased_address">Address of the deceased</label>
                        <input id="deceased_address" name="deceased_address" class="br-input" type="text" autocomplete="off" value="<?= old('deceased_address') ?>" aria-describedby="err-deceased_address">
                        <p class="br-error" id="err-deceased_address" <?= $errorFor('deceased_address') === '' ? 'hidden' : '' ?>><?= $e($errorFor('deceased_address')) ?></p>
                    </div>
                </div>
            </section>

            <?php if ($onBehalf): ?>
            <section class="br-card" aria-labelledby="br-claimant-title">
                <h2 id="br-claimant-title" class="br-card__title">Who is making the claim</h2>
                <p class="br-muted">Choose the claimant from the plan holder's records. Beneficiaries must be registered on the plan; they come to the branch because they have no account.</p>
                <?php $claimantOptions = is_array($claimant_options ?? null) ? $claimant_options : []; $chosen = (string) old('claimant_choice', ''); ?>
                <fieldset class="br-field" aria-describedby="err-claimant_choice">
                    <legend class="br-label">Claimant <span class="br-req">Required</span></legend>
                    <?php foreach ($claimantOptions as $opt): ?>
                        <label class="br-check">
                            <input type="radio" name="claimant_choice" class="br-check__input claimant-choice" value="<?= $e($opt['id']) ?>" data-kind="<?= $e($opt['kind'] ?? '') ?>" data-rel="<?= $e($opt['relationship']) ?>" <?= $chosen === (string) $opt['id'] ? 'checked' : '' ?> required>
                            <span><?= $e($opt['label']) ?><?= $opt['relationship'] !== '' ? ' · ' . $e($opt['relationship']) : '' ?></span>
                        </label>
                    <?php endforeach; ?>
                    <p class="br-muted" id="claimantRule">If the plan holder died, the claimant is a registered beneficiary or the emergency contact. If a beneficiary died, the claimant is the plan holder.</p>
                    <p class="br-error" id="err-claimant_choice" <?= $errorFor('claimant_choice') === '' ? 'hidden' : '' ?>><?= $e($errorFor('claimant_choice')) ?></p>
                </fieldset>
                <div class="br-field">
                    <label for="claimant_relationship">Their relationship to the plan holder <span class="br-req">Required</span></label>
                    <select id="claimant_relationship" name="claimant_relationship" class="br-input" aria-describedby="err-claimant_relationship" required>
                        <option value="">Select</option>
                        <?php foreach (array_unique(array_merge(['Plan holder'], $claimantRelationships)) as $rel): ?>
                            <option value="<?= $e($rel) ?>" <?= (string) old('claimant_relationship') === $rel ? 'selected' : '' ?>><?= $e($rel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="br-error" id="err-claimant_relationship" <?= $errorFor('claimant_relationship') === '' ? 'hidden' : '' ?>><?= $e($errorFor('claimant_relationship')) ?></p>
                </div>
                <script>
                (function () {
                    var sel = document.getElementById('claimant_relationship');
                    document.querySelectorAll('.claimant-choice').forEach(function (r) {
                        r.addEventListener('change', function () {
                            var rel = r.getAttribute('data-rel') || '';
                            if (!rel) { return; }
                            var found = false;
                            for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].value === rel) { found = true; } }
                            if (!found) { var o = document.createElement('option'); o.value = rel; o.textContent = rel; sel.appendChild(o); }
                            sel.value = rel;
                        });
                    });
                })();
                </script>
            </section>
            <?php endif; ?>

            <section class="br-card" aria-labelledby="br-contact-title">
                <h2 id="br-contact-title" class="br-card__title">Contact person</h2>
                <div class="br-grid">
                    <div class="br-field">
                        <label for="beneficiary_name">Contact person's full name <span class="br-req">Required</span></label>
                        <input id="beneficiary_name" name="beneficiary_name" class="br-input" type="text" autocomplete="name" value="<?= old('beneficiary_name', (string) $beneficiary_prefill) ?>" aria-describedby="err-beneficiary_name" required>
                        <p class="br-error" id="err-beneficiary_name" <?= $errorFor('beneficiary_name') === '' ? 'hidden' : '' ?>><?= $e($errorFor('beneficiary_name')) ?></p>
                    </div>
                    <div class="br-field">
                        <label for="beneficiary_contact">Mobile number <span class="br-req">Required</span></label>
                        <input id="beneficiary_contact" name="beneficiary_contact" class="br-input" type="tel" inputmode="tel" autocomplete="tel" placeholder="09XX XXX XXXX" value="<?= old('beneficiary_contact') ?>" aria-describedby="hint-beneficiary_contact err-beneficiary_contact" required>
                        <p class="br-hint" id="hint-beneficiary_contact">11 digits, starts with 09.</p>
                        <p class="br-error" id="err-beneficiary_contact" <?= $errorFor('beneficiary_contact') === '' ? 'hidden' : '' ?>><?= $e($errorFor('beneficiary_contact')) ?></p>
                    </div>
                </div>
            </section>

            <section class="br-card" aria-labelledby="br-docs-title">
                <h2 id="br-docs-title" class="br-card__title">Supporting documents</h2>
                <p class="br-muted br-docs-intro">Photos or PDFs, up to 5 MB each.</p>

                <div class="br-doc" data-doc="death_certificate">
                    <span class="br-doc__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                    <div class="br-doc__body">
                        <div class="br-doc__head">
                            <label for="doc_death_certificate" class="br-doc__title">Death certificate</label>
                            <span class="br-tag br-tag--req">Required</span>
                        </div>
                        <p class="br-hint">Don't have it yet? Submit anyway — staff can help.</p>
                        <div class="br-file" hidden>
                            <span class="br-file__name"></span>
                            <button type="button" class="br-remove" aria-label="Remove death certificate" data-remove="doc_death_certificate">×</button>
                        </div>
                        <input id="doc_death_certificate" name="documents[death_certificate]" type="file" class="br-sr" accept="image/*,application/pdf" aria-describedby="err-documents_death_certificate">
                        <p class="br-error" id="err-documents_death_certificate" <?= $errorFor('documents_death_certificate') === '' ? 'hidden' : '' ?>><?= $e($errorFor('documents_death_certificate')) ?></p>
                    </div>
                    <button type="button" class="br-btn br-btn--ghost br-doc__upload" data-upload="doc_death_certificate">Upload</button>
                </div>

                <div class="br-doc" data-doc="beneficiary_id">
                    <span class="br-doc__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M14 10h4M14 14h3"/></svg></span>
                    <div class="br-doc__body">
                        <div class="br-doc__head">
                            <label for="doc_beneficiary_id" class="br-doc__title"><?= $onBehalf ? 'Valid ID of the person making the claim' : 'Valid ID of the contact person' ?></label>
                            <span class="br-tag br-tag--req">Required</span>
                        </div>
                        <p class="br-hint">A clear photo of a government-issued ID.</p>
                        <div class="br-file" hidden>
                            <span class="br-file__name"></span>
                            <button type="button" class="br-remove" aria-label="Remove ID" data-remove="doc_beneficiary_id">×</button>
                        </div>
                        <input id="doc_beneficiary_id" name="documents[beneficiary_id]" type="file" class="br-sr" accept="image/*,application/pdf" aria-describedby="err-documents_beneficiary_id">
                        <p class="br-error" id="err-documents_beneficiary_id" <?= $errorFor('documents_beneficiary_id') === '' ? 'hidden' : '' ?>><?= $e($errorFor('documents_beneficiary_id')) ?></p>
                    </div>
                    <button type="button" class="br-btn br-btn--ghost br-doc__upload" data-upload="doc_beneficiary_id">Upload</button>
                </div>

                <div class="br-doc" data-doc="other">
                    <span class="br-doc__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/></svg></span>
                    <div class="br-doc__body">
                        <div class="br-doc__head">
                            <label for="doc_other" class="br-doc__title">Other documents</label>
                            <span class="br-tag">Optional</span>
                        </div>
                        <p class="br-hint">Anything else that helps, such as a permit. You can add more than one.</p>
                        <div class="br-file" hidden>
                            <span class="br-file__name"></span>
                            <button type="button" class="br-remove" aria-label="Remove other documents" data-remove="doc_other">×</button>
                        </div>
                        <input id="doc_other" name="documents[other][]" type="file" multiple class="br-sr" accept="image/*,application/pdf" aria-describedby="err-documents_other">
                        <p class="br-error" id="err-documents_other" <?= $errorFor('documents_other') === '' ? 'hidden' : '' ?>><?= $e($errorFor('documents_other')) ?></p>
                    </div>
                    <button type="button" class="br-btn br-btn--ghost br-doc__upload" data-upload="doc_other">Upload</button>
                </div>
            </section>
        </div>

        <aside class="br-summary" aria-labelledby="br-sum-title">
            <h2 id="br-sum-title" class="br-card__title">What this means for your balance</h2>
            <dl class="br-rows" id="quoteRows" aria-live="polite"></dl>
            <div class="br-divider"></div>
            <p class="br-label">Added to your balance</p>
            <p id="amountDue" class="br-amount br-num"></p>
            <p id="amountNote" class="br-muted br-amount-note"></p>
            <button type="submit" id="submitBtn" class="br-btn br-btn--primary br-btn--block" <?= $blocked ? 'disabled' : '' ?>><?= $isClaim ? 'Submit claim' : 'Submit request' ?></button>
            <a class="br-link" href="<?= site_url('/client/service?tab=packages') ?>">Cancel</a>
            <p class="br-help">Need help now?
                <?php if ($branch_phone !== ''): ?>
                    <a href="tel:<?= $e(preg_replace('/[^\d+]/', '', (string) $branch_phone)) ?>">Call your branch <?= $e($branch_phone) ?></a>
                <?php else: ?>
                    Contact your branch.
                <?php endif; ?>
            </p>
        </aside>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('brForm');
    if (!form) { return; }

    var today = '<?= $today ?>';
    var attireSwitch = document.getElementById('attireSwitch');
    var attireBox = document.getElementById('burialAttire');
    var quoteRows = document.getElementById('quoteRows');
    var amountDue = document.getElementById('amountDue');
    var amountNote = document.getElementById('amountNote');
    var submitBtn = document.getElementById('submitBtn');
    var Q = <?= json_encode($fq, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function money(value) {
        return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function row(label, value, cls) {
        return '<div class="br-row' + (cls ? ' ' + cls : '') + '"><dt>' + label + '</dt><dd class="br-num">' + value + '</dd></div>';
    }

    // The exact figures the approval will use (ClaimAvailService), with or without the add-on.
    function renderAmount() {
        var on = attireBox.checked;
        attireSwitch.setAttribute('aria-checked', on ? 'true' : 'false');
        attireSwitch.classList.toggle('is-on', on);
        var q = (on ? Q.attire : Q.plain) || Q.plain;
        if (!q || q.added === undefined) { return; }
        var h = '';
        if (q.free) {
            h += row(q.name + ' (in your plan)', 'Free', 'br-row--credit');
            if (q.addons_total > 0) { h += row(q.addons_label + ' add-on', '+ ' + money(q.addons_total)); }
        } else {
            h += row(q.name, money(q.item_price));
            if (q.addons_total > 0) { h += row(q.addons_label + ' add-on', '+ ' + money(q.addons_total)); }
        }
        if (q.added > 0) {
            if (q.upfront) {
                h += row('Paid in full up front', money(q.down));
            } else {
                h += row('Down payment (' + Math.round(q.rate * 100) + '%)', money(q.down));
                h += row('Then paid in instalments', money(q.financed));
                if (q.terms && q.terms.length) {
                    h += row('Monthly instalment, by the term your branch chooses', '');
                    q.terms.forEach(function (t) { h += row('&nbsp;&nbsp;' + t.term + ' months', money(t.monthly) + ' a month'); });
                }
            }
            h += (q.plan_waived > 0) ? row('Plan balance waived', '− ' + money(q.plan_waived), 'br-row--credit') : row('Plan balance (unchanged)', money(q.plan_balance));
            h += row('Total balance after approval', money(q.total_after));
        } else if (q.plan_waived > 0) {
            h += row('Plan balance waived', '− ' + money(q.plan_waived), 'br-row--credit');
        } else {
            h += row('Your plan balance stays', money(q.plan_balance));
        }
        quoteRows.innerHTML = h;
        amountDue.textContent = q.added > 0 ? money(q.added) : '₱0.00';
        if (q.added > 0 && q.upfront) {
            amountNote.textContent = 'This small amount is paid in full within ' + q.down_days + ' days of approval, or the request is cancelled. Nothing is charged now.';
        } else if (q.added > 0) {
            amountNote.textContent = 'Added to your balance when your branch approves. The down payment is due within ' + q.down_days + ' days of approval, or the request is cancelled. Nothing is charged now.';
        } else {
            amountNote.textContent = q.plan_waived > 0
                ? 'Nothing is added to your balance. What is left of your plan (' + money(q.plan_waived) + ') is waived and a new cycle starts at 0 months.'
                : 'Nothing is added to your balance. You keep paying your plan contribution of ' + money(q.plan_monthly) + ' a month.';
        }
    }
    renderAmount();
    attireSwitch.addEventListener('click', function () {
        attireBox.checked = !attireBox.checked;
        renderAmount();
    });

    var memberName = <?= json_encode((string) $member_name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var memberAddress = <?= json_encode((string) $member_address, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var memberBox = document.getElementById('deceased_is_member');
    if (memberBox) {
        memberBox.addEventListener('change', function () {
            if (!memberBox.checked) { return; }
            if (memberName) { document.getElementById('deceased_name').value = memberName; }
            if (memberAddress) { document.getElementById('deceased_address').value = memberAddress; }
            validateField('deceased_name');
            validateField('deceased_address');
        });
    }

    var rules = {
        deceased_name: function (value) {
            if (value.trim() === '') { return 'Enter the full name of the deceased.'; }
            if (value.trim().length > 150) { return 'Keep the name under 150 characters.'; }
            return '';
        },
        deceased_date_of_death: function (value) {
            if (value && value > today) { return 'The date of death can’t be in the future.'; }
            return '';
        },
        deceased_address: function (value) {
            return value.length > 255 ? 'Keep the address under 255 characters.' : '';
        },
        beneficiary_name: function (value) {
            if (value.trim() === '') { return 'Enter the name of the contact person.'; }
            return '';
        },
        beneficiary_contact: function (value) {
            var digits = value.replace(/[\s-]/g, '');
            return /^09\d{9}$/.test(digits) ? '' : 'Enter an 11-digit mobile number that starts with 09.';
        },
        documents_beneficiary_id: function () {
            return document.getElementById('doc_beneficiary_id').files.length > 0 ? '' : 'Upload a valid ID of the contact person.';
        }
    };

    var inputFor = {
        documents_beneficiary_id: 'doc_beneficiary_id'
    };

    // "Who died?": the name comes from the plan's own records; the claimant choices follow who died.
    var deceasedChoice = document.getElementById('deceased_choice');
    if (deceasedChoice) {
        rules.deceased_name = function () { return ''; };
        rules.deceased_choice = function (value) { return value === '' ? 'Choose who died.' : ''; };
        var applyDeceased = function () {
            var opt = deceasedChoice.options[deceasedChoice.selectedIndex];
            var kind = opt ? opt.getAttribute('data-kind') : '';
            document.getElementById('deceased_name').value = opt && opt.value !== '' ? opt.text.replace(/\s*\([^)]*\)\s*$/, '') : '';
            if (kind === 'holder' && memberAddress && !document.getElementById('deceased_address').value) { document.getElementById('deceased_address').value = memberAddress; }
            document.querySelectorAll('.claimant-choice').forEach(function (r) {
                var k = r.getAttribute('data-kind');
                var ok = kind === '' || (kind === 'holder' ? k !== 'holder' : k === 'holder');
                var label = r.closest('label');
                if (label) { label.hidden = !ok; }
                if (!ok) { r.checked = false; }
                r.disabled = !ok;
            });
            setError('deceased_choice', '');
        };
        deceasedChoice.addEventListener('change', applyDeceased);
        applyDeceased();
    }

    function setError(name, message) {
        var errorEl = document.getElementById('err-' + name);
        var input = document.getElementById(inputFor[name] || name);
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = message === '';
        }
        if (input) {
            if (message) { input.setAttribute('aria-invalid', 'true'); } else { input.removeAttribute('aria-invalid'); }
        }
        return message;
    }

    function validateField(name) {
        var input = document.getElementById(inputFor[name] || name);
        if (!input || !rules[name]) { return ''; }
        var value = input.type === 'file' ? '' : input.value;
        return setError(name, rules[name](value));
    }

    ['deceased_name', 'deceased_date_of_death', 'deceased_address', 'beneficiary_name', 'beneficiary_contact'].forEach(function (name) {
        var input = document.getElementById(name);
        input.addEventListener('blur', function () { validateField(name); });
    });
    document.getElementById('doc_beneficiary_id').addEventListener('change', function () {
        validateField('documents_beneficiary_id');
        renderFile(this);
    });

    function renderFile(input) {
        var row = input.closest('.br-doc');
        var box = row.querySelector('.br-file');
        var upload = row.querySelector('.br-doc__upload');
        var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
        box.hidden = names.length === 0;
        box.querySelector('.br-file__name').textContent = names.join(', ');
        upload.hidden = names.length > 0;
    }

    ['doc_death_certificate', 'doc_other'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () { renderFile(this); });
    });

    document.querySelectorAll('[data-upload]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById(button.getAttribute('data-upload')).click();
        });
    });
    document.querySelectorAll('[data-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-remove'));
            input.value = '';
            renderFile(input);
            if (input.id === 'doc_beneficiary_id') { validateField('documents_beneficiary_id'); }
        });
    });

    form.addEventListener('submit', function (event) {
        var names = Object.keys(rules);
        var firstInvalid = null;
        names.forEach(function (name) {
            var message = validateField(name);
            if (message && !firstInvalid) {
                firstInvalid = document.getElementById(inputFor[name] || name);
            }
        });
        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';
    });

    var firstServerError = form.querySelector('.br-error:not([hidden])');
    if (firstServerError) {
        var serverKey = firstServerError.id.replace('err-', '');
        var target = document.getElementById(inputFor[serverKey] || serverKey);
        if (target) { target.focus(); }
    }

    renderAmount();
})();
</script>
