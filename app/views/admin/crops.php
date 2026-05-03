<?php
/**
 * SMART CROP ASSISTANT — Admin: All Crops
 * File: app/views/admin/crops.php
 * Route: GET admin/crops
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if (($_SESSION['user']['role'] ?? '') !== 'admin') { header('Location: ' . url('dashboard')); exit; }

$admin = $_SESSION['user'];

try {
    $crops = db()->table('rice_crops')->order_by('created_at','DESC')->get_all();
    if (!$crops) $crops = [];

    $users_raw = db()->table('users')->get_all();
    $user_map  = [];
    if ($users_raw) foreach ($users_raw as $u) $user_map[$u['id']] = $u['full_name'];

    $plots_raw = db()->table('field_plots')->get_all();
    $plot_map  = [];
    if ($plots_raw) foreach ($plots_raw as $p) $plot_map[$p['id']] = $p['plot_name'];

} catch (Exception $e) {
    $crops = $user_map = $plot_map = [];
    error_log('Admin crops error: ' . $e->getMessage());
}

$total     = count($crops);
$active    = count(array_filter($crops, fn($c) => $c['status'] === 'active'));
$harvested = count(array_filter($crops, fn($c) => $c['status'] === 'harvested'));
$failed    = count(array_filter($crops, fn($c) => $c['status'] === 'failed'));

function adminCropBadge($status) {
    return match($status) {
        'active'    => 'bg-green-100 text-green-700 border-green-200',
        'harvested' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'failed'    => 'bg-red-50 text-red-600 border-red-200',
        default     => 'bg-gray-100 text-gray-600 border-gray-200',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Crops — Admin</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .crop-row{transition:background .15s;}.crop-row:hover{background:#faf5ff;}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<nav class="nav-glass sticky top-0 z-50 border-b border-purple-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('admin') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-purple-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">👑</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-purple-900" style="font-family:'Playfair Display',serif">Admin Panel</div>
                <div class="text-xs text-gray-400">All Crops</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('admin') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">👑 Overview</a>
            <a href="<?= url('admin/users') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">👥 Users</a>
            <a href="<?= url('admin/crops') ?>" class="px-3 py-2 rounded-lg bg-purple-50 text-purple-700 font-medium text-sm border border-purple-200">🌾 All Crops</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<div class="bg-purple-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <p class="text-purple-300 text-sm font-medium mb-1">👑 Admin Panel</p>
        <h1 class="text-3xl font-bold mb-1">All Crop Cycles</h1>
        <p class="text-purple-300 text-sm"><?= $total ?> total &nbsp;·&nbsp; <span class="text-green-300"><?= $active ?> active</span> &nbsp;·&nbsp; <?= $harvested ?> harvested &nbsp;·&nbsp; <?= $failed ?> failed</p>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8">

    <!-- Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 fade-up">
        <?php foreach ([['🌾',$total,'Total','border-gray-100'],['🌱',$active,'Active','border-green-100'],['🧺',$harvested,'Harvested','border-yellow-100'],['❌',$failed,'Failed','border-red-100']] as [$ic,$v,$l,$b]): ?>
        <div class="bg-white rounded-2xl border <?= $b ?> shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-gray-800"><?= $v ?></div>
            <div class="text-xs text-gray-500 mt-1"><?= $ic ?> <?= $l ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($crops)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center fade-up">
        <div class="text-5xl mb-3">🌾</div>
        <p class="text-gray-400 text-sm">No crop cycles recorded by any farmer yet.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
        <div class="bg-purple-700 px-6 py-4">
            <span class="text-white font-medium text-sm">🌾 All Crop Cycles — Platform-wide</span>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($crops as $crop):
                $badge  = adminCropBadge($crop['status']);
                $farmer = $user_map[$crop['user_id']] ?? 'Unknown';
                $plot   = $plot_map[$crop['plot_id']] ?? '—';

                $days_planted = 0;
                if ($crop['planting_date']) {
                    $days_planted = (int)(new DateTime())->diff(new DateTime($crop['planting_date']))->days;
                }
            ?>
            <div class="crop-row px-6 py-4 flex items-center gap-4">
                <div class="w-11 h-11 bg-purple-100 border border-purple-200 rounded-xl flex items-center justify-center text-xl flex-shrink-0">🌾</div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($crop['rice_variety']) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium border <?= $badge ?>"><?= ucfirst($crop['status']) ?></span>
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400">
                        <span>👨‍🌾 <?= htmlspecialchars($farmer) ?></span>
                        <span>🗺️ <?= htmlspecialchars($plot) ?></span>
                        <span>📅 Planted <?= date('M j, Y', strtotime($crop['planting_date'])) ?></span>
                        <span>📆 Day <?= $days_planted ?></span>
                        <?php if ($crop['expected_harvest']): ?><span>🌾 Harvest <?= date('M j, Y', strtotime($crop['expected_harvest'])) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="text-xs text-gray-400 flex-shrink-0 text-right">
                    <div>ID #<?= $crop['id'] ?></div>
                    <div class="mt-0.5"><?= date('M j', strtotime($crop['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — Admin Panel</div></footer>
</body>
</html>