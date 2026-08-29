<aside class="sidebar-wrapper app-sidebar p-3">
    <a href="<?= base_url('dashboard') ?>" class="d-flex align-items-center gap-3 mb-4 text-decoration-none text-white">
        <span class="rounded-4 d-inline-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem; background: linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,.08));">
            <img src="<?= base_url('assets/images/logo-icon.svg') ?>" alt="Logo" width="28">
        </span>
        <span>
            <span class="d-block fw-bold lh-1">Plan Holder</span>
            <small class="text-white-50">Service dashboard</small>
        </span>
    </a>
    <nav class="nav flex-column gap-2">
        <a class="nav-link text-white rounded-4 px-3 py-2" href="<?= base_url('client/dashboard') ?>"><i class="ti ti-layout-dashboard me-2"></i>Dashboard</a>
        <a class="nav-link text-white rounded-4 px-3 py-2" href="<?= base_url('client/profile') ?>"><i class="ti ti-user-circle me-2"></i>My Profile</a>
        <a class="nav-link text-white rounded-4 px-3 py-2" href="<?= base_url('client/payment') ?>"><i class="ti ti-receipt me-2"></i>Payment</a>
        <a class="nav-link text-white rounded-4 px-3 py-2" href="<?= base_url('client/service') ?>"><i class="ti ti-clipboard-list me-2"></i>Service</a>
        <a class="nav-link text-white rounded-4 px-3 py-2" href="<?= base_url('client/membership') ?>"><i class="ti ti-certificate me-2"></i>Membership Details</a>
        <a class="nav-link text-white rounded-4 px-3 py-2" href="<?= base_url('client/notification') ?>"><i class="ti ti-bell me-2"></i>Notification</a>
        <a class="nav-link text-danger rounded-4 px-3 py-2 mt-2" href="<?= base_url('logout') ?>"><i class="ti ti-logout me-2"></i>Logout</a>
    </nav>
</aside>
