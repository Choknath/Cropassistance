<?php
/**
 * FARMLOG — My Crops
 * File: app/views/farmlog/crops.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

try {
    $crops = db()->table('rice_crops')
                 ->where('user_id', $user_id)
                 ->order_by('created_at', 'DESC')
                 ->get_all();
    if (!$crops) $crops = [];

    $plots = db()->table('field_plots')
                 ->where('user_id', $user_id)
                 ->get_all();
    if (!$plots) $plots = [];

    $plot_map = [];
    foreach ($plots as $p) $plot_map[$p['id']] = $p['plot_name'];

} catch (Exception $e) {
    $crops = $plots = $plot_map = [];
    error_log('Crops list error: ' . $e->getMessage());
}

$total     = count($crops);
$active    = count(array_filter($crops, fn($c) => $c['status'] === 'active'));
$harvested = count(array_filter($crops, fn($c) => $c['status'] === 'harvested'));

function cropBadge($status) {
    return match($status) {
        'active'    => ['bg-green-100 text-green-700 border-green-200',  '🌱 Active'],
        'harvested' => ['bg-yellow-50 text-yellow-700 border-yellow-200','🌾 Harvested'],
        'failed'    => ['bg-red-50 text-red-700 border-red-200',         '❌ Failed'],
        default     => ['bg-gray-100 text-gray-600 border-gray-200',     '—'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Crops — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .crop-row{transition:background .15s;}.crop-row:hover{background:#f8fdf9;}
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
                <div class="text-xs text-gray-400">FarmLog — My Crops</div>
            </div>
        </a>
        <div class="flex items-center gap-1 flex-wrap">
            <a href="<?= url('dashboard') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 Dashboard</a>
            <a href="<?= url('crops') ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">🌾 My Crops</a>
            <a href="<?= url('plots') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🗺️ Plots</a>
            <a href="<?= url('fertilizer') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">💊 Fertilizer</a>
            <a href="<?= url('harvest') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🧺 Harvest</a>
            <a href="<?= url('crop-assistant') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🔍 Analyze</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <p class="text-green-400 text-sm font-medium mb-1">🏡 <?= htmlspecialchars($user['farm_name']) ?></p>
            <h1 class="text-3xl font-bold mb-1">My Rice Crops</h1>
            <p class="text-green-300 text-sm"><?= $total ?> total &nbsp;·&nbsp; <span class="text-green-400"><?= $active ?> active</span> &nbsp;·&nbsp; <?= $harvested ?> harvested</p>
        </div>
        <a href="<?= url('crops/create') ?>" class="flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white font-semibold px-5 py-3 rounded-xl text-sm transition-colors shadow-lg">➕ Add Crop Cycle</a>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8">

    <?php if ($flash_success): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 fade-up"><p class="text-sm text-green-700">✅ <?= htmlspecialchars($flash_success) ?></p></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up"><p class="text-sm text-red-700">❌ <?= htmlspecialchars($flash_error) ?></p></div>
    <?php endif; ?>

    <?php if (empty($crops)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-14 text-center fade-up">
        <div class="text-6xl mb-4">🌱</div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">No crop cycles yet</h2>
        <?php if (empty($plots)): ?>
            <p class="text-yellow-600 text-xs bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 mb-5 inline-block">⚠️ Add a Field Plot first before adding a crop cycle.</p><br>
            <a href="<?= url('plots/create') ?>" class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-xl text-sm transition-colors">🗺️ Add a Field Plot First</a>
        <?php else: ?>
            <p class="text-gray-400 text-sm mb-6">Record each planting season as a crop cycle.</p>
            <a href="<?= url('crops/create') ?>" class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-xl text-sm transition-colors">➕ Add Your First Crop Cycle</a>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
        <div class="bg-green-700 px-6 py-4 flex items-center justify-between">
            <span class="text-white font-medium text-sm">🌾 <?= $total ?> Crop Cycle<?= $total !== 1 ? 's' : '' ?></span>
            <a href="<?= url('crops/create') ?>" class="bg-white/20 hover:bg-white/30 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-colors">+ New Cycle</a>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($crops as $crop):
                [$badge, $label] = cropBadge($crop['status']);
                $plot_name = $plot_map[$crop['plot_id']] ?? '—';
                $days_left = '';
                if ($crop['expected_harvest'] && $crop['status'] === 'active') {
                    $now  = new DateTime();
                    $exp  = new DateTime($crop['expected_harvest']);
                    $diff = $now->diff($exp);
                    $days_left = $diff->invert ? 'Overdue!' : $diff->days . ' days left';
                }
            ?>
            <div class="crop-row px-6 py-4 flex items-center gap-4">
                <div class="w-11 h-11 bg-green-100 border border-green-200 rounded-xl flex items-center justify-center text-xl flex-shrink-0">🌾</div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($crop['rice_variety']) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium border <?= $badge ?>"><?= $label ?></span>
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400">
                        <span>🗺️ <?= htmlspecialchars($plot_name) ?></span>
                        <span>📅 Planted <?= date('M j, Y', strtotime($crop['planting_date'])) ?></span>
                        <?php if ($crop['expected_harvest']): ?><span>🌾 Harvest <?= date('M j, Y', strtotime($crop['expected_harvest'])) ?></span><?php endif; ?>
                        <?php if ($days_left): ?><span class="<?= str_contains($days_left,'Overdue') ? 'text-red-500 font-semibold' : 'text-green-600' ?>">⏳ <?= $days_left ?></span><?php endif; ?>
                    </div>
                    <?php if ($crop['notes']): ?><p class="text-xs text-gray-400 mt-1 truncate">📝 <?= htmlspecialchars($crop['notes']) ?></p><?php endif; ?>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="<?= url('crops/' . $crop['id']) ?>" class="bg-green-700 hover:bg-green-600 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition-colors">View →</a>
                    <a href="<?= url('crops/edit/' . $crop['id']) ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors">✏️</a>
                    <button onclick="confirmDelete(<?= $crop['id'] ?>, '<?= htmlspecialchars(addslashes($crop['rice_variety'])) ?>')" class="bg-red-50 hover:bg-red-100 text-red-500 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors border border-red-200">🗑️</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<form id="delete-form" action="<?= url('crops/delete') ?>" method="POST" style="display:none;">
    <?= csrf_field() ?><input type="hidden" name="crop_id" id="delete-crop-id">
</form>
<div id="confirm-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden px-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <div class="text-3xl text-center mb-3">🗑️</div>
        <h3 class="text-lg font-semibold text-gray-800 text-center mb-2">Delete this crop cycle?</h3>
        <p id="modal-crop-name" class="text-sm font-semibold text-red-600 text-center mb-2"></p>
        <p class="text-xs text-gray-400 text-center mb-5">⚠️ All progress logs for this crop will also be removed.</p>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 border border-gray-200 text-gray-600 font-medium py-2.5 rounded-xl text-sm hover:bg-gray-50">Cancel</button>
            <button onclick="submitDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 rounded-xl text-sm">Yes, Delete</button>
        </div>
    </div>
</div>
<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>
<script>
function confirmDelete(id,name){document.getElementById('delete-crop-id').value=id;document.getElementById('modal-crop-name').textContent='"'+name+'"';document.getElementById('confirm-modal').classList.remove('hidden');}
function closeModal(){document.getElementById('confirm-modal').classList.add('hidden');}
function submitDelete(){document.getElementById('delete-form').submit();}
document.getElementById('confirm-modal').addEventListener('click',function(e){if(e.target===this)closeModal();});
</script>
</body>
</html>