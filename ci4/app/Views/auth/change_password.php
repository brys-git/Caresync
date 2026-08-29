<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - CareSync</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <style>
        :root {
            --login-bg: #0f172a;
            --login-surface: #ffffff;
            --login-accent: #2563eb;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.20), transparent 28%),
                radial-gradient(circle at bottom right, rgba(20, 184, 166, 0.16), transparent 26%),
                linear-gradient(135deg, #eef4ff 0%, #f7fbff 45%, #eefcf9 100%);
        }

        .auth-shell {
            min-height: 100vh;
            padding: 1.25rem;
        }

        .auth-frame {
            width: 100%;
            max-width: 1080px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.18);
            background: var(--login-surface);
        }

        .auth-hero {
            position: relative;
            background: linear-gradient(160deg, rgba(15, 23, 42, 0.96), rgba(37, 99, 235, 0.92));
            color: #fff;
            padding: 3rem;
            min-height: 540px;
            display: flex;
            align-items: center;
        }

        .auth-hero-content {
            position: relative;
            z-index: 1;
            max-width: 420px;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            margin-bottom: 1.5rem;
        }

        .auth-badge img {
            width: 32px;
            height: 32px;
        }

        .auth-title {
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
            margin-bottom: 1rem;
        }

        .auth-copy {
            color: rgba(255, 255, 255, 0.82);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .auth-point {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
            margin-bottom: 0.75rem;
        }

        .auth-point i {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.14);
            display: grid;
            place-items: center;
            font-style: normal;
            font-weight: 700;
        }

        .auth-form-wrap {
            padding: 2rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            min-height: 540px;
            display: flex;
            align-items: center;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .auth-card .card {
            border: 0;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.10);
        }

        .form-control {
            border-radius: 14px;
            min-height: 48px;
        }

        .btn-auth {
            min-height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--login-accent) 0%, #1d4ed8 100%);
            border: 0;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22);
        }

        @media (max-width: 991.98px) {
            .auth-hero {
                min-height: auto;
                padding: 2rem;
            }

            .auth-form-wrap {
                min-height: auto;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="auth-shell d-flex align-items-center justify-content-center">
    <div class="auth-frame row g-0">
        <div class="col-lg-6 auth-hero">
            <div class="auth-hero-content">
                <div class="auth-badge">
                    <img src="<?= base_url('assets/images/logo-icon.svg') ?>" alt="CareSync">
                    <img src="<?= base_url('assets/images/logo.svg') ?>" alt="CareSync" style="max-height: 24px; width: auto;">
                </div>
                <h1 class="auth-title">Secure your account with a new password</h1>
                <p class="auth-copy">Temporary passwords are used for secure onboarding. You must update it before continuing.</p>

                <div class="auth-point"><i>1</i><span>Temporary credential verification</span></div>
                <div class="auth-point"><i>2</i><span>Set a private password</span></div>
                <div class="auth-point"><i>3</i><span>Continue to your role dashboard</span></div>
            </div>
        </div>

        <div class="col-lg-6 auth-form-wrap">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <h2 class="h4 mb-2">Change Your Password</h2>
                            <p class="text-muted mb-0">This account uses a temporary password. Set a new password to continue.</p>
                        </div>

                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif; ?>

                        <?php if (session()->getFlashdata('success')): ?>
                            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= base_url('change-password') ?>">
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
                            <button class="btn btn-auth btn-primary w-100" type="submit">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/main.js') ?>" type="module"></script>
</body>
</html>
