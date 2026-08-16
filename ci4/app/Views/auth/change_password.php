<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CareSync Change Password</title>
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/ti-icons/css/themify-icons.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/font-awesome/css/font-awesome.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/css/style.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('purpleadmin/assets/images/favicon.png') ?>" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
          <div class="row w-100 mx-0">
            <div class="col-lg-8 mx-auto">
              <div class="auth-form-light text-left p-5">
                <div class="brand-logo text-center mb-4">
                  <img src="<?= base_url('purpleadmin/assets/images/logo.svg?v=2') ?>" alt="logo">
                </div>
                <h4>Change Your Password</h4>
                <h6 class="font-weight-light">Set a strong password before continuing.</h6>

                <?php if (session()->getFlashdata('error')): ?>
                  <div class="alert alert-danger mt-3"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                  <div class="alert alert-success mt-3"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <form class="pt-3" method="post" action="<?= base_url('change-password') ?>">
                  <?= csrf_field() ?>
                  <div class="form-group mb-3">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" class="form-control form-control-lg" required>
                  </div>
                  <div class="form-group mb-3">
                    <label for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" class="form-control form-control-lg" required>
                  </div>
                  <div class="form-group mb-4">
                    <label for="new_password_confirm">Confirm New Password</label>
                    <input id="new_password_confirm" name="new_password_confirm" type="password" class="form-control form-control-lg" required>
                  </div>
                  <div class="mt-3">
                    <button type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">Update Password</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
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
