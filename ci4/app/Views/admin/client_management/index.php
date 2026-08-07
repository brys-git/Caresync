<?= $this->extend($role_layout) ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/clients.css') ?>">

<?php
    $filters = $filters ?? [];
    $currentSearch = (string) ($filters['search'] ?? '');
    $currentStatus = (string) ($filters['status'] ?? '');
    $perPage = 10;
    $totalItems = count($clients ?? []);
    $currentPage = max(1, (int) ($_GET['page'] ?? 1));
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $offset = ($currentPage - 1) * $perPage;
    $pageClients = array_slice($clients ?? [], $offset, $perPage);
?>

<div class="cm">

    <!-- ====== Header ====== -->
    <div class="cm-header">
        <div class="cm-header__text">
            <h1 class="cm-header__title">Clients</h1>
            <p class="cm-header__sub">Manage and view all registered system clients.</p>
        </div>
    </div>

    <!-- ====== Flash Messages ====== -->
    <?php if (session()->getFlashdata('error')): ?>
        <div style="background:#fff5f5;border:1px solid #fed7d7;color:#e53e3e;padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:600;">
            <i class="mdi mdi-alert-circle-outline"></i>
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div style="background:#f0fff4;border:1px solid #c6f6d5;color:#38a169;padding:12px 16px;border-radius:10px;font-size:0.86rem;font-weight:600;">
            <i class="mdi mdi-check-circle-outline"></i>
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>

    <!-- ====== Filter Bar ====== -->
    <form method="get" action="<?= site_url('admin/client-management') ?>" class="cm-filter-bar">
        <div class="cm-search">
            <i class="mdi mdi-magnify"></i>
            <input type="text" name="search" value="<?= esc($currentSearch) ?>" placeholder="Search clients by name, ID, or contact...">
        </div>

        <div class="cm-status-tabs">
            <button type="button" class="cm-tab <?= $currentStatus === '' ? 'cm-tab--active' : '' ?>" onclick="cmSetStatus('')">All</button>
            <button type="button" class="cm-tab <?= $currentStatus === 'active' ? 'cm-tab--active' : '' ?>" onclick="cmSetStatus('active')">Active</button>
            <button type="button" class="cm-tab <?= $currentStatus === 'inactive' ? 'cm-tab--active' : '' ?>" onclick="cmSetStatus('inactive')">Inactive</button>
        </div>
        <input type="hidden" name="status" id="cm-status-input" value="<?= esc($currentStatus) ?>">

        <a href="<?= site_url('plan-holders/register') ?>" class="cm-btn cm-btn--purple">
            <i class="mdi mdi-plus"></i> Register New Client
        </a>
    </form>

    <!-- ====== Main layout: Table + Sidebar ====== -->
    <div class="cm-layout">

        <!-- Table -->
        <div class="cm-card">
            <div class="cm-table-wrap">
                <table class="cm-table">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Client ID</th>
                            <th>Contact Number</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pageClients)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="cm-empty">
                                        <i class="mdi mdi-account-outline"></i>
                                        No clients found.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pageClients as $client):
                                $holderStatus = strtolower((string) ($client['plan_holder_status'] ?? 'inactive'));
                                $clientId = (int) ($client['plan_holder_id'] ?? 0);
                                $fullName = trim((string) ($client['first_name'] ?? '') . ' ' . (string) ($client['last_name'] ?? ''));
                                $initials = strtoupper(mb_substr($client['first_name'] ?? '', 0, 1) . mb_substr($client['last_name'] ?? '', 0, 1));
                                $contact = (string) ($client['contact_number'] ?? '-');
                                $viewUrl = site_url('admin/client-management/view/' . $clientId);
                            ?>
                            <tr>
                                <td>
                                    <div class="cm-client">
                                        <div class="cm-avatar"><?= esc($initials ?: '?') ?></div>
                                        <div>
                                            <div class="cm-client__name"><?= esc($fullName ?: 'Unknown') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>CL-<?= esc((string) $clientId) ?></td>
                                <td><?= esc($contact) ?></td>
                                <td>
                                    <span class="cm-badge <?= $holderStatus === 'active' ? 'cm-badge--active' : 'cm-badge--inactive' ?>">
                                        <?= $holderStatus === 'active' ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="cm-actions">
                                        <a href="<?= $viewUrl ?>" class="cm-action-icon" title="View client">
                                            <i class="mdi mdi-magnify"></i>
                                        </a>
                                        <div class="cm-dropdown">
                                            <button class="cm-action-icon" onclick="cmToggleDropdown(this)" title="More actions" style="cursor:pointer;">
                                                <i class="mdi mdi-dots-horizontal"></i>
                                            </button>
                                            <div class="cm-dropdown__menu">
                                                <a href="<?= $viewUrl ?>" class="cm-dropdown__item">
                                                    <i class="mdi mdi-eye-outline"></i> View Details
                                                </a>
                                                <a href="<?= site_url('admin/client-management/edit/' . $clientId) ?>" class="cm-dropdown__item">
                                                    <i class="mdi mdi-pencil-outline"></i> Edit Client
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="cm-pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= site_url('admin/client-management?status=' . rawurlencode($currentStatus) . '&search=' . rawurlencode($currentSearch) . '&page=' . ($currentPage - 1)) ?>" class="cm-page-btn cm-page-btn--nav" title="Previous">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    if ($startPage > 1): ?>
                        <a href="<?= site_url('admin/client-management?status=' . rawurlencode($currentStatus) . '&search=' . rawurlencode($currentSearch) . '&page=1') ?>" class="cm-page-btn">1</a>
                        <?php if ($startPage > 2): ?><span style="padding:0 4px;color:#a0aec0;">…</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <a href="<?= site_url('admin/client-management?status=' . rawurlencode($currentStatus) . '&search=' . rawurlencode($currentSearch) . '&page=' . $p) ?>"
                           class="cm-page-btn <?= $p === $currentPage ? 'cm-page-btn--active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?><span style="padding:0 4px;color:#a0aec0;">…</span><?php endif; ?>
                        <a href="<?= site_url('admin/client-management?status=' . rawurlencode($currentStatus) . '&search=' . rawurlencode($currentSearch) . '&page=' . $totalPages) ?>" class="cm-page-btn"><?= $totalPages ?></a>
                    <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= site_url('admin/client-management?status=' . rawurlencode($currentStatus) . '&search=' . rawurlencode($currentSearch) . '&page=' . ($currentPage + 1)) ?>" class="cm-page-btn cm-page-btn--nav" title="Next">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar KPIs -->
        <div class="cm-sidebar">
            <div class="cm-kpi">
                <div class="cm-kpi__label">Total Clients:</div>
                <div class="cm-kpi__value"><?= (int) ($total_clients ?? 0) ?></div>
            </div>
            <div class="cm-kpi">
                <div class="cm-kpi__label">Active Clients:</div>
                <div class="cm-kpi__value"><?= (int) ($active_clients ?? 0) ?></div>
            </div>
            <div class="cm-kpi">
                <div class="cm-kpi__label">New Clients (This Month):</div>
                <div class="cm-kpi__value"><?= (int) ($new_clients_month ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ====== Scripts ====== -->
<script>
(function () {
    'use strict';

    /* Status tab filter */
    window.cmSetStatus = function (status) {
        document.getElementById('cm-status-input').value = status;
        document.querySelector('form').submit();
    };

    /* Dropdown toggle */
    window.cmToggleDropdown = function (btn) {
        var menu = btn.nextElementSibling;
        var isOpen = menu.classList.contains('show');
        cmCloseAllDropdowns();
        if (!isOpen) menu.classList.add('show');
    };

    function cmCloseAllDropdowns() {
        document.querySelectorAll('.cm-dropdown__menu.show').forEach(function (m) {
            m.classList.remove('show');
        });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.cm-dropdown')) cmCloseAllDropdowns();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cmCloseAllDropdowns();
    });
})();
</script>
<?= $this->endSection() ?>
