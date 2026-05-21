<aside class="sidebar-wrapper app-sidebar p-3">
    <a href="<?= base_url('dashboard') ?>" class="d-flex align-items-center mb-4 text-decoration-none">
        <img src="<?= base_url('assets/images/logo-icon.svg') ?>" alt="Logo" width="34">
        <span class="ms-2 fw-semibold">System Admin</span>
    </a>
    <nav class="nav flex-column gap-2">
        <a class="nav-link" href="<?= base_url('dashboard/admin') ?>">Dashboard</a>
        <a class="nav-link" href="<?= base_url('admin/branch-management') ?>">Branch Management</a>
        <a class="nav-link" href="<?= base_url('admin/client-management') ?>">Client Management</a>
        <a class="nav-link" href="<?= base_url('admin/payment-monitoring') ?>">Payment Monitoring</a>
        <a class="nav-link" href="<?= base_url('admin/reports') ?>">Reports</a>
        <a class="nav-link" href="<?= base_url('admin/service-offer') ?>">Service Offer</a>
        <a class="nav-link" href="<?= base_url('admin/profile') ?>">Profile</a>
        <a class="nav-link text-danger" href="<?= base_url('logout') ?>">Logout</a>
    </nav>
</aside>
