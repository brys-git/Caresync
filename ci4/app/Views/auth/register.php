<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CareSync Register</title>
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
            <div class="col-lg-10 mx-auto">
              <div class="auth-form-light text-left p-5">
                <div class="brand-logo text-center mb-4">
                  <img src="<?= base_url('purpleadmin/assets/images/logo.svg?v=2') ?>" alt="logo">
                </div>
                <h4>Register as Plan Holder</h4>
                <h6 class="font-weight-light">Fill your details to create the account.</h6>

                <?php if (session()->getFlashdata('error')): ?>
                  <div class="alert alert-danger mt-3"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                  <div class="alert alert-success mt-3"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <form class="pt-3" method="post" action="<?= base_url('register') ?>">
                  <?= csrf_field() ?>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="username">Username</label>
                      <input id="username" name="username" type="text" class="form-control form-control-lg" value="<?= old('username') ?>" placeholder="Choose a username" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="email">Email</label>
                      <input id="email" name="email" type="email" class="form-control form-control-lg" value="<?= old('email') ?>" placeholder="name@example.com" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="first_name">First Name</label>
                      <input id="first_name" name="first_name" type="text" class="form-control form-control-lg" value="<?= old('first_name') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="last_name">Last Name</label>
                      <input id="last_name" name="last_name" type="text" class="form-control form-control-lg" value="<?= old('last_name') ?>" required>
                    </div>
                    <div class="col-md-12 mb-3">
                      <label class="form-label" for="contact_number">Contact Number</label>
                      <input id="contact_number" name="contact_number" type="text" class="form-control form-control-lg" value="<?= old('contact_number') ?>" placeholder="09xxxxxxxxx">
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="password">Password</label>
                      <input id="password" name="password" type="password" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label" for="password_confirm">Confirm Password</label>
                      <input id="password_confirm" name="password_confirm" type="password" class="form-control form-control-lg" required>
                    </div>
                  </div>
                  <div class="mt-3">
                    <button type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">Create Account</button>
                  </div>
                  <div class="text-center mt-4 font-weight-light"> Already have an account? <a href="<?= base_url('login') ?>" class="text-primary">Sign In</a></div>
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
