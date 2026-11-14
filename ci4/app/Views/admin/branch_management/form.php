<?= $this->extend($role_layout ?? 'layouts/admin') ?>

<?= $this->section('content') ?>
<?php
// Flash messages are surfaced as toasts by layouts/_shell.php; field errors are shown inline below.
$errors   = session('errors') ?? [];
$isEdit   = ! empty($branch);
$val      = static fn (string $key, $default = '') => old($key, $branch[$key] ?? $default);
$err      = static fn (string $key): string => isset($errors[$key]) ? '<div class="text-danger small mt-1">' . esc($errors[$key]) . '</div>' : '';
$invalid  = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
$action   = $isEdit ? site_url('/admin/branch-management/update/' . (int) $branch['branch_id']) : site_url('/admin/branch-management/store');
?>

<div class="mb-3 text-end">
    <a href="<?= site_url('/admin/branch-management?tab=branches') ?>" class="btn btn-ghost btn-sm">Back to branches</a>
</div>

<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">A. Branch information</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Branch Code *</label>
                    <input type="text" id="branchCode" class="form-control" value="<?= esc($branch['branch_code'] ?? 'Generated on save') ?>" readonly>
                    <div class="form-text">System-generated and unique. It cannot be edited.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="branch_name">Branch Name *</label>
                    <input type="text" id="branch_name" name="branch_name" class="form-control<?= $invalid('branch_name') ?>" maxlength="100" placeholder="e.g. Calapan Branch" value="<?= esc($val('branch_name')) ?>">
                    <?= $err('branch_name') ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="branch_type">Branch Type</label>
                    <select id="branch_type" name="branch_type" class="form-select">
                        <option value="main" <?= $val('branch_type', 'branch') === 'main' ? 'selected' : '' ?>>Main Office</option>
                        <option value="branch" <?= $val('branch_type', 'branch') === 'branch' ? 'selected' : '' ?>>Branch Office</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="date_established">Date Established</label>
                    <input type="date" id="date_established" name="date_established" max="<?= date('Y-m-d') ?>" class="form-control<?= $invalid('date_established') ?>" value="<?= esc($val('date_established')) ?>">
                    <?= $err('date_established') ?>
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">B. Branch address</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="address_street">House/Building No. and Street *</label>
                    <input type="text" id="address_street" name="address_street" class="form-control<?= $invalid('address_street') ?>" maxlength="100" placeholder="e.g. Sta. Isabel" value="<?= esc($val('address_street')) ?>">
                    <?= $err('address_street') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="address_barangay">Barangay *</label>
                    <input type="text" id="address_barangay" name="address_barangay" class="form-control<?= $invalid('address_barangay') ?>" maxlength="100" placeholder="e.g. Sta. Isabel" value="<?= esc($val('address_barangay')) ?>">
                    <?= $err('address_barangay') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="address_city">City/Municipality *</label>
                    <input type="text" id="address_city" name="address_city" class="form-control<?= $invalid('address_city') ?>" maxlength="100" placeholder="e.g. Calapan City" value="<?= esc($val('address_city')) ?>">
                    <?= $err('address_city') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="address_province">Province *</label>
                    <input type="text" id="address_province" name="address_province" class="form-control<?= $invalid('address_province') ?>" maxlength="100" placeholder="e.g. Oriental Mindoro" value="<?= esc($val('address_province')) ?>">
                    <?= $err('address_province') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="address_region">Region</label>
                    <input type="text" id="address_region" name="address_region" class="form-control" maxlength="100" placeholder="e.g. MIMAROPA (Region IV-B)" value="<?= esc($val('address_region')) ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">C. Contact information</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="contact_number">Branch Contact Number *</label>
                    <input type="text" id="contact_number" name="contact_number" class="form-control<?= $invalid('contact_number') ?>" maxlength="20" placeholder="09XX-XXX-XXXX" value="<?= esc($val('contact_number')) ?>">
                    <?= $err('contact_number') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Branch Email Address</label>
                    <input type="email" id="email" name="email" class="form-control<?= $invalid('email') ?>" maxlength="100" placeholder="calapan@company-domain.com" value="<?= esc($val('email')) ?>">
                    <?= $err('email') ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="operating_hours">Operating Hours</label>
                    <input type="text" id="operating_hours" name="operating_hours" class="form-control" maxlength="150" placeholder="Monday-Sunday, 24 hours" value="<?= esc($val('operating_hours')) ?>">
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">D. Branch manager / head</h2></div>
        <div class="cs-panel__body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label" for="managerSearch">Assigned Branch Manager *</label>
                    <input type="search" id="managerSearch" class="form-control mb-2" placeholder="Type to search existing Branch Admin / Staff accounts" autocomplete="off">
                    <select id="manager_user_id" name="manager_user_id" class="form-select<?= $invalid('manager_user_id') ?>" size="1">
                        <option value="">Select an existing account</option>
                        <?php foreach ($managers as $m): ?>
                            <option value="<?= (int) $m['user_id'] ?>"
                                    data-position="<?= esc($m['role_name'] === 'Branch Admin' ? 'Branch Manager' : 'Staff') ?>"
                                    data-contact="<?= esc($m['contact_number'] ?? '') ?>"
                                    <?= (string) $val('manager_user_id') === (string) $m['user_id'] ? 'selected' : '' ?>>
                                <?= esc(trim($m['first_name'] . ' ' . $m['last_name'])) ?> - <?= esc($m['role_name'] ?? '') ?><?= ! empty($m['branch_name']) ? ' (' . esc($m['branch_name']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('manager_user_id') ?>
                    <div class="form-text">Managers are picked from existing accounts, so no personal details are entered again. Changing the manager later keeps the previous manager's records and history.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="manager_position">Manager Position</label>
                    <input type="text" id="manager_position" name="manager_position" class="form-control" maxlength="50" placeholder="Branch Manager" value="<?= esc($val('manager_position')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="manager_contact_number">Manager Contact Number</label>
                    <input type="text" id="manager_contact_number" name="manager_contact_number" class="form-control<?= $invalid('manager_contact_number') ?>" maxlength="20" placeholder="09XX-XXX-XXXX" value="<?= esc($val('manager_contact_number')) ?>">
                    <?= $err('manager_contact_number') ?>
                </div>
            </div>
        </div>
    </section>

    <section class="cs-panel mb-4">
        <div class="cs-panel__head"><h2 class="cs-panel__title">E. Branch status</h2></div>
        <div class="cs-panel__body">
            <label class="form-label" for="status">Status *</label>
            <select id="status" name="status" class="form-select" style="max-width: 20rem;">
                <option value="active" <?= $val('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $val('status', 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <div class="form-text">Inactive branches cannot accept new transactions, but their historical records remain accessible to authorized users.</div>
        </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= site_url('/admin/branch-management?tab=branches') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Branch</button>
    </div>
</form>

<script>
(function () {
    var select = document.getElementById('manager_user_id');
    var search = document.getElementById('managerSearch');
    var position = document.getElementById('manager_position');
    var contact = document.getElementById('manager_contact_number');

    // Filter the account list as the admin types.
    search.addEventListener('input', function () {
        var q = search.value.toLowerCase();
        Array.prototype.forEach.call(select.options, function (o) {
            o.hidden = o.value !== '' && q !== '' && o.text.toLowerCase().indexOf(q) === -1;
        });
        select.size = q ? Math.min(6, Math.max(2, Array.prototype.filter.call(select.options, function (o) { return !o.hidden; }).length)) : 1;
    });

    // Auto-fill position and work number from the chosen account (only when still blank).
    select.addEventListener('change', function () {
        var o = select.options[select.selectedIndex];
        if (!o || !o.value) { return; }
        if (!position.value) { position.value = o.getAttribute('data-position') || ''; }
        if (!contact.value) { contact.value = o.getAttribute('data-contact') || ''; }
        select.size = 1;
    });

    <?php if (! $isEdit): ?>
    // Live preview of the generated code; the server assigns the final one.
    var counters = <?= json_encode((object) $counters) ?>;
    var city = document.getElementById('address_city');
    var code = document.getElementById('branchCode');
    function preview() {
        var letters = city.value.replace(/\b(city|of|municipality)\b/ig, '').replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 3);
        if (!letters) { code.value = 'Generated on save'; return; }
        while (letters.length < 3) { letters += 'X'; }
        var prefix = 'KKM-' + letters;
        var n = (counters[prefix] || 0) + 1;
        code.value = prefix + '-' + ('00' + n).slice(-3);
    }
    city.addEventListener('input', preview);
    preview();
    <?php endif; ?>
})();
</script>
<?= $this->endSection() ?>
