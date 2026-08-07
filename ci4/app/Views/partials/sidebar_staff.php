<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="nav-profile-image">
          <img src="<?= esc(session()->get('avatar') ? base_url('uploads/avatars/' . session()->get('avatar')) : base_url('purpleadmin/assets/images/faces/face1.jpg')) ?>" alt="profile" />
          <span class="login-status online"></span>
        </div>
        <div class="nav-profile-text d-flex flex-column">
          <span class="font-weight-bold mb-2"><?= esc((trim((string) (session()->get('first_name') . ' ' . session()->get('last_name'))) ?: 'Staff')) ?></span>
          <span class="text-secondary text-small">Staff Member</span>
        </div>
        <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('dashboard/staff') ?>">
        <span class="menu-title">Dashboard</span>
        <i class="mdi mdi-home menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#staff-client" aria-expanded="false" aria-controls="staff-client">
        <span class="menu-title">Client Management</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-account-multiple menu-icon"></i>
      </a>
      <div class="collapse" id="staff-client">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/client-management') ?>">Clients</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/client/register') ?>">Register Client</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#staff-payments" aria-expanded="false" aria-controls="staff-payments">
        <span class="menu-title">Payments</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-credit-card menu-icon"></i>
      </a>
      <div class="collapse" id="staff-payments">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/payment-management') ?>">Payment Records</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/payment-management') ?>">Record Payment</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/payment-management') ?>">Record Initial Payment</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#staff-services" aria-expanded="false" aria-controls="staff-services">
        <span class="menu-title">Services</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-package-variant menu-icon"></i>
      </a>
      <div class="collapse" id="staff-services">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/services') ?>">Services</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('staff/packages') ?>">Packages</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('staff/reports') ?>">
        <span class="menu-title">Reports</span>
        <i class="mdi mdi-file-chart menu-icon"></i>
      </a>
    </li>
  </ul>
</nav>
