<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="nav-profile-image">
          <img src="<?= base_url('purpleadmin/assets/images/faces/face1.jpg') ?>" alt="profile" />
          <span class="login-status online"></span>
        </div>
        <div class="nav-profile-text d-flex flex-column">
          <span class="font-weight-bold mb-2"><?= esc(session()->get('user_name') ?? 'System Admin') ?></span>
          <span class="text-secondary text-small">Administrator</span>
        </div>
        <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('dashboard/admin') ?>">
        <span class="menu-title">Dashboard</span>
        <i class="mdi mdi-home menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#admin-branches" aria-expanded="false" aria-controls="admin-branches">
        <span class="menu-title">Branch Management</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-office-building menu-icon"></i>
      </a>
      <div class="collapse" id="admin-branches">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="<?= base_url('admin/branches') ?>">Branches</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= base_url('admin/branch-management') ?>">Package / Service Control</a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#admin-clients" aria-expanded="false" aria-controls="admin-clients">
        <span class="menu-title">Client Management</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-account-multiple menu-icon"></i>
      </a>
      <div class="collapse" id="admin-clients">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/client-management') ?>">Clients</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('plan-holders/register') ?>">Register Client</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/client-import') ?>">Import Records</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#admin-payments" aria-expanded="false" aria-controls="admin-payments">
        <span class="menu-title">Payments</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-credit-card menu-icon"></i>
      </a>
      <div class="collapse" id="admin-payments">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/payment-monitoring') ?>">Payment Monitoring</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/payment-monitoring/export') ?>">Export Payments</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#admin-services" aria-expanded="false" aria-controls="admin-services">
        <span class="menu-title">Package / Service</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-package-variant menu-icon"></i>
      </a>
      <div class="collapse" id="admin-services">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/service-offer') ?>">Service Offers</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/reports') ?>">Reports</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('admin/reports') ?>">
        <span class="menu-title">Reports</span>
        <i class="mdi mdi-chart-bar menu-icon"></i>
      </a>
    </li>
  </ul>
</nav>
