<aside class="sidebar-wrapper app-sidebar p-3">
    <a href="<?= base_url('dashboard') ?>" class="d-flex align-items-center mb-4 text-decoration-none">
        <img src="<?= base_url('assets/images/logo-icon.svg') ?>" alt="Logo" width="34">
        <span class="ms-2 fw-semibold">Staff</span>
    </a>
    <nav class="nav flex-column gap-2">
        <a class="nav-link" href="<?= base_url('dashboard/staff') ?>">Dashboard</a>
        <a class="nav-link" href="<?= base_url('staff/client-management') ?>">Client Management</a>
        <a class="nav-link" href="<?= base_url('staff/payment-management') ?>">Payment Management</a>
        <a class="nav-link" href="<?= base_url('staff/services') ?>">Services</a>
        <a class="nav-link" href="<?= base_url('staff/reports') ?>">Reports</a>
        <a class="nav-link" href="<?= base_url('staff/profile') ?>">Profile</a>
        <a class="nav-link text-danger" href="<?= base_url('logout') ?>">Logout</a>
    </nav>
</aside>
