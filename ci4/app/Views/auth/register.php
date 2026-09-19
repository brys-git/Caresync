<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1c3a34">
    <title>Create account · CareSync</title>
    <link rel="icon" href="<?= base_url('assets/favicon_io/favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/caresync.css') ?>">
</head>
<body>
<main class="signin">
    <aside class="signin__aside">
        <div class="signin__wordmark">
            <img src="<?= base_url('assets/images/logo-icon.svg') ?>" alt="" width="26">
            <span>CareSync</span>
        </div>

        <div>
            <h1 class="signin__statement">Register your plan holder account.</h1>
            <p class="signin__sub">
                Create your personal account first, then complete plan holder
                registration after signing in.
            </p>
        </div>

        <div class="signin__facts signin__facts--stacked">
            <div>
                <div class="signin__fact-value">Unique identifier</div>
                <div class="signin__fact-label">Every profile is trackable from day one</div>
            </div>
            <div>
                <div class="signin__fact-value">Post-login step</div>
                <div class="signin__fact-label">Full plan holder registration comes after sign-in</div>
            </div>
            <div>
                <div class="signin__fact-value">Branch-aware</div>
                <div class="signin__fact-label">Records stay isolated to the right branch</div>
            </div>
        </div>
    </aside>

    <div class="signin__main">
        <div class="signin__form">
            <h2 class="signin__title">Register as Plan Holder</h2>
            <p class="cs-muted mb-4">Create your personal account, then complete plan holder registration after signing in.</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="ti ti-alert-circle" aria-hidden="true"></i>
                    <span><?= esc(session()->getFlashdata('error')) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('register') ?>" data-cs-once>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input id="username" name="username" type="text" class="form-control" value="<?= old('username') ?>" placeholder="Choose a username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-control" value="<?= old('email') ?>" placeholder="name@example.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="first_name">First Name</label>
                        <input id="first_name" name="first_name" type="text" class="form-control" value="<?= old('first_name') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input id="last_name" name="last_name" type="text" class="form-control" value="<?= old('last_name') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="unique_identifier">Unique Identifier (Optional, for existing membership linking)</label>
                        <input id="unique_identifier" name="unique_identifier" type="text" class="form-control" value="<?= old('unique_identifier') ?>" placeholder="Enter existing membership identifier if available">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input id="contact_number" name="contact_number" type="text" class="form-control" value="<?= old('contact_number') ?>" placeholder="09xxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" name="password" type="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Confirm Password</label>
                        <input id="password_confirm" name="password_confirm" type="password" class="form-control" required>
                    </div>
                </div>

                <button class="btn btn-primary w-100 mt-4" type="submit">Submit Registration</button>
            </form>

            <hr class="cs-hairline">

            <p class="cs-muted mb-0" style="font-size:.875rem">
                Already have an account?
                <a href="<?= base_url('login') ?>">Back to sign in</a>
            </p>
        </div>
    </div>
</main>

<script src="<?= base_url('assets/js/caresync.js') ?>" defer></script>
</body>
</html>
