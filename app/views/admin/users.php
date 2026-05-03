<?php
/**
 * SMART CROP ASSISTANT — Admin: User Management
 * File: app/views/admin/users.php
 * Route: GET admin/users
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if (($_SESSION['user']['role'] ?? '') !== 'admin') { header('Location: ' . url('dashboard')); exit; }

$admin = $_SESSION['user'];
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Handle role toggle (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_role'])) {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $new_role   = trim($_POST['new_role'] ?? '');
    if ($target_id > 0 && $target_id !== (int)$admin['id'] && in_array($new_role, ['farmer','admin'])) {
        try {
            db()->table('users')->where('id', $target_id)->update(['role' => $new_role]);
            $_SESSION['flash_success'] = 'User role updated successfully.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Could not update role.';
            error_log('Admin role update: ' . $e->getMessage());
        }
    }
    header('Location: ' . url('admin/users')); exit;
}

try {
    $users = db()->table('users')->order_by('created_at', 'DESC')->get_all();
    if (!$users) $users = [];

    // Get scan count per user
    $scan_counts = [];
    $scans_raw = db()->table('scans')->get_all();
    if ($scans_raw) foreach ($scans_raw as $s) {
        $scan_counts[$s['user_id']] = ($scan_counts[$s['user_id']] ?? 0) + 1;
    }

    // Get crop count per user
    $crop_counts = [];
    $crops_raw = db()->table('rice_crops')->get_all();
    if ($crops_raw) foreach ($crops_raw as $c) {
        $crop_counts[$c['user_id']] = ($crop_counts[$c['user_id']] ?? 0) + 1;
    }

} catch (Exception $e) {
    $users = [];
    error_log('Admin users error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — Admin</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .user-row{transition:background .15s;}.user-row:hover{background:#faf5ff;}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<nav class="nav-glass sticky top-0 z-50 border-b border-purple-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('admin') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-purple-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">👑</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-purple-900" style="font-family:'Playfair Display',serif">Admin Panel</div>
                <div class="text-xs text-gray-400">User Management</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('admin') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">👑 Overview</a>
            <a href="<?= url('admin/users') ?>" class="px-3 py-2 rounded-lg bg-purple-50 text-purple-700 font-medium text-sm border border-purple-200">👥 Users</a>
            <a href="<?= url('admin/crops') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🌾 All Crops</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<div class="bg-purple-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <p class="text-purple-300 text-sm font-medium mb-1">👑 Admin Panel</p>
        <h1 class="text-3xl font-bold mb-1">User Management</h1>
        <p class="text-purple-300 text-sm"><?= count($users) ?> registered farmer<?= count($users) !== 1 ? 's' : '' ?> on the platform</p>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8">

    <?php if ($flash_success): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 fade-up"><p class="text-sm text-green-700">✅ <?= htmlspecialchars($flash_success) ?></p></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up"><p class="text-sm text-red-700">❌ <?= htmlspecialchars($flash_error) ?></p></div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center fade-up">
        <div class="text-5xl mb-3">👥</div>
        <p class="text-gray-400 text-sm">No users registered yet.</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
        <div class="bg-purple-700 px-6 py-4">
            <span class="text-white font-medium text-sm">👥 All Registered Farmers (<?= count($users) ?>)</span>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($users as $u):
                $is_me   = $u['id'] == $admin['id'];
                $role    = $u['role'] ?? 'farmer';
                $scans   = $scan_counts[$u['id']] ?? 0;
                $crops   = $crop_counts[$u['id']] ?? 0;
            ?>
            <div class="user-row px-6 py-4 flex items-center gap-4">
                <!-- Avatar -->
                <div class="w-11 h-11 <?= $role==='admin' ? 'bg-purple-100 border-purple-200' : 'bg-green-100 border-green-200' ?> border rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                    <?= $role === 'admin' ? '👑' : '👨‍🌾' ?>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($u['full_name']) ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $role==='admin' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' ?>"><?= $role ?></span>
                        <?php if ($is_me): ?><span class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">You</span><?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-x-3 text-xs text-gray-400">
                        <span>📧 <?= htmlspecialchars($u['email']) ?></span>
                        <span>🏡 <?= htmlspecialchars($u['farm_name'] ?? '—') ?></span>
                        <span>📍 <?= htmlspecialchars($u['location'] ?? '—') ?></span>
                    </div>
                    <div class="flex flex-wrap gap-x-3 text-xs text-gray-400 mt-0.5">
                        <span>🔬 <?= $scans ?> scans</span>
                        <span>🌾 <?= $crops ?> crops</span>
                        <span>📅 Joined <?= date('M j, Y', strtotime($u['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Role toggle (can't change own role) -->
                <?php if (!$is_me): ?>
                <form method="POST" action="<?= url('admin/users') ?>" class="flex-shrink-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                    <input type="hidden" name="new_role"   value="<?= $role === 'admin' ? 'farmer' : 'admin' ?>">
                    <input type="hidden" name="toggle_role" value="1">
                    <button type="submit"
                            onclick="return confirm('<?= $role === 'admin' ? 'Remove admin from' : 'Make admin:' ?> <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')"
                            class="text-xs <?= $role === 'admin' ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100' : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100' ?> border font-medium px-3 py-1.5 rounded-lg transition-colors">
                        <?= $role === 'admin' ? '🔽 Remove Admin' : '⬆️ Make Admin' ?>
                    </button>
                </form>
                <?php else: ?>
                <span class="text-xs text-gray-300 flex-shrink-0">—</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — Admin Panel</div></footer>
</body>
</html>