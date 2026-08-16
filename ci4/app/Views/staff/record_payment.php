<?= $this->extend($role_layout ?? 'layouts/staff') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/payment-recording.css') ?>">

<?php
    $monthlyFee = (float) ($monthly_fee ?? 240.0);
    $clientsJson = json_encode($clients ?? []);
?>

<div class="pr">

    <!-- ====== Header ====== -->
    <div>
        <h1 class="pr-header__title">Payment Recording</h1>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="pr-alert pr-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="pr-alert pr-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== Tabs ====== -->
    <div class="pr-tabs" id="pr-tabs">
        <button class="pr-tab pr-tab--active" data-tab="regular" onclick="prSwitchTab(this)">[Regular Payment]</button>
        <button class="pr-tab" data-tab="initial" onclick="prSwitchTab(this)">[Initial Payment]</button>
        <button class="pr-tab" data-tab="other" onclick="prSwitchTab(this)">[Other]</button>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Regular Payment                                             -->
    <!-- ================================================================ -->
    <div id="pr-panel-regular">
        <form method="post" action="<?= site_url('staff/record-payment/save') ?>" id="pr-form-regular">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_type" value="regular">

            <div class="pr-form-grid">
                <!-- Client Search -->
                <div class="pr-form-card pr-form-card--highlight">
                    <div class="pr-form-label">Client <span>*</span></div>
                    <div class="pr-form-hint">Smart Client Search</div>
                    <div class="pr-search-wrap">
                        <input type="text" class="pr-search-input" id="pr-search-regular"
                               placeholder="Start typing client name..."
                               oninput="prSearchClient(this, 'pr-dropdown-regular', 'pr-client-id-regular')"
                               onfocus="prShowDropdown('pr-dropdown-regular')"
                               autocomplete="off">
                        <input type="hidden" name="client_name" id="pr-client-id-regular">
                        <div class="pr-search-dropdown" id="pr-dropdown-regular"></div>
                    </div>
                    <button type="button" class="pr-add-client" onclick="window.location.href='<?= site_url('staff/client-management/register') ?>'">
                        <i class="mdi mdi-account-plus"></i> Add New Client
                    </button>
                </div>

                <!-- Rate / Calculation -->
                <div class="pr-form-card">
                    <div class="pr-rate-box">
                        <div class="pr-rate-row">
                            <span class="pr-rate-label">Rate:</span>
                            <span class="pr-rate-value">₱<?= esc(number_format($monthlyFee, 0)) ?>/month</span>
                        </div>
                        <div class="pr-rate-row">
                            <span class="pr-rate-label">Months Covered:</span>
                            <select name="months_covered" class="pr-rate-select" id="pr-months-regular" onchange="prUpdateCalc('regular')">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m === 1 ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="pr-calc-box">
                        <div class="pr-calc-label">Calculation:</div>
                        <div class="pr-calc-formula" id="pr-calc-regular">
                            ₱<?= esc(number_format($monthlyFee, 0)) ?> × 1 = [<strong>₱<?= esc(number_format($monthlyFee, 2)) ?></strong>]
                        </div>
                        <a href="#" class="pr-calc-link">View Detailed Formula</a>
                    </div>
                </div>

                <!-- Receipt -->
                <div class="pr-form-card">
                    <div class="pr-form-label">Receipt <span>*</span></div>
                    <div class="pr-form-hint">Official Receipt Number</div>
                    <input type="text" class="pr-input" name="receipt_number" placeholder="" value="<?= esc(old('receipt_number')) ?>" required>
                </div>
            </div>
        </form>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Initial Payment                                             -->
    <!-- ================================================================ -->
    <div id="pr-panel-initial" style="display:none;">
        <form method="post" action="<?= site_url('staff/record-payment/save') ?>" id="pr-form-initial">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_type" value="initial">

            <div class="pr-form-grid">
                <div class="pr-form-card pr-form-card--highlight">
                    <div class="pr-form-label">Client <span>*</span></div>
                    <div class="pr-form-hint">Smart Client Search</div>
                    <div class="pr-search-wrap">
                        <input type="text" class="pr-search-input" id="pr-search-initial"
                               placeholder="Start typing client name..."
                               oninput="prSearchClient(this, 'pr-dropdown-initial', 'pr-client-id-initial')"
                               onfocus="prShowDropdown('pr-dropdown-initial')"
                               autocomplete="off">
                        <input type="hidden" name="client_name" id="pr-client-id-initial">
                        <div class="pr-search-dropdown" id="pr-dropdown-initial"></div>
                    </div>
                    <button type="button" class="pr-add-client" onclick="window.location.href='<?= site_url('staff/client-management/register') ?>'">
                        <i class="mdi mdi-account-plus"></i> Add New Client
                    </button>
                </div>

                <div class="pr-form-card">
                    <div class="pr-rate-box">
                        <div class="pr-rate-row">
                            <span class="pr-rate-label">Rate:</span>
                            <span class="pr-rate-value">₱<?= esc(number_format($monthlyFee, 0)) ?>/month</span>
                        </div>
                        <div class="pr-rate-row">
                            <span class="pr-rate-label">Months Covered:</span>
                            <select name="months_covered" class="pr-rate-select" id="pr-months-initial" onchange="prUpdateCalc('initial')">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m === 1 ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="pr-calc-box">
                        <div class="pr-calc-label">Calculation:</div>
                        <div class="pr-calc-formula" id="pr-calc-initial">
                            ₱<?= esc(number_format($monthlyFee, 0)) ?> × 1 = [<strong>₱<?= esc(number_format($monthlyFee, 2)) ?></strong>]
                        </div>
                        <a href="#" class="pr-calc-link">View Detailed Formula</a>
                    </div>
                </div>

                <div class="pr-form-card">
                    <div class="pr-form-label">Receipt <span>*</span></div>
                    <div class="pr-form-hint">Official Receipt Number</div>
                    <input type="text" class="pr-input" name="receipt_number" value="<?= esc(old('receipt_number')) ?>" required>
                </div>
            </div>
        </form>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Other                                                       -->
    <!-- ================================================================ -->
    <div id="pr-panel-other" style="display:none;">
        <div class="pr-card" style="padding:40px;text-align:center;">
            <div class="pr-empty">
                <i class="mdi mdi-tools"></i>
                Other payment types coming soon.
            </div>
        </div>
    </div>

    <!-- ====== Approval Queue ====== -->
    <div class="pr-card">
        <div class="pr-card__header">
            <h3 class="pr-card__title">Approval Queue</h3>
        </div>
        <div class="pr-table-wrap" style="overflow-x:auto;">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th style="width:60%;">Name</th>
                        <th style="text-align:right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approval_queue ?? [])): ?>
                        <tr>
                            <td colspan="2">
                                <div class="pr-empty">
                                    <i class="mdi mdi-clipboard-text-outline"></i>
                                    No records in the approval queue.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($approval_queue as $record):
                            $status = strtolower((string) ($record['status'] ?? 'pending'));
                            $statusClass = $status === 'paid' ? 'approved' : ($status === 'pending' ? 'pending' : 'rejected');
                            $statusLabel = strtoupper($status);
                            $clientLabel = esc(trim((string) ($record['first_name'] ?? '')) . ' ' . esc((string) ($record['last_name'] ?? '')));
                        ?>
                        <tr>
                            <td>
                                <div class="pr-name-cell">
                                    <?= $clientLabel ?>
                                    <span class="pr-search-tag pr-search-tag--<?= $status === 'paid' ? 'verified' : 'pending' ?>">
                                        [<?= $status === 'paid' ? 'Verified' : 'Pending' ?>]
                                    </span>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <span class="pr-status-badge pr-status-badge--<?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    var clientsData = <?= $clientsJson ?>;
    var monthlyFee = <?= json_encode($monthlyFee) ?>;
    // Same source of truth as the server (PaymentService::ADVANCE_DISCOUNTS).
    var discountSchedule = <?= json_encode(\App\Services\PaymentService::ADVANCE_DISCOUNTS) ?>;

    /* --- Tab switching --- */
    window.prSwitchTab = function (btn) {
        var tabId = btn.getAttribute('data-tab');
        document.querySelectorAll('#pr-tabs .pr-tab').forEach(function (t) { t.classList.remove('pr-tab--active'); });
        btn.classList.add('pr-tab--active');

        document.querySelectorAll('[id^="pr-panel-"]').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('pr-panel-' + tabId);
        if (panel) panel.style.display = '';
    };

    /* --- Calculation update (applies the advance-payment discount) --- */
    window.prUpdateCalc = function (prefix) {
        var monthsEl = document.getElementById('pr-months-' + prefix);
        var calcEl = document.getElementById('pr-calc-' + prefix);
        if (!monthsEl || !calcEl) return;

        var months = parseInt(monthsEl.value) || 1;
        var subtotal = monthlyFee * months;
        var pct = discountSchedule[months] || 0;
        var discount = Math.round(subtotal * pct) / 100;
        var total = subtotal - discount;
        var discountNote = (pct > 0) ? ' (incl. ' + pct + '% advance discount)' : '';
        calcEl.innerHTML = '₱' + monthlyFee.toLocaleString() + ' × ' + months + discountNote + ' = [<strong>₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>]';
    };

    /* --- Client search --- */
    window.prSearchClient = function (input, dropdownId, hiddenId) {
        var dropdown = document.getElementById(dropdownId);
        var hidden = document.getElementById(hiddenId);
        var query = input.value.toLowerCase().trim();

        if (query.length < 1) {
            dropdown.classList.remove('show');
            hidden.value = '';
            return;
        }

        var matches = clientsData.filter(function (c) {
            var name = ((c.first_name || '') + ' ' + (c.last_name || '')).toLowerCase();
            return name.indexOf(query) !== -1;
        });

        if (matches.length === 0) {
            dropdown.classList.remove('show');
            hidden.value = input.value;
            return;
        }

        dropdown.innerHTML = matches.map(function (c) {
            var name = (c.first_name || '') + ' ' + (c.last_name || '');
            var status = (c.plan_holder_status || 'inactive').toLowerCase();
            var tagClass = status === 'active' ? 'verified' : 'pending';
            var tagLabel = status === 'active' ? 'Verified' : 'Pending';
            return '<div class="pr-search-item" onclick="prSelectClient(\'' + dropdownId + '\', \'' + hiddenId + '\', \'' + name.replace(/'/g, "\\'") + '\')">' +
                '<span>' + name + '</span>' +
                '<span class="pr-search-tag pr-search-tag--' + tagClass + '">[' + tagLabel + ']</span>' +
            '</div>';
        }).join('');

        dropdown.classList.add('show');
    };

    window.prSelectClient = function (dropdownId, hiddenId, name) {
        var dropdown = document.getElementById(dropdownId);
        var hidden = document.getElementById(hiddenId);
        var input = dropdown.previousElementSibling;

        input.value = name;
        hidden.value = name;
        dropdown.classList.remove('show');
    };

    window.prShowDropdown = function (dropdownId) {
        var dropdown = document.getElementById(dropdownId);
        if (dropdown.children.length > 0) {
            dropdown.classList.add('show');
        }
    };

    /* Close dropdowns on outside click */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.pr-search-wrap')) {
            document.querySelectorAll('.pr-search-dropdown.show').forEach(function (d) {
                d.classList.remove('show');
            });
        }
    });
})();
</script>
<?= $this->endSection() ?>
