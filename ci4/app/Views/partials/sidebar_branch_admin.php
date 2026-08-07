<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="nav-profile-image">
          <img src="<?= base_url('purpleadmin/assets/images/faces/face1.jpg') ?>" alt="profile" />
          <span class="login-status online"></span>
        </div>
        <div class="nav-profile-text d-flex flex-column">
          <span class="font-weight-bold mb-2"><?= esc(session()->get('user_name') ?? 'Branch Admin') ?></span>
          <span class="text-secondary text-small">Branch Administrator</span>
        </div>
        <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('dashboard/branch-admin') ?>">
        <span class="menu-title">Dashboard</span>
        <i class="mdi mdi-home menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#branch-client" aria-expanded="false" aria-controls="branch-client">
        <span class="menu-title">Client Management</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-account-multiple menu-icon"></i>
      </a>
      <div class="collapse" id="branch-client">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('branch-admin/client-management') ?>">View Clients</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('branch-admin/client/register') ?>">Register Client</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#branch-payments" aria-expanded="false" aria-controls="branch-payments">
        <span class="menu-title">Payment Tracking</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-credit-card menu-icon"></i>
      </a>
      <div class="collapse" id="branch-payments">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('branch-admin/payment-tracking') ?>">Payment Records</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('branch-admin/cash-payment-record') ?>">Record Payment</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#branch-services" aria-expanded="false" aria-controls="branch-services">
        <span class="menu-title">Services</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-package-variant menu-icon"></i>
      </a>
      <div class="collapse" id="branch-services">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('branch-admin/service-package/packages') ?>">Service Packages</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('branch-admin/service-package/services') ?>">Service Offers</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('branch-admin/reports') ?>">
        <span class="menu-title">Reports</span>
        <i class="mdi mdi-chart-bar menu-icon"></i>
      </a>
    </li>
  </ul>
</nav>
