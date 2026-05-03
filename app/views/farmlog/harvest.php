<?php
/**
 * FARMLOG — Harvest Records
 * File: app/views/farmlog/harvest.php
 * Route: GET harvest
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];

$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

try {
    $harvests = db()->table('harvest_records')
                    ->where('user_id', $user_id)
                    ->order_by('harvest_date', 'DESC')
                    ->get_all();
    if (!$harvests) $harvests = [];

    $crops_raw = db()->table('rice_crops')->where('user_id', $user_id)->get_all();
    $crop_map  = [];
    if ($crops_raw) foreach ($crops_raw as $c) $crop_map[$c['id']] = $c['rice_variety'];

} catch (Exception $e) {
    $harvests = []; $crop_map = [];
    error_log('Harvest list error: ' . $e->getMessage());
}

$total_kg     = array_sum(array_column($harvests, 'quantity_kg'));
$total_cavans = array_sum(array_column($harvests, 'quantity_cavans'));
$total_income = array_sum(array_column($harvests, 'total_income'));

$grade_badge = fn($g) => match($g) {
    'premium'  => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    'standard' => 'bg-blue-50 text-blue-700 border-blue-200',
    'poor'     => 'bg-red-50 text-red-600 border-red-200',
    default    => 'bg-gray-100 text-gray-600 border-gray-200',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvest Records — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .harvest-row{transition:background .15s;}.harvest-row:hover{background:#fefce8;}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">🌾</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900" style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">FarmLog — Harvest Records</div>
            </div>
        </a>
        <div class="flex items-center gap-1 flex-wrap">
            <a href="<?= url('dashboard') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 Dashboard</a>
            <a href="<?= url('crops') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🌾 My Crops</a>
            <a href="<?= url('fertilizer') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">💊 Fertilizer</a>
            <a href="<?= url('harvest') ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">🧺 Harvest</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-1">🧺 Harvest Records</h1>
        <p class="text-green-300 text-sm">All recorded yields from your farm</p>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <?php if ($flash_success): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 fade-up"><p class="text-sm text-green-700">✅ <?= htmlspecialchars($flash_success) ?></p></div>
    <?php endif; ?>

    <!-- Summary -->
    <?php if (!empty($harvests)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 fade-up">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= count($harvests) ?></div>
            <div class="text-xs text-gray-500 mt-1">🧺 Total Harvests</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?= number_format($total_kg, 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">⚖️ Total kg</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600"><?= number_format($total_cavans, 1) ?></div>
            <div class="text-xs text-gray-500 mt-1">🌾 Total cavans</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-700">₱<?= number_format($total_income, 0) ?></div>
            <div class="text-xs text-gray-500 mt-1">💰 Total Income</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($harvests)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center fade-up">
        <div class="text-5xl mb-3">🧺</div>
        <h2 class="text-lg font-semibold text-gray-700 mb-2">No harvest records yet</h2>
        <p class="text-gray-400 text-sm mb-5">Log a harvest from any active crop cycle's detail page.</p>
        <a href="<?= url('crops') ?>" class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-xl text-sm transition-colors">🌾 Go to My Crops</a>
    </div>

    <?php else: ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
        <div class="bg-yellow-200 px-5 py-4">
            <span class="text-yellow-800 font-medium text-sm">🧺 <?= count($harvests) ?> Harvest Record<?= count($harvests) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($harvests as $h): ?>
            <div class="harvest-row px-5 py-4 flex items-center gap-4">
                <div class="w-11 h-11 bg-yellow-100 border border-yellow-200 rounded-xl flex items-center justify-center text-xl flex-shrink-0">🧺</div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($crop_map[$h['crop_id']] ?? 'Unknown crop') ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium border <?= $grade_badge($h['quality_grade']) ?>"><?= ucfirst($h['quality_grade']) ?></span>
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400">
                        <span>📅 <?= date('M j, Y', strtotime($h['harvest_date'])) ?></span>
                        <span>⚖️ <?= number_format($h['quantity_kg'], 2) ?> kg</span>
                        <span>🌾 <?= number_format($h['quantity_cavans'], 2) ?> cavans</span>
                        <?php if ($h['selling_price']): ?><span>💲 ₱<?= number_format($h['selling_price'], 2) ?>/cavan</span><?php endif; ?>
                        <?php if ($h['total_income']): ?><span class="text-green-600 font-medium">💰 ₱<?= number_format($h['total_income'], 2) ?></span><?php endif; ?>
                    </div>
                    <?php if ($h['notes']): ?><p class="text-xs text-gray-400 mt-0.5 truncate">📝 <?= htmlspecialchars($h['notes']) ?></p><?php endif; ?>
                </div>
                <a href="<?= url('crops/' . $h['crop_id']) ?>" class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg transition-colors flex-shrink-0">View Crop →</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>
</body>
</html>