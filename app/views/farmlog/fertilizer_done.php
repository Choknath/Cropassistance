<?php
/**
 * FARMLOG — Mark Fertilizer as Done
 * File: app/views/farmlog/fertilizer_done.php
 * Route: POST fertilizer/done
 * No HTML — action only.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('fertilizer')); exit; }

$user_id      = $_SESSION['user']['id'];
$fert_id      = (int)($_POST['fertilizer_id'] ?? 0);
$crop_id      = (int)($_POST['crop_id']       ?? 0);
// Where to redirect after — if crop_id given go back to crop detail, else fertilizer page
$redirect     = $crop_id > 0 ? url('crops/' . $crop_id) : url('fertilizer');

if ($fert_id <= 0) { header('Location: ' . $redirect); exit; }

try {
    db()->table('fertilizer_schedule')
        ->where('id', $fert_id)
        ->where('user_id', $user_id)
        ->update(['is_done' => 1]);

    $_SESSION['flash_success'] = 'Fertilizer application marked as done! ✅';
} catch (Exception $e) {
    error_log('Fertilizer done error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not update. Please try again.';
}

header('Location: ' . $redirect); exit;