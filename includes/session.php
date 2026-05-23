<?php
// Central session bootstrap.

function app_is_secure_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return (($_SERVER['SERVER_PORT'] ?? null) === '443')
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

function app_regenerate_session(bool $deleteOldSession = false): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id($deleteOldSession);
        $_SESSION['last_session_regeneration'] = time();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => app_is_secure_request(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Regenerate periodically, but do not delete the old session during routine
// requests. Deleting it on every AJAX request can invalidate concurrent calls.
if (empty($_SESSION['last_session_regeneration'])) {
    $_SESSION['last_session_regeneration'] = time();
} elseif (time() - (int)$_SESSION['last_session_regeneration'] > 1800) {
    app_regenerate_session(false);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
