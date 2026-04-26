<?php
/**
 * AUTH MIDDLEWARE
 * File: app/middlewares/auth.php
 *
 * This runs BEFORE any protected route.
 * If the user is not logged in, they get
 * redirected to the login page automatically.
 *
 * LavaLite middleware must return:
 *   true  → allow the request to continue
 *   false → block the request
 */

return function($method, $params) {

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    // We store user info in $_SESSION['user'] after login
    if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {

        // Save the page they were trying to visit
        // so we can redirect them back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';

        // Redirect to login page
        header('Location: ' . url('login'));
        exit;
    }

    // User is logged in — allow request to continue
    return true;
};