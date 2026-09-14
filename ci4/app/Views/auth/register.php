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
    <style>
        /* Same signin shell as auth/login.php - the two pre-auth screens share one look. */
        .signin {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--cs-paper);
        }

        .signin__aside {
            background: var(--cs-pine);
            color: #dce5e1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .signin__wordmark {
            display: flex;
            align-items: center;
            gap: .625rem;
            color: #fff;
        }
        .signin__wordmark span {
            font-family: var(--cs-serif);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .signin__statement {
            font-family: var(--cs-serif);
            font-size: clamp(1.75rem, 2.6vw, 2.5rem);
            line-height: 1.22;
            letter-spacing: -.015em;
            color: #fff;
            max-width: 20ch;
            margin: 0 0 1rem;
        }

        .signin__sub {
            font-size: .9375rem;
            color: rgba(255, 255, 255, .62);
            max-width: 42ch;
            margin: 0;
        }

        .signin__facts {
            display: flex;
            flex-direction: column;
            gap: .85rem;
            padding-top: 1.75rem;
            border-top: 1px solid rgba(255, 255, 255, .14);
        }
        .signin__fact-value {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            line-height: 1.2;
        }
        .signin__fact-label {
            font-size: .78125rem;
            color: rgba(255, 255, 255, .55);
        }

        .signin__main {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }

        .signin__form { width: 100%; max-width: 460px; }

        .signin__title {
            font-size: 1.5rem;
            letter-spacing: -.02em;
            margin: 0 0 .25rem;
        }

        @media (max-width: 900px) {
            .signin { grid-template-columns: 1fr; }
            .signin__aside { padding: 2rem 1.5rem; gap: 1.75rem; }
            .signin__facts { display: none; }
            .signin__statement { font-size: 1.5rem; max-width: none; }
            .signin__main { padding: 2rem 1.5rem 3rem; }
        }
    </style>
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

        <div class="signin__facts">
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
