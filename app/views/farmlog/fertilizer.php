<?php
/**
 * FARMLOG — Fertilizer Schedule
 * File: app/views/farmlog/fertilizer.php
 * Route: GET fertilizer
 * Shows all fertilizer reminders for all active crops.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];

$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

try {
    // Get all fertilizer records joined with crop info
    $all = db()->table('fertilizer_schedule')
               ->where('user_id', $user_id)
               ->order_by('application_date', 'ASC')
               ->get_all();
    if (!$all) $all = [];

    // Get crop names for display
    $crops_raw = db()->table('rice_crops')->where('user_id', $user_id)->get_all();
    $crop_map  = [];
    if ($crops_raw) foreach ($crops_raw as $c) $crop_map[$c['id']] = $c['rice_variety'];

} catch (Exception $e) {
    $all = []; $crop_map = [];
    error_log('Fertilizer fetch error: ' . $e->getMessage());
}

$today    = date('Y-m-d');
$pending  = array_filter($all, fn($f) => !$f['is_done']);
$overdue  = array_filter($all, fn($f) => !$f['is_done'] && $f['application_date'] < $today);
$upcoming = array_filter($all, fn($f) => !$f['is_done'] && $f['application_date'] >= $today);
$done     = array_filter($all, fn($f) => $f['is_done']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fertilizer Schedule — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .fert-row{transition:background .15s;}.fert-row:hover{background:#f8fdf9;}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- NAV -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">🌾</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900" style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">FarmLog — Fertilizer</div>
            </div>
        </a>
        <div class="flex items-center gap-1 flex-wrap">
            <a href="<?= url('dashboard') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 Dashboard</a>
            <a href="<?= url('crops') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🌾 My Crops</a>
            <a href="<?= url('fertilizer') ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">💊 Fertilizer</a>
            <a href="<?= url('harvest') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🧺 Harvest</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-1">💊 Fertilizer Schedule</h1>
        <p class="text-green-300 text-sm">All fertilizer reminders across your active crops</p>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <?php if ($flash_success): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 fade-up"><p class="text-sm text-green-700">✅ <?= htmlspecialchars($flash_success) ?></p></div>
    <?php endif; ?>

    <!-- Summary cards -->
    <div class="grid grid-cols-3 gap-4 fade-up">
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-red-500"><?= count($overdue) ?></div>
            <div class="text-xs text-gray-500 mt-1">🔴 Overdue</div>
        </div>
        <div class="bg-white rounded-2xl border border-yellow-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600"><?= count($upcoming) ?></div>
            <div class="text-xs text-gray-500 mt-1">⏳ Upcoming</div>
        </div>
        <div class="bg-white rounded-2xl border border-green-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-600"><?= count($done) ?></div>
            <div class="text-xs text-gray-500 mt-1">✅ Completed</div>
        </div>
    </div>

    <?php if (empty($all)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center fade-up">
        <div class="text-5xl mb-3">💊</div>
        <h2 class="text-lg font-semibold text-gray-700 mb-2">No fertilizer schedules yet</h2>
        <p class="text-gray-400 text-sm mb-5">Schedules are auto-generated when you add a crop cycle.</p>
        <a href="<?= url('crops/create') ?>" class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-xl text-sm transition-colors">🌾 Add a Crop Cycle</a>
    </div>

    <?php else: ?>

    <!-- Overdue -->
    <?php if (!empty($overdue)): ?>
    <div class="bg-white rounded-2xl border border-red-200 shadow-sm overflow-hidden fade-up">
        <div class="bg-red-600 px-5 py-3 flex items-center gap-2">
            <span class="text-white">🔴</span><span class="text-white font-medium text-sm">Overdue — Apply immediately!</span>
        </div>
        <?php foreach ($overdue as $f): ?>
        <div class="fert-row px-5 py-3.5 flex items-center gap-4 bg-red-50/40 border-b border-red-100 last:border-b-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-sm font-medium text-red-800"><?= htmlspecialchars($f['application_name']) ?></span>
                    <span class="text-xs text-red-500 bg-red-100 px-2 py-0.5 rounded-full"><?= round((new DateTime($f['application_date']))->diff(new DateTime())->days) ?> days overdue</span>
                </div>
                <div class="text-xs text-gray-400 flex flex-wrap gap-x-3">
                    <span>🌾 <?= htmlspecialchars($crop_map[$f['crop_id']] ?? 'Unknown crop') ?></span>
                    <span>💊 <?= htmlspecialchars($f['fertilizer_type']) ?></span>
                    <span>⚖️ <?= $f['amount_kg'] ?> kg/ha</span>
                    <span>📅 Was due <?= date('M j, Y', strtotime($f['application_date'])) ?></span>
                </div>
            </div>
            <form method="POST" action="<?= url('fertilizer/done') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="fertilizer_id" value="<?= $f['id'] ?>">
                <input type="hidden" name="crop_id" value="<?= $f['crop_id'] ?>">
                <button type="submit" class="text-xs bg-green-700 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">✓ Mark Done</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Upcoming -->
    <?php if (!empty($upcoming)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
        <div class="bg-green-700 px-5 py-3 flex items-center gap-2">
            <span class="text-white">⏳</span><span class="text-white font-medium text-sm">Upcoming Applications</span>
        </div>
        <div class="divide-y divide-gray-50">
        <?php foreach ($upcoming as $f):
            $days_until = (int)(new DateTime())->diff(new DateTime($f['application_date']))->days;
            $urgency = $days_until <= 3 ? 'text-orange-600' : ($days_until <= 7 ? 'text-yellow-600' : 'text-gray-400');
        ?>
        <div class="fert-row px-5 py-3.5 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-100 border border-green-200 rounded-xl flex items-center justify-center text-lg flex-shrink-0">💊</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($f['application_name']) ?></span>
                    <span class="text-xs <?= $urgency ?>"><?= $days_until === 0 ? 'Today!' : "in {$days_until} days" ?></span>
                </div>
                <div class="text-xs text-gray-400 flex flex-wrap gap-x-3">
                    <span>🌾 <?= htmlspecialchars($crop_map[$f['crop_id']] ?? '—') ?></span>
                    <span>💊 <?= htmlspecialchars($f['fertilizer_type']) ?></span>
                    <span>⚖️ <?= $f['amount_kg'] ?> kg/ha</span>
                    <span>📅 <?= date('M j, Y', strtotime($f['application_date'])) ?></span>
                </div>
            </div>
            <form method="POST" action="<?= url('fertilizer/done') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="fertilizer_id" value="<?= $f['id'] ?>">
                <input type="hidden" name="crop_id" value="<?= $f['crop_id'] ?>">
                <button type="submit" class="text-xs bg-green-700 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors">✓ Done</button>
            </form>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Completed -->
    <?php if (!empty($done)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
        <div class="bg-gray-100 px-5 py-3">
            <span class="text-gray-600 font-medium text-sm">✅ Completed (<?= count($done) ?>)</span>
        </div>
        <div class="divide-y divide-gray-50">
        <?php foreach ($done as $f): ?>
        <div class="fert-row px-5 py-3 flex items-center gap-4 opacity-60">
            <div class="flex-1 min-w-0">
                <span class="text-sm text-gray-500 line-through"><?= htmlspecialchars($f['application_name']) ?></span>
                <div class="text-xs text-gray-400 flex flex-wrap gap-x-3 mt-0.5">
                    <span>🌾 <?= htmlspecialchars($crop_map[$f['crop_id']] ?? '—') ?></span>
                    <span>💊 <?= htmlspecialchars($f['fertilizer_type']) ?></span>
                    <span>⚖️ <?= $f['amount_kg'] ?> kg/ha</span>
                    <span>📅 <?= date('M j, Y', strtotime($f['application_date'])) ?></span>
                </div>
            </div>
            <span class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded-full">✓ Done</span>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>
</body>
</html>