<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Holder Registration - CareSync</title>
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
            max-width: 1180px;
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
            min-height: 620px;
            display: flex;
            align-items: center;
        }

        .auth-hero::before,
        .auth-hero::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .auth-hero::before {
            width: 180px;
            height: 180px;
            top: -40px;
            right: -30px;
        }

        .auth-hero::after {
            width: 260px;
            height: 260px;
            bottom: -110px;
            left: -100px;
        }

        .auth-hero-content {
            position: relative;
            z-index: 1;
            max-width: 460px;
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
            font-size: clamp(2.2rem, 4vw, 3.9rem);
            line-height: 1.04;
            letter-spacing: -0.04em;
            margin-bottom: 1rem;
        }

        .auth-copy {
            color: rgba(255, 255, 255, 0.82);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .auth-points {
            display: grid;
            gap: 0.85rem;
        }

        .auth-point {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
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
            min-height: 620px;
            display: flex;
            align-items: center;
        }

        .auth-card {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
        }

        .auth-card .card {
            border: 0;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.10);
        }

        .form-control,
        .form-select {
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

        .btn-auth:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        }

        .auth-link {
            color: var(--login-accent);
            text-decoration: none;
        }

        .auth-link:hover {
            text-decoration: underline;
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
        <div class="col-lg-5 auth-hero">
            <div class="auth-hero-content">
                <div class="auth-badge">
                    <img src="<?= base_url('assets/images/logo-icon.svg') ?>" alt="CareSync">
                    <img src="<?= base_url('assets/images/logo.svg') ?>" alt="CareSync" style="max-height: 24px; width: auto;">
                </div>
                <h1 class="auth-title">Register your plan holder account</h1>
                <p class="auth-copy">Secure enrollment for new clients with clean identity tracking and branch-aware registration.</p>

                <div class="auth-points">
                    <div class="auth-point"><i>1</i><span>Profile linked to a unique identifier</span></div>
                    <div class="auth-point"><i>2</i><span>Post-login plan holder registration step</span></div>
                    <div class="auth-point"><i>3</i><span>Safe and branch-aware plan holder onboarding</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 auth-form-wrap">
            <div class="auth-card">
                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <h2 class="h4 mb-2">Register as Plan Holder</h2>
                            <p class="text-muted mb-0">Create your personal account, then complete plan holder registration after signing in.</p>
                        </div>

                        <?php if (session()->getFlashdata('error')): ?>
                            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= base_url('register') ?>">
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

                            <button class="btn btn-auth btn-primary w-100 mt-4" type="submit">Submit Registration</button>
                            <div class="text-center mt-3 small text-muted">
                                Already have an account? <a href="<?= base_url('login') ?>" class="auth-link">Back to Sign In</a>
                            </div>
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
