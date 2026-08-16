<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($page_title ?? 'CareSync Staff') ?></title>
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/ti-icons/css/themify-icons.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/font-awesome/css/font-awesome.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/css/style.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('purpleadmin/assets/images/favicon.png') ?>" />
</head>
<body>
    <div class="container-scroller">
        <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <a class="navbar-brand brand-logo" href="<?= base_url('dashboard/staff') ?>"><img src="<?= base_url('purpleadmin/assets/images/logo.svg?v=2') ?>" alt="logo" /></a>
                <a class="navbar-brand brand-logo-mini" href="<?= base_url('dashboard/staff') ?>"><img src="<?= base_url('purpleadmin/assets/images/logo-mini.svg?v=2') ?>" alt="logo" /></a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-stretch">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="mdi mdi-menu"></span>
                </button>
                <div class="search-field d-none d-md-block">
                    <form class="d-flex align-items-center h-100" action="#">
                        <div class="input-group">
                            <div class="input-group-prepend bg-transparent">
                                <i class="input-group-text border-0 mdi mdi-magnify"></i>
                            </div>
                            <input type="text" class="form-control bg-transparent border-0" placeholder="Search staff tasks">
                        </div>
                    </form>
                </div>
                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item nav-profile dropdown">
                        <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="nav-profile-img">
                                <img src="<?= esc(session()->get('avatar') ? base_url('uploads/avatars/' . session()->get('avatar')) : base_url('purpleadmin/assets/images/faces/face1.jpg')) ?>" alt="image">
                                <span class="availability-status online"></span>
                            </div>
                            <div class="nav-profile-text">
                                <p class="mb-1 text-black"><?= esc(trim((string) (session()->get('first_name') . ' ' . session()->get('last_name'))) ?: 'Staff') ?></p>
                            </div>
                        </a>
                        <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                            <a class="dropdown-item" href="<?= base_url('staff/profile') ?>">
                                <i class="mdi mdi-account-circle text-success me-2"></i> Account Settings </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?= base_url('logout') ?>">
                                <i class="mdi mdi-logout text-primary me-2"></i> Signout </a>
                        </div>
                    </li>
                    <li class="nav-item d-none d-lg-block full-screen-link">
                        <a class="nav-link">
                            <i class="mdi mdi-fullscreen" id="fullscreen-button"></i>
                        </a>
                    </li>
                    <li class="nav-item nav-logout d-none d-lg-block">
                        <a class="nav-link" href="<?= base_url('logout') ?>">
                            <i class="mdi mdi-power"></i>
                        </a>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <div class="container-fluid page-body-wrapper">
            <?= $this->include('partials/sidebar_staff') ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <?php if (! empty($page_title)): ?>
                        <div class="page-header">
                            <h3 class="page-title"><?= esc($page_title) ?></h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard/staff') ?>">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Staff</li>
                                </ol>
                            </nav>
                        </div>
                    <?php endif ?>
                    <?= $this->renderSection('content') ?>
                </div>
                <footer class="footer">
                    <div class="d-sm-flex justify-content-center justify-content-sm-between">
                        <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">© <?= date('Y') ?> CareSync Staff access.</span>
                        <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Manage clients with the new admin template.</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    <script src="<?= base_url('purpleadmin/assets/vendors/js/vendor.bundle.base.js') ?>"></script>
    <script src="<?= base_url('purpleadmin/assets/js/off-canvas.js') ?>"></script>
    <script src="<?= base_url('purpleadmin/assets/js/misc.js') ?>"></script>
    <script src="<?= base_url('purpleadmin/assets/js/settings.js') ?>"></script>
    <script src="<?= base_url('purpleadmin/assets/js/todolist.js') ?>"></script>
    <script src="<?= base_url('purpleadmin/assets/js/jquery.cookie.js') ?>"></script>
</body>
</html>
