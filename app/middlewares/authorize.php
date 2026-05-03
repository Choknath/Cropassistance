<?php
/**
 * AUTHORIZE MIDDLEWARE
 * File: app/middlewares/authorize.php
 *
 * Checks if the logged-in user has admin role.
 * Runs AFTER the auth middleware.
 */

return function($method, $params) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check role — must be 'admin'
    if (!isset($_SESSION['user']['role']) ||
        $_SESSION['user']['role'] !== 'admin') {

        // Show 403 forbidden page
        http_response_code(403);
        echo '<!DOCTYPE html><html><head>
            <title>405 Forbidden</title>
            <style>
                body { font-family: sans-serif; text-align: center;
                       padding: 80px; background: #f8f7f4; }
                h1   { color: #dc2626; font-size: 48px; margin-bottom: 8px; }
                p    { color: #6b7280; }
                a    { color: #16a34a; font-weight: 600; }
            </style>
        </head><body>
            <h1>403</h1>
            <p>You do not have permission to access this page.</p>
            <p><a href="' . url('dashboard') . '">← Back to Dashboard</a></p>
        </body></html>';
        exit;
    }

    return true;
};