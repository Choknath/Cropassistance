<?php
/**
 * SMART CROP ASSISTANT + FARMLOG
 * File: app/views/farmlog/plots_delete.php
 *
 * This file has NO HTML — it only processes a DELETE action.
 *
 * It receives a POST request from the delete form on plots.php,
 * deletes the record from the database (only if it belongs to
 * the logged-in user), then redirects back to plots.php.
 *
 * Route: POST plots/delete
 *
 * SECURITY RULES:
 *   1. Must be POST (not GET) — prevents accidental deletion via URL
 *   2. Must be logged in (session check)
 *   3. plot must belong to THIS user (user_id check in WHERE clause)
 */

// ── Session guard ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) {
    header('Location: ' . url('login')); exit;
}

// ── Only allow POST requests ───────────────────────────────
// If someone tries to visit this URL in their browser directly,
// it would be a GET request — we reject those.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('plots')); exit;
}

$user_id = $_SESSION['user']['id'];

// ── Get the plot ID from the hidden form field ─────────────
$plot_id = (int)($_POST['plot_id'] ?? 0);

if ($plot_id <= 0) {
    $_SESSION['flash_error'] = 'Invalid plot. Please try again.';
    header('Location: ' . url('plots')); exit;
}

// ── Attempt to delete ──────────────────────────────────────
try {
    // First: fetch the plot so we can use its name in the flash message
    // AND to confirm it exists + belongs to this user
    $plot = db()->table('field_plots')
                ->where('id', $plot_id)
                ->where('user_id', $user_id) // ← SECURITY CHECK
                ->get();

    if (!$plot) {
        // Either doesn't exist OR belongs to another user
        $_SESSION['flash_error'] = 'Plot not found or you do not have permission to delete it.';
        header('Location: ' . url('plots')); exit;
    }

    // Safe to delete now
    db()->table('field_plots')
        ->where('id', $plot_id)
        ->where('user_id', $user_id)
        ->delete();

    $_SESSION['flash_success'] = "Plot \"{$plot['plot_name']}\" was deleted successfully.";

} catch (Exception $e) {
    error_log('Plot delete error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not delete the plot. Please try again.';
}

// ── Redirect back to the plots list ───────────────────────
header('Location: ' . url('plots'));
exit;