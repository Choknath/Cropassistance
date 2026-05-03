<?php
/**
 * FARMLOG — Log Growth Stage
 * File: app/views/farmlog/progress_store.php
 * Route: POST progress/store
 * No HTML — saves progress entry then redirects to crop detail.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('crops')); exit; }

$user_id      = $_SESSION['user']['id'];
$crop_id      = (int)($_POST['crop_id']      ?? 0);
$stage        = trim($_POST['growth_stage']  ?? '');
$date_rec     = trim($_POST['date_recorded'] ?? '');
$notes        = trim($_POST['notes']         ?? '');

$valid_stages = ['land_preparation','seedling','transplanting','tillering','panicle_initiation','flowering','ripening','harvested'];

if ($crop_id <= 0 || empty($stage) || !in_array($stage, $valid_stages)) {
    $_SESSION['flash_error'] = 'Invalid data. Please try again.';
    header('Location: ' . url('crops/' . $crop_id)); exit;
}

if (empty($date_rec)) $date_rec = date('Y-m-d');

try {
    // Verify crop belongs to this user
    $crop = db()->table('rice_crops')->where('id', $crop_id)->where('user_id', $user_id)->get();
    if (!$crop) { $_SESSION['flash_error'] = 'Crop not found.'; header('Location: ' . url('crops')); exit; }

    db()->table('crop_progress')->insert([
        'crop_id'       => $crop_id,
        'user_id'       => $user_id,
        'growth_stage'  => $stage,
        'date_recorded' => $date_rec,
        'notes'         => $notes ?: null,
    ]);

    // If stage is 'harvested', mark crop status as harvested
    if ($stage === 'harvested') {
        db()->table('rice_crops')
            ->where('id', $crop_id)
            ->update(['status' => 'harvested', 'actual_harvest' => $date_rec]);
    }

    $_SESSION['flash_success'] = 'Growth stage logged successfully!';
} catch (Exception $e) {
    error_log('Progress store error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Could not save stage. Please try again.';
}

header('Location: ' . url('crops/' . $crop_id)); exit;