<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CareSync Sign In</title>
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/mdi/css/materialdesignicons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/ti-icons/css/themify-icons.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/css/vendor.bundle.base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/vendors/font-awesome/css/font-awesome.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('purpleadmin/assets/css/style.css') ?>">
    <link rel="shortcut icon" href="<?= base_url('purpleadmin/assets/images/favicon.png') ?>" />
    <style>
      body { background: #f4f7fb; }
      .auth .auth-form-light { border-radius: 20px; box-shadow: 0 20px 60px rgba(22, 46, 80, 0.08); }
      .auth .brand-logo img { max-width: 130px; }
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
          <div class="row w-100 mx-0">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left p-5">
                <div class="brand-logo text-center mb-4">
                  <img src="<?= base_url('purpleadmin/assets/images/logo.svg') ?>" alt="logo">
                </div>
                <h4>Hello! let's get started</h4>
                <h6 class="font-weight-light">Sign in to continue.</h6>

                <?php if (session()->getFlashdata('error')): ?>
                  <div class="alert alert-danger mt-3"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                  <div class="alert alert-success mt-3"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <form class="pt-3" method="post" action="<?= base_url('login') ?>">
                  <?= csrf_field() ?>
                  <div class="form-group">
                    <input type="text" class="form-control form-control-lg" id="identifier" name="identifier" placeholder="Username or Email" value="<?= old('identifier') ?>" required>
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required>
                  </div>
                  <div class="my-2 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                      <label class="form-check-label text-muted">
                        <input type="checkbox" class="form-check-input" name="remember"> Keep me signed in
                      </label>
                    </div>
                    <a href="<?= base_url('change-password') ?>" class="auth-link text-primary">Forgot password?</a>
                  </div>
                  <div class="mt-3">
                    <button class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn" type="submit">SIGN IN</button>
                  </div>
                  <div class="text-center mt-4 font-weight-light"> Don't have an account? <a href="<?= base_url('register') ?>" class="text-primary">Create</a></div>
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
