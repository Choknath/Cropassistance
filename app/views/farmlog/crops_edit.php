<?php
/**
 * FARMLOG — Edit Crop Cycle
 * File: app/views/farmlog/crops_edit.php
 * Route: GET crops/edit/{id}  |  POST crops/update/{id}
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];
$crop_id = (int)($params['id'] ?? 0);
if ($crop_id <= 0) { header('Location: ' . url('crops')); exit; }

$errors = [];

try {
    $crop = db()->table('rice_crops')
                ->where('id', $crop_id)
                ->where('user_id', $user_id)
                ->get();
    if (!$crop) { $_SESSION['flash_error'] = 'Crop not found.'; header('Location: ' . url('crops')); exit; }

    $plots = db()->table('field_plots')->where('user_id', $user_id)->get_all();
    if (!$plots) $plots = [];

} catch (Exception $e) {
    header('Location: ' . url('crops')); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $variety        = trim($_POST['rice_variety']    ?? '');
    $plot_id        = (int)($_POST['plot_id']        ?? 0);
    $planting_date  = trim($_POST['planting_date']   ?? '');
    $exp_harvest    = trim($_POST['expected_harvest']?? '');
    $actual_harvest = trim($_POST['actual_harvest']  ?? '');
    $status         = trim($_POST['status']          ?? 'active');
    $notes          = trim($_POST['notes']           ?? '');

    $crop = array_merge($crop, compact('variety','plot_id','planting_date','notes'));
    $crop['rice_variety']     = $variety;
    $crop['expected_harvest'] = $exp_harvest;
    $crop['actual_harvest']   = $actual_harvest;
    $crop['status']           = $status;

    if (empty($variety))      $errors[] = 'Rice variety is required.';
    if (empty($planting_date))$errors[] = 'Planting date is required.';

    if (empty($errors)) {
        try {
            db()->table('rice_crops')
                ->where('id', $crop_id)
                ->where('user_id', $user_id)
                ->update([
                    'rice_variety'     => $variety,
                    'plot_id'          => $plot_id ?: null,
                    'planting_date'    => $planting_date,
                    'expected_harvest' => $exp_harvest   ?: null,
                    'actual_harvest'   => $actual_harvest?: null,
                    'status'           => $status,
                    'notes'            => $notes ?: null,
                ]);
            $_SESSION['flash_success'] = "Crop \"{$variety}\" updated successfully!";
            header('Location: ' . url('crops/' . $crop_id)); exit;
        } catch (Exception $e) {
            $errors[] = 'Could not update. Please try again.';
            error_log('Crop update error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Crop — <?= htmlspecialchars($crop['rice_variety']) ?></title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        input:focus,select:focus,textarea:focus{outline:none;box-shadow:0 0 0 3px rgba(34,197,94,.2);border-color:#4ade80!important;}
        .btn-primary{background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);transition:all .2s;}
        .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.35);}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">🌾</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900" style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">FarmLog — Edit Crop</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('crops/' . $crop_id) ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">← Back to Crop</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-2 text-green-400 text-xs mb-3">
            <a href="<?= url('crops') ?>" class="hover:text-white">🌾 My Crops</a>
            <span>›</span><a href="<?= url('crops/' . $crop_id) ?>" class="hover:text-white"><?= htmlspecialchars($crop['rice_variety']) ?></a>
            <span>›</span><span class="text-green-200">Edit</span>
        </div>
        <h1 class="text-3xl font-bold mb-1">Edit Crop Cycle</h1>
        <p class="text-green-300 text-sm">Update details for <?= htmlspecialchars($crop['rice_variety']) ?></p>
    </div>
</div>
<main class="max-w-2xl mx-auto px-4 py-8">
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up">
            <p class="text-sm font-semibold text-red-700 mb-2">⚠️ Please fix:</p>
            <?php foreach ($errors as $e): ?><p class="text-sm text-red-600">· <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-up">
        <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex items-center gap-3">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center"><span>✏️</span></div>
            <h2 class="text-white font-semibold text-base">Edit Crop Details</h2>
        </div>
        <div class="p-7">
            <form method="POST" action="<?= url('crops/update/' . $crop_id) ?>">
                <?= csrf_field() ?>
                <!-- Variety -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">🌾 Rice Variety <span class="text-red-400">*</span></label>
                    <input type="text" name="rice_variety" value="<?= htmlspecialchars($crop['rice_variety']) ?>"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>
                <!-- Plot -->
                <?php if (!empty($plots)): ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">🗺️ Field Plot</label>
                    <select name="plot_id" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors cursor-pointer">
                        <option value="">-- None --</option>
                        <?php foreach ($plots as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $crop['plot_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['plot_name']) ?> (<?= $p['area_hectares'] ?> ha)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <!-- Dates grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">📅 Planting Date <span class="text-red-400">*</span></label>
                        <input type="date" name="planting_date" value="<?= htmlspecialchars($crop['planting_date']) ?>"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">🌾 Expected Harvest</label>
                        <input type="date" name="expected_harvest" value="<?= htmlspecialchars($crop['expected_harvest'] ?? '') ?>"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                    </div>
                </div>
                <!-- Status + Actual Harvest -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">📋 Status</label>
                        <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors cursor-pointer">
                            <option value="active"    <?= $crop['status'] === 'active'    ? 'selected' : '' ?>>🌱 Active</option>
                            <option value="harvested" <?= $crop['status'] === 'harvested' ? 'selected' : '' ?>>🌾 Harvested</option>
                            <option value="failed"    <?= $crop['status'] === 'failed'    ? 'selected' : '' ?>>❌ Failed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">✅ Actual Harvest Date</label>
                        <input type="date" name="actual_harvest" value="<?= htmlspecialchars($crop['actual_harvest'] ?? '') ?>"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                    </div>
                </div>
                <!-- Notes -->
                <div class="mb-7">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">📝 Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors resize-none"><?= htmlspecialchars($crop['notes'] ?? '') ?></textarea>
                </div>
                <div class="flex gap-3">
                    <a href="<?= url('crops/' . $crop_id) ?>" class="flex-1 text-center border border-gray-200 text-gray-600 font-medium py-3 rounded-xl text-sm hover:bg-gray-50 transition-colors">← Cancel</a>
                    <button type="submit" class="btn-primary flex-1 text-white font-semibold py-3 rounded-xl text-sm">💾 Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</main>
<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>
</body>
</html>