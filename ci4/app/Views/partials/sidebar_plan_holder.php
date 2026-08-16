<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="nav-profile-image">
          <img src="<?= esc(session()->get('avatar') ? base_url('uploads/avatars/' . session()->get('avatar')) : base_url('purpleadmin/assets/images/faces/face1.jpg')) ?>" alt="profile" />
          <span class="login-status online"></span>
        </div>
        <div class="nav-profile-text d-flex flex-column">
          <span class="font-weight-bold mb-2"><?= esc(trim((string) (session()->get('first_name') . ' ' . session()->get('last_name'))) ?: ($plan_holder_name ?? 'Plan Holder')) ?></span>
          <span class="text-secondary text-small">Plan Holder</span>
        </div>
        <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('client/dashboard') ?>">
        <span class="menu-title">Dashboard</span>
        <i class="mdi mdi-home menu-icon"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#client-payments" aria-expanded="false" aria-controls="client-payments">
        <span class="menu-title">Payments</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-credit-card menu-icon"></i>
      </a>
      <div class="collapse" id="client-payments">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('client/payment') ?>">Payment History</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('client/payment/advance') ?>#advance-payment-section">Advance Payment</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="collapse" href="#client-services" aria-expanded="false" aria-controls="client-services">
        <span class="menu-title">Package / Service</span>
        <i class="menu-arrow"></i>
        <i class="mdi mdi-package-variant menu-icon"></i>
      </a>
      <div class="collapse" id="client-services">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="<?= base_url('client/service?tab=packages') ?>">Packages</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('client/service?tab=services') ?>">Services</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?= base_url('client/membership') ?>">
        <span class="menu-title">Membership</span>
        <i class="mdi mdi-account-check menu-icon"></i>
      </a>
    </li>
  </ul>
</nav>
