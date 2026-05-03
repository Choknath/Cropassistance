<?php
/**
 * SMART CROP ASSISTANT + FARMLOG
 * File: app/views/farmlog/plots_edit.php
 *
 * GET  → loads existing plot data into the form
 * POST → validates and updates the record in DB
 *
 * Route: GET  plots/edit/{id}
 *        POST plots/update/{id}
 *
 * SECURITY: We always check user_id so a farmer
 * cannot edit another farmer's plots.
 */

// ── Session guard ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) {
    header('Location: ' . url('login')); exit;
}

$user    = $_SESSION['user'];
$user_id = $user['id'];

// ── Get the plot ID from the URL ───────────────────────────
// LavaLite passes route params in $params array
$plot_id = (int)($params['id'] ?? 0);

if ($plot_id <= 0) {
    header('Location: ' . url('plots')); exit;
}

// ── Load the existing plot from DB ────────────────────────
$plot   = null;
$errors = [];

try {
    $plot = db()->table('field_plots')
                ->where('id', $plot_id)
                ->where('user_id', $user_id) // ← SECURITY: own plots only
                ->get();

} catch (Exception $e) {
    error_log('Plot edit fetch error: ' . $e->getMessage());
}

// Plot not found or doesn't belong to this user → redirect
if (!$plot) {
    $_SESSION['flash_error'] = 'Plot not found or access denied.';
    header('Location: ' . url('plots')); exit;
}

// ── Handle POST (update submit) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plot_name = trim($_POST['plot_name']    ?? '');
    $area      = trim($_POST['area_hectares']?? '');
    $location  = trim($_POST['location']     ?? '');
    $soil_type = trim($_POST['soil_type']    ?? 'Clay Loam');
    $notes     = trim($_POST['notes']        ?? '');

    // Update $plot array so form repopulates on error
    $plot = array_merge($plot, [
        'plot_name'     => $plot_name,
        'area_hectares' => $area,
        'location'      => $location,
        'soil_type'     => $soil_type,
        'notes'         => $notes,
    ]);

    // ── Validation ────────────────────────────────────────
    if (empty($plot_name)) {
        $errors[] = 'Plot name is required.';
    } elseif (strlen($plot_name) > 100) {
        $errors[] = 'Plot name must be 100 characters or less.';
    }

    if (empty($area) || !is_numeric($area) || (float)$area <= 0) {
        $errors[] = 'Area must be a positive number.';
    }

    if (empty($location)) {
        $errors[] = 'Location is required.';
    }

    // ── Update database ───────────────────────────────────
    if (empty($errors)) {
        try {
            db()->table('field_plots')
                ->where('id', $plot_id)
                ->where('user_id', $user_id)
                ->update([
                    'plot_name'     => $plot_name,
                    'area_hectares' => (float)$area,
                    'location'      => $location,
                    'soil_type'     => $soil_type,
                    'notes'         => $notes ?: null,
                ]);

            $_SESSION['flash_success'] = "Plot \"{$plot_name}\" was updated successfully!";
            header('Location: ' . url('plots'));
            exit;

        } catch (Exception $e) {
            $errors[] = 'Could not update the plot. Please try again.';
            error_log('Plot update error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plot — <?= htmlspecialchars($plot['plot_name']) ?></title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body     { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3 { font-family: 'Playfair Display', Georgia, serif; }
        .nav-glass { background: rgba(255,255,255,0.88); backdrop-filter: blur(14px); }
        .fade-up { animation: fadeUp 0.5s ease forwards; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
            border-color: #4ade80 !important;
        }
        .btn-primary { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); transition: all 0.2s ease; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(22,163,74,0.35); }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- ── NAVIGATION ──────────────────────────────────────── -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm">
                <span class="text-lg">🌾</span>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900"
                     style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">FarmLog — Edit Plot</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('dashboard') ?>"
               class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                📊 Dashboard
            </a>
            <a href="<?= url('plots') ?>"
               class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">
                🗺️ Field Plots
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
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-2 text-green-400 text-xs mb-3">
            <a href="<?= url('plots') ?>" class="hover:text-white transition-colors">🗺️ Field Plots</a>
            <span>›</span>
            <span class="text-green-200">Edit Plot</span>
        </div>
        <h1 class="text-3xl font-bold mb-1">Edit Plot</h1>
        <p class="text-green-300 text-sm">
            Updating: <strong class="text-white"><?= htmlspecialchars($plot['plot_name']) ?></strong>
        </p>
    </div>
</div>

<!-- ── MAIN CONTENT ───────────────────────────────────── -->
<main class="max-w-2xl mx-auto px-4 py-8">

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up">
            <p class="text-sm font-semibold text-red-700 mb-2">⚠️ Please fix the following:</p>
            <?php foreach ($errors as $e): ?>
                <p class="text-sm text-red-600">· <?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Edit form card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-up">

        <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex items-center gap-3">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center"><span>✏️</span></div>
            <div>
                <h2 class="text-white font-semibold text-base">Edit Plot Details</h2>
                <p class="text-green-200 text-xs">Make your changes below and save</p>
            </div>
        </div>

        <div class="p-7">
            <!-- NOTE: action points to plots/update/{id} -->
            <form method="POST" action="<?= url('plots/update/' . $plot_id) ?>">
                <?= csrf_field() ?>

                <!-- Plot Name -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        🏷️ Plot Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text"
                           name="plot_name"
                           value="<?= htmlspecialchars($plot['plot_name']) ?>"
                           placeholder="e.g. North Field"
                           maxlength="100"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Area + Soil Type -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            📐 Area (hectares) <span class="text-red-400">*</span>
                        </label>
                        <input type="number"
                               name="area_hectares"
                               value="<?= htmlspecialchars($plot['area_hectares']) ?>"
                               step="0.01" min="0.01" max="9999.99"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3
                                      text-sm bg-gray-50 focus:bg-white transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            🌍 Soil Type
                        </label>
                        <select name="soil_type"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3
                                       text-sm bg-gray-50 focus:bg-white transition-colors cursor-pointer">
                            <?php
                            $soil_options  = ['Clay Loam','Clay','Sandy Loam','Sandy','Silt Loam','Silty Clay','Loam'];
                            $current_soil  = $plot['soil_type'];
                            foreach ($soil_options as $s):
                            ?>
                            <option value="<?= $s ?>" <?= $current_soil === $s ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Location -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        📍 Location / Barangay <span class="text-red-400">*</span>
                    </label>
                    <input type="text"
                           name="location"
                           value="<?= htmlspecialchars($plot['location']) ?>"
                           placeholder="e.g. Brgy. Sta. Cruz, Legazpi City"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Notes -->
                <div class="mb-7">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        📝 Notes <span class="text-gray-400 font-normal text-xs">(optional)</span>
                    </label>
                    <textarea name="notes"
                              rows="3"
                              placeholder="Any notes about this plot..."
                              class="w-full border border-gray-200 rounded-xl px-4 py-3
                                     text-sm bg-gray-50 focus:bg-white transition-colors resize-none"><?= htmlspecialchars($plot['notes'] ?? '') ?></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <a href="<?= url('plots') ?>"
                       class="flex-1 text-center border border-gray-200 text-gray-600
                              font-medium py-3 rounded-xl text-sm hover:bg-gray-50 transition-colors">
                        ← Cancel
                    </a>
                    <button type="submit"
                            class="btn-primary flex-1 text-white font-semibold py-3 rounded-xl text-sm">
                        💾 Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Plot info badge -->
    <div class="mt-4 text-center text-xs text-gray-400">
        Plot ID #<?= $plot_id ?> &nbsp;·&nbsp;
        Created <?= date('M j, Y', strtotime($plot['created_at'])) ?>
    </div>

</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4">
    <div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">
        Smart Crop Assistant — FarmLog Module
    </div>
</footer>

</body>
</html>