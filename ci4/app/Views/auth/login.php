<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1c3a34">
    <title>Sign in · CareSync</title>
    <link rel="icon" href="<?= base_url('assets/favicon_io/favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/main.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/caresync.css') ?>">
    <style>
        /* The only page-specific CSS left in the system. Everything else is tokens. */
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

        /* The plan is a promise kept over decades. That is the one idea this
           panel carries, set in the serif, with nothing competing beside it. */
        .signin__statement {
            font-family: var(--cs-serif);
            font-size: clamp(1.75rem, 2.6vw, 2.5rem);
            line-height: 1.22;
            letter-spacing: -.015em;
            color: #fff;
            max-width: 17ch;
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
            gap: 2.5rem;
            padding-top: 1.75rem;
            border-top: 1px solid rgba(255, 255, 255, .14);
        }
        .signin__fact-value {
            font-size: 1.375rem;
            font-weight: 600;
            color: #fff;
            font-variant-numeric: tabular-nums;
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
            <h1 class="signin__statement">A plan paid for today, honoured decades from now.</h1>
            <p class="signin__sub">
                Branch records, collections, and member plans held in one place —
                so a family is never asked to prove what they already paid for.
            </p>
        </div>

        <div class="signin__facts">
            <div>
                <div class="signin__fact-value">Locked</div>
                <div class="signin__fact-label">Package pricing</div>
            </div>
            <div>
                <div class="signin__fact-value">Per branch</div>
                <div class="signin__fact-label">Record isolation</div>
            </div>
            <div>
                <div class="signin__fact-value">Full</div>
                <div class="signin__fact-label">Payment history</div>
            </div>
        </div>
    </aside>

    <div class="signin__main">
        <div class="signin__form">
            <h2 class="signin__title">Sign in</h2>
            <p class="cs-muted mb-4">Use the username or email your branch issued you.</p>

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

            <form method="post" action="<?= base_url('login') ?>" data-cs-once>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="identifier">Username or email</label>
                    <input class="form-control" id="identifier" name="identifier" type="text"
                           value="<?= old('identifier') ?>" required autofocus autocomplete="username">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password"
                           required autocomplete="current-password">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember" style="font-size:.875rem">Keep me signed in</label>
                    </div>
                    <a href="<?= base_url('forgot-password') ?>" style="font-size:.875rem">Forgot password?</a>
                </div>

                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>

            <hr class="cs-hairline">

            <p class="cs-muted mb-0" style="font-size:.875rem">
                Buying a plan for the first time?
                <a href="<?= base_url('register') ?>">Create a plan holder account</a>
            </p>
        </div>
    </div>
</main>

<script src="<?= base_url('assets/js/caresync.js') ?>" defer></script>
</body>
</html>
