<?php
// session.php

if (session_status() == PHP_SESSION_NONE) {

    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Set to your domain if needed
        'secure' => isset($_SERVER['HTTPS']), // Ensure this is only used over HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
?>
