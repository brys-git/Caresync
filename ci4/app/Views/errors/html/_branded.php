<?php
/**
 * Shared branded error page (404 and production errors). Standalone on purpose:
 * it must render even when the framework could not finish booting, so it only
 * uses plain PHP and optional helpers. It never prints a message from the
 * exception, a file path or a stack trace.
 *
 * Expects: $errorCode (string), $errorTitle, $errorText.
 */
$base = '/';
try {
    if (function_exists('base_url')) {
        $base = rtrim((string) base_url(), '/') . '/';
    }
} catch (\Throwable $e) {
    $base = '/';
}

$signedIn = false;
try {
    $signedIn = function_exists('session') && session()->has('user_id');
} catch (\Throwable $e) {
    $signedIn = false;
}

$buttonUrl   = $signedIn ? $base . 'dashboard' : $base . 'login';
$buttonLabel = $signedIn ? 'Back to dashboard' : 'Sign in';
$logo        = $base . 'assets/images/company-logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8') ?> - CareSync</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1rem;
            background: #f5f6fa;
            color: #171a2b;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 30rem;
            padding: 2.5rem 2rem;
            text-align: center;
            background: #fff;
            border: 1px solid #dbdfec;
            border-top: 4px solid #1b2473;
            border-radius: 10px;
            box-shadow: 0 6px 16px -4px rgba(23, 26, 43, .12);
        }
        .logo { height: 64px; width: auto; margin-bottom: 1rem; }
        .brand { margin: 0 0 1.5rem; font-size: .9rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #1b2473; }
        .code { margin: 0; font-size: 4.5rem; font-weight: 700; line-height: 1; color: #1b2473; }
        h1 { margin: .5rem 0 .5rem; font-size: 1.375rem; }
        p { margin: 0 0 1.75rem; color: #414659; line-height: 1.5; }
        .btn {
            display: inline-block;
            padding: .65rem 1.5rem;
            border-radius: 6px;
            background: #1b2473;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }
        .btn:hover, .btn:focus { background: #111752; }
        .btn:focus-visible { outline: 3px solid #8f98e0; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="" onerror="this.style.display='none'">
        <p class="brand">Kaagapay CareSync</p>
        <p class="code"><?= htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8') ?></p>
        <h1><?= htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($errorText, ENT_QUOTES, 'UTF-8') ?></p>
        <a class="btn" href="<?= htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8') ?></a>
    </main>
</body>
</html>
