<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/branches.css') ?>">

<div class="br">

    <!-- ====== Header ====== -->
    <div class="br-header">
        <div class="br-header__text">
            <h1 class="br-header__title">Branches</h1>
            <p class="br-header__sub">Manage all company branches and locations.</p>
        </div>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="br-alert br-alert--error">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="br-alert br-alert--success">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== KPI Cards ====== -->
    <div class="br-kpis">
        <div class="br-kpi">
            <div class="br-kpi__label">Total Branches</div>
            <div class="br-kpi__value"><?= (int) ($total_branches ?? 0) ?></div>
        </div>
        <div class="br-kpi">
            <div class="br-kpi__label">Active Branches <span class="br-kpi__badge">Active</span></div>
            <div class="br-kpi__value"><?= (int) ($active_branches ?? 0) ?></div>
        </div>
        <div class="br-kpi">
            <div class="br-kpi__label">Total Managers</div>
            <div class="br-kpi__value"><?= (int) ($total_managers ?? 0) ?></div>
        </div>
    </div>

    <!-- ====== Filters ====== -->
    <div class="br-filters">
        <div class="br-search">
            <i class="mdi mdi-magnify"></i>
            <input type="text" id="br-search" placeholder="Search branch by name or location..." oninput="brFilterBranches()">
        </div>

        <div class="br-filter-group">
            <label class="br-filter-label">Status</label>
            <div class="br-tabs" id="br-status-tabs">
                <button class="br-tab br-tab--active" data-filter="all" onclick="brSetStatus(this)">All</button>
                <button class="br-tab" data-filter="active" onclick="brSetStatus(this)">Active</button>
                <button class="br-tab" data-filter="inactive" onclick="brSetStatus(this)">Inactive</button>
            </div>
        </div>

        <div class="br-filter-group">
            <label class="br-filter-label">Sorted By</label>
            <select class="br-sort" id="br-sort" onchange="brSortBranches()">
                <option value="name">Name</option>
                <option value="date">Date</option>
                <option value="manager">Manager</option>
            </select>
        </div>

        <div class="br-filter-group" style="justify-content:flex-end;">
            <label class="br-filter-label">&nbsp;</label>
            <button type="button" class="br-btn br-btn--filled" onclick="brOpenModal()">
                <i class="mdi mdi-plus"></i> Add New Branch
            </button>
        </div>
    </div>

    <!-- ====== Branch Cards Grid ====== -->
    <div class="br-grid" id="br-grid">
        <?php if (empty($branches)): ?>
            <div class="br-empty">
                <i class="mdi mdi-office-building-outline"></i>
                No branches found. Click "Add New Branch" to create one.
            </div>
        <?php else: ?>
            <?php foreach ($branches as $branch):
                $status = strtolower((string) ($branch['status'] ?? 'inactive'));
                $branchId = (int) ($branch['branch_id'] ?? 0);
                $bid = 'BID-' . str_pad((string) $branchId, 4, '0', STR_PAD_LEFT);

                $address = trim(implode(', ', array_filter([
                    trim((string) ($branch['address_barangay'] ?? '')),
                    trim((string) ($branch['address_city'] ?? '')),
                ])));
                if (trim((string) ($branch['address_province'] ?? ''))) {
                    $address .= ', ' . trim((string) $branch['address_province']);
                }

                $managerName = trim(
                    trim((string) ($branch['manager_first_name'] ?? ''))
                    . ' ' . trim((string) ($branch['manager_last_name'] ?? ''))
                );
                $managerPosition = trim((string) ($branch['manager_position'] ?? 'Branch Manager'));
                $contact = trim((string) ($branch['contact_number'] ?? ''));

                $dateEstablished = trim((string) ($branch['date_established'] ?? ''));
            ?>
            <div class="br-card"
                 data-status="<?= esc($status) ?>"
                 data-name="<?= esc(strtolower((string) ($branch['branch_name'] ?? ''))) ?>"
                 data-date="<?= esc($dateEstablished) ?>"
                 data-manager="<?= esc(strtolower($managerName)) ?>"
                 data-address="<?= esc(strtolower($address)) ?>">

                <div class="br-card__top">
                    <div class="br-card__icon">
                        <i class="mdi mdi-office-building"></i>
                    </div>
                    <div>
                        <h3 class="br-card__title"><?= esc($branch['branch_name'] ?? 'Branch') ?></h3>
                        <div class="br-card__id"><?= $bid ?></div>
                    </div>
                </div>

                <div class="br-card__badge <?= $status === 'active' ? 'br-card__badge--active' : 'br-card__badge--inactive' ?>">
                    <?= $status === 'active' ? 'Active' : 'Inactive' ?>
                </div>

                <div class="br-card__details">
                    <?php if ($address !== ''): ?>
                        <div class="br-card__detail">
                            <i class="mdi mdi-map-marker-outline"></i>
                            <?= esc($address) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($managerName !== ''): ?>
                        <div class="br-card__detail">
                            <i class="mdi mdi-account-outline"></i>
                            <?= esc($managerName) ?>, <?= esc($managerPosition) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($contact !== ''): ?>
                        <div class="br-card__detail">
                            <i class="mdi mdi-phone-outline"></i>
                            <?= esc($contact) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="br-card__footer">
                    <div class="br-card__footer-left">
                        <a href="<?= site_url('admin/client-management?branch_id=' . $branchId) ?>" class="br-btn br-btn--outline br-btn--sm">View Details</a>
                    </div>
                    <div class="br-dropdown">
                        <button class="br-btn br-btn--ghost br-btn--sm" onclick="brToggleDropdown(this)" title="Options">
                            <i class="mdi mdi-dots-horizontal"></i> (Options)
                        </button>
                        <div class="br-dropdown__menu">
                            <a href="<?= site_url('admin/client-management?branch_id=' . $branchId) ?>" class="br-dropdown__item">
                                <i class="mdi mdi-eye-outline"></i> View Details
                            </a>
                            <button class="br-dropdown__item" onclick="brCloseAllDropdowns()">
                                <i class="mdi mdi-account-plus-outline"></i> Assign User
                            </button>
                            <button class="br-dropdown__item" onclick="brCloseAllDropdowns()">
                                <i class="mdi mdi-chart-bar"></i> Branch Activity
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ====== Add Branch Modal ====== -->
<div class="br-modal-overlay" id="br-modal">
    <div class="br-modal">
        <div class="br-modal__header">
            <h2 class="br-modal__title">Add New Branch</h2>
            <button type="button" class="br-modal__close" onclick="brCloseModal()"><i class="mdi mdi-close"></i></button>
        </div>
        <div class="br-modal__body">
            <form method="post" action="<?= site_url('/branches/store') ?>" id="br-add-form">
                <?= csrf_field() ?>

                <div class="br-form-group">
                    <label class="br-form-label" for="br-f-name">Branch Name <span style="color:#e53e3e;">*</span></label>
                    <input class="br-form-input" id="br-f-name" name="branch_name" type="text" value="<?= old('branch_name') ?>" required>
                </div>

                <div class="br-form-group">
                    <label class="br-form-label" for="br-f-contact">Contact Number</label>
                    <input class="br-form-input" id="br-f-contact" name="contact_number" type="text" value="<?= old('contact_number') ?>">
                </div>

                <div class="br-form-section">
                    <h3 class="br-form-section-title">Address</h3>
                    <div class="br-form-group">
                        <label class="br-form-label" for="br-f-street">Street</label>
                        <input class="br-form-input" id="br-f-street" name="address_street" type="text" value="<?= old('address_street') ?>">
                    </div>
                    <div class="br-form-row br-form-row--2">
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-barangay">Barangay <span style="color:#e53e3e;">*</span></label>
                            <input class="br-form-input" id="br-f-barangay" name="address_barangay" type="text" value="<?= old('address_barangay') ?>" required>
                        </div>
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-city">City <span style="color:#e53e3e;">*</span></label>
                            <input class="br-form-input" id="br-f-city" name="address_city" type="text" value="<?= old('address_city') ?>" required>
                        </div>
                    </div>
                    <div class="br-form-group">
                        <label class="br-form-label" for="br-f-province">Province <span style="color:#e53e3e;">*</span></label>
                        <input class="br-form-input" id="br-f-province" name="address_province" type="text" value="<?= old('address_province') ?>" required>
                    </div>
                </div>

                <div class="br-form-section">
                    <h3 class="br-form-section-title">Branch Manager</h3>
                    <div class="br-form-row br-form-row--3">
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-mfirst">First Name</label>
                            <input class="br-form-input" id="br-f-mfirst" name="manager_first_name" type="text" value="<?= old('manager_first_name') ?>">
                        </div>
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-mmid">Middle Name</label>
                            <input class="br-form-input" id="br-f-mmid" name="manager_middle_name" type="text" value="<?= old('manager_middle_name') ?>">
                        </div>
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-mlast">Last Name</label>
                            <input class="br-form-input" id="br-f-mlast" name="manager_last_name" type="text" value="<?= old('manager_last_name') ?>">
                        </div>
                    </div>
                    <div class="br-form-row br-form-row--2">
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-mpos">Position</label>
                            <input class="br-form-input" id="br-f-mpos" name="manager_position" type="text" value="<?= old('manager_position', 'Branch Manager') ?>">
                        </div>
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-mext">Extension</label>
                            <input class="br-form-input" id="br-f-mext" name="manager_extension" type="text" value="<?= old('manager_extension') ?>">
                        </div>
                    </div>
                </div>

                <div class="br-form-section">
                    <div class="br-form-row br-form-row--2">
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-date">Date Established</label>
                            <input class="br-form-input" id="br-f-date" name="date_established" type="date" value="<?= old('date_established') ?>">
                        </div>
                        <div class="br-form-group">
                            <label class="br-form-label" for="br-f-status">Status <span style="color:#e53e3e;">*</span></label>
                            <select class="br-form-select" id="br-f-status" name="status" required>
                                <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="br-modal__footer">
            <button type="button" class="br-btn br-btn--outline" onclick="brCloseModal()">Cancel</button>
            <button type="submit" form="br-add-form" class="br-btn br-btn--filled">
                <i class="mdi mdi-plus"></i> Add Branch
            </button>
        </div>
    </div>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    var currentStatus = 'all';
    var currentSort = 'name';

    /* --- Filter & Sort --- */
    window.brSetStatus = function (btn) {
        document.querySelectorAll('#br-status-tabs .br-tab').forEach(function (t) {
            t.classList.remove('br-tab--active');
        });
        btn.classList.add('br-tab--active');
        currentStatus = btn.getAttribute('data-filter');
        brFilterBranches();
    };

    window.brSortBranches = function () {
        currentSort = document.getElementById('br-sort').value;
        brFilterBranches();
    };

    window.brFilterBranches = function () {
        var searchVal = (document.getElementById('br-search').value || '').toLowerCase().trim();
        var cards = document.querySelectorAll('#br-grid .br-card');
        var visible = [];

        cards.forEach(function (card) {
            var matchStatus = currentStatus === 'all' || card.getAttribute('data-status') === currentStatus;
            var matchSearch = !searchVal ||
                (card.getAttribute('data-name') || '').indexOf(searchVal) !== -1 ||
                (card.getAttribute('data-address') || '').indexOf(searchVal) !== -1;

            if (matchStatus && matchSearch) {
                card.style.display = '';
                visible.push(card);
            } else {
                card.style.display = 'none';
            }
        });

        /* Sort visible cards */
        visible.sort(function (a, b) {
            if (currentSort === 'name') {
                return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
            } else if (currentSort === 'date') {
                return (a.getAttribute('data-date') || '').localeCompare(b.getAttribute('data-date') || '');
            } else if (currentSort === 'manager') {
                return (a.getAttribute('data-manager') || '').localeCompare(b.getAttribute('data-manager') || '');
            }
            return 0;
        });

        var grid = document.getElementById('br-grid');
        visible.forEach(function (card) { grid.appendChild(card); });
    };

    /* --- Modal --- */
    window.brOpenModal = function () {
        document.getElementById('br-modal').classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.brCloseModal = function () {
        document.getElementById('br-modal').classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('br-add-form').reset();
    };

    document.getElementById('br-modal').addEventListener('click', function (e) {
        if (e.target === this) brCloseModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            brCloseModal();
            brCloseAllDropdowns();
        }
    });

    /* --- Dropdowns --- */
    window.brToggleDropdown = function (btn) {
        var menu = btn.nextElementSibling;
        var isOpen = menu.classList.contains('show');
        brCloseAllDropdowns();
        if (!isOpen) menu.classList.add('show');
    };

    window.brCloseAllDropdowns = function () {
        document.querySelectorAll('.br-dropdown__menu.show').forEach(function (m) {
            m.classList.remove('show');
        });
    };

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.br-dropdown')) {
            brCloseAllDropdowns();
        }
    });
})();
</script>
<?= $this->endSection() ?>
