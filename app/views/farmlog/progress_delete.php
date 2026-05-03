<?php
/**
 * FARMLOG — Delete Growth Stage Log
 * File: app/views/farmlog/progress_delete.php
 * Route: POST progress/delete
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('crops')); exit; }

$user_id     = $_SESSION['user']['id'];
$progress_id = (int)($_POST['progress_id'] ?? 0);
$crop_id     = (int)($_POST['crop_id']     ?? 0);

if ($progress_id <= 0) { header('Location: ' . url('crops/' . $crop_id)); exit; }

try {
    db()->table('crop_progress')
        ->where('id', $progress_id)
        ->where('user_id', $user_id)
        ->delete();

    $_SESSION['flash_success'] = 'Stage log removed.';
} catch (Exception $e) {
    error_log('Progress delete error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not remove log entry.';
}

header('Location: ' . url('crops/' . $crop_id)); exit;