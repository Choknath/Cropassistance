<?php
/**
 * SMART CROP ASSISTANT + FARMLOG
 * File: app/views/farmlog/plots.php
 *
 * Lists all field plots belonging to the logged-in farmer.
 * From here the farmer can: Add / Edit / Delete plots.
 */

// ── Session guard ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) {
    header('Location: ' . url('login')); exit;
}

$user    = $_SESSION['user'];
$user_id = $user['id'];

// ── Flash messages (set by create / edit / delete) ─────────
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Fetch this farmer's plots ──────────────────────────────
try {
    $plots = db()->table('field_plots')
                 ->where('user_id', $user_id)
                 ->order_by('created_at', 'DESC')
                 ->get_all();

    if (!$plots) $plots = [];

} catch (Exception $e) {
    $plots = [];
    error_log('Plots fetch error: ' . $e->getMessage());
}

$total_plots    = count($plots);
$total_hectares = array_sum(array_column($plots, 'area_hectares'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Field Plots — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body     { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3 { font-family: 'Playfair Display', Georgia, serif; }
        .nav-glass {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .fade-up { opacity: 0; animation: fadeUp 0.5s ease forwards; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .plot-card { transition: transform 0.2s, box-shadow 0.2s; }
        .plot-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- ── NAVIGATION ──────────────────────────────────────── -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">

        <!-- Brand -->
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm">
                <span class="text-lg">🌾</span>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900"
                     style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">FarmLog — Field Plots</div>
            </div>
        </a>

        <!-- Nav links -->
        <div class="flex items-center gap-1 flex-wrap">
            <a href="<?= url('dashboard') ?>"
               class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                📊 Dashboard
            </a>
            <a href="<?= url('crops') ?>"
               class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                🌾 My Crops
            </a>
            <a href="<?= url('plots') ?>"
               class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">
                🗺️ Field Plots
            </a>
            <a href="<?= url('crop-assistant') ?>"
               class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                🔍 Analyze
            </a>
            <a href="<?= url('logout') ?>"
               class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">
                Logout
            </a>
        </div>
    </div>
</nav>

<!-- ── PAGE HEADER ────────────────────────────────────── -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <p class="text-green-400 text-sm font-medium mb-1">🏡 <?= htmlspecialchars($user['farm_name']) ?></p>
            <h1 class="text-3xl font-bold mb-1">My Field Plots</h1>
            <p class="text-green-300 text-sm">
                <?= $total_plots ?> plot<?= $total_plots !== 1 ? 's' : '' ?>
                &nbsp;·&nbsp;
                <?= number_format($total_hectares, 2) ?> total hectares
            </p>
        </div>
        <a href="<?= url('plots/create') ?>"
           class="flex items-center gap-2 bg-green-600 hover:bg-green-500
                  text-white font-semibold px-5 py-3 rounded-xl text-sm
                  transition-colors shadow-lg">
            ➕ Add New Plot
        </a>
    </div>
</div>

<!-- ── MAIN CONTENT ───────────────────────────────────── -->
<main class="max-w-6xl mx-auto px-4 py-8">

    <!-- Flash messages -->
    <?php if ($flash_success): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 fade-up">
            <p class="text-sm text-green-700">✅ <?= htmlspecialchars($flash_success) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up">
            <p class="text-sm text-red-700">❌ <?= htmlspecialchars($flash_error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($plots)): ?>
    <!-- ── EMPTY STATE ─────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-14 text-center fade-up">
        <div class="text-6xl mb-4">🗺️</div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">No field plots yet</h2>
        <p class="text-gray-400 text-sm mb-6">
            Add your rice field plots to start tracking crop cycles,
            fertilizer schedules, and harvest records.
        </p>
        <a href="<?= url('plots/create') ?>"
           class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600
                  text-white font-semibold px-6 py-3 rounded-xl text-sm transition-colors">
            ➕ Add Your First Plot
        </a>
    </div>

    <?php else: ?>
    <!-- ── PLOTS GRID ──────────────────────────────────── -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($plots as $plot): ?>
        <div class="plot-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">

            <!-- Card header -->
            <div class="bg-gradient-to-r from-green-700 to-green-600 px-5 py-4">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-xl">
                        🌱
                    </div>
                    <span class="text-xs bg-white/20 text-white px-2.5 py-1 rounded-full font-medium">
                        <?= htmlspecialchars($plot['soil_type']) ?>
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg mt-3 leading-tight">
                    <?= htmlspecialchars($plot['plot_name']) ?>
                </h3>
                <p class="text-green-200 text-xs mt-0.5">
                    📍 <?= htmlspecialchars($plot['location']) ?>
                </p>
            </div>

            <!-- Card body -->
            <div class="p-5">
                <!-- Area -->
                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <span class="text-xs text-gray-400">📐 Area</span>
                    <span class="text-sm font-semibold text-gray-700">
                        <?= number_format($plot['area_hectares'], 2) ?> hectares
                    </span>
                </div>

                <!-- Added date -->
                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <span class="text-xs text-gray-400">📅 Added</span>
                    <span class="text-sm text-gray-600">
                        <?= date('M j, Y', strtotime($plot['created_at'])) ?>
                    </span>
                </div>

                <!-- Notes (if any) -->
                <?php if (!empty($plot['notes'])): ?>
                <div class="mt-3 bg-gray-50 rounded-xl p-3 border border-gray-100">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        📝 <?= htmlspecialchars($plot['notes']) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Action buttons -->
                <div class="flex gap-2 mt-4">
                    <a href="<?= url('plots/edit/' . $plot['id']) ?>"
                       class="flex-1 text-center bg-green-50 hover:bg-green-100
                              text-green-700 font-medium py-2 rounded-xl text-xs
                              border border-green-200 transition-colors">
                        ✏️ Edit
                    </a>
                    <!-- Delete triggers a small confirm dialog -->
                    <button onclick="confirmDelete(<?= $plot['id'] ?>, '<?= htmlspecialchars(addslashes($plot['plot_name'])) ?>')"
                            class="flex-1 text-center bg-red-50 hover:bg-red-100
                                   text-red-600 font-medium py-2 rounded-xl text-xs
                                   border border-red-200 transition-colors">
                        🗑️ Delete
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>

<!-- ── HIDDEN DELETE FORM ──────────────────────────────── -->
<!-- We use a hidden form so the delete is a POST request (safer than GET) -->
<form id="delete-form" action="<?= url('plots/delete') ?>" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="plot_id" id="delete-plot-id">
</form>

<!-- ── CONFIRM MODAL ──────────────────────────────────── -->
<div id="confirm-modal"
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden px-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <div class="text-3xl text-center mb-3">🗑️</div>
        <h3 class="text-lg font-semibold text-gray-800 text-center mb-2">Delete this plot?</h3>
        <p class="text-sm text-gray-500 text-center mb-1">
            You are about to delete:
        </p>
        <p id="modal-plot-name"
           class="text-sm font-semibold text-red-600 text-center mb-4"></p>
        <p class="text-xs text-gray-400 text-center mb-5">
            ⚠️ This cannot be undone.
        </p>
        <div class="flex gap-3">
            <button onclick="closeModal()"
                    class="flex-1 border border-gray-200 text-gray-600 font-medium
                           py-2.5 rounded-xl text-sm hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button onclick="submitDelete()"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium
                           py-2.5 rounded-xl text-sm transition-colors">
                Yes, Delete
            </button>
        </div>
    </div>
</div>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4">
    <div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">
        Smart Crop Assistant — FarmLog Module
    </div>
</footer>

<script>
    // Show confirm modal before deleting
    function confirmDelete(plotId, plotName) {
        document.getElementById('delete-plot-id').value  = plotId;
        document.getElementById('modal-plot-name').textContent = '"' + plotName + '"';
        document.getElementById('confirm-modal').classList.remove('hidden');
    }

    // Close modal without doing anything
    function closeModal() {
        document.getElementById('confirm-modal').classList.add('hidden');
    }

    // Submit the hidden form
    function submitDelete() {
        document.getElementById('delete-form').submit();
    }

    // Close modal if user clicks outside of it
    document.getElementById('confirm-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

</body>
</html>