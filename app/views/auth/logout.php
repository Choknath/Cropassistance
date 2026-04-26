<?php
/**
 * SMART CROP ASSISTANT - Logout
 * File: app/views/auth/logout.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store flash message BEFORE destroying
$flash = 'You have been logged out successfully.';

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'],   $params['domain'],
        $params['secure'],  $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Start fresh session for flash message
session_start();
$_SESSION['flash_success'] = $flash;

// Redirect to login
header('Location: ' . url('login'));
exit;