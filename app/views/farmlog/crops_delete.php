<?php
/**
 * FARMLOG — Delete Crop Cycle
 * File: app/views/farmlog/crops_delete.php
 * Route: POST crops/delete
 * No HTML — processes action then redirects.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('crops')); exit; }

$user_id = $_SESSION['user']['id'];
$crop_id = (int)($_POST['crop_id'] ?? 0);

if ($crop_id <= 0) { $_SESSION['flash_error'] = 'Invalid crop.'; header('Location: ' . url('crops')); exit; }

try {
    $crop = db()->table('rice_crops')->where('id', $crop_id)->where('user_id', $user_id)->get();
    if (!$crop) { $_SESSION['flash_error'] = 'Crop not found.'; header('Location: ' . url('crops')); exit; }

    // Delete child records first to keep DB clean
    db()->table('crop_progress')      ->where('crop_id', $crop_id)->delete();
    db()->table('fertilizer_schedule') ->where('crop_id', $crop_id)->delete();
    db()->table('harvest_records')     ->where('crop_id', $crop_id)->delete();

    // Delete the crop itself
    db()->table('rice_crops')->where('id', $crop_id)->where('user_id', $user_id)->delete();

    $_SESSION['flash_success'] = "Crop \"{$crop['rice_variety']}\" deleted successfully.";
} catch (Exception $e) {
    error_log('Crop delete error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not delete crop. Please try again.';
}

header('Location: ' . url('crops')); exit;