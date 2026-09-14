<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1c3a34">
    <title>Change password · CareSync</title>
    <link rel="icon" href="<?= base_url('assets/favicon_io/favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/caresync.css') ?>">
    <style>
        /* Same signin shell as auth/login.php - the pre-auth screens share one look. */
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
            max-width: 18ch;
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
        }

        .signin__form { width: 100%; max-width: 360px; }

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
            <h1 class="signin__statement">Secure your account with a new password.</h1>
            <p class="signin__sub">
                Temporary passwords are used for secure onboarding. You must
                update it before continuing.
            </p>
        </div>

        <div class="signin__facts">
            <div>
                <div class="signin__fact-value">Step 1</div>
                <div class="signin__fact-label">Temporary credential verification</div>
            </div>
            <div>
                <div class="signin__fact-value">Step 2</div>
                <div class="signin__fact-label">Set a private password</div>
            </div>
            <div>
                <div class="signin__fact-value">Step 3</div>
                <div class="signin__fact-label">Continue to your role dashboard</div>
            </div>
        </div>
    </aside>

    <div class="signin__main">
        <div class="signin__form">
            <h2 class="signin__title">Change Your Password</h2>
            <p class="cs-muted mb-4">This account uses a temporary password. Set a new password to continue.</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="ti ti-alert-circle" aria-hidden="true"></i>
                    <span><?= esc(session()->getFlashdata('error')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success" role="status">
                    <i class="ti ti-circle-check" aria-hidden="true"></i>
                    <span><?= esc(session()->getFlashdata('success')) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('change-password') ?>" data-cs-once>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password_confirm">Confirm New Password</label>
                    <input id="new_password_confirm" name="new_password_confirm" type="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Update Password</button>
            </form>
        </div>
    </div>
</main>

<script src="<?= base_url('assets/js/caresync.js') ?>" defer></script>
</body>
</html>
