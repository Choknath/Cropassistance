<?php
/**
 * FARMLOG — Log Harvest Record
 * File: app/views/farmlog/harvest_create.php
 * Route: GET  harvest/create/{id}
 *        POST harvest/store
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];
$crop_id = (int)($params['id'] ?? $_POST['crop_id'] ?? 0);

if ($crop_id <= 0) { header('Location: ' . url('crops')); exit; }

$errors = [];
$old    = [];

try {
    // Load crop — must belong to this user
    $crop = db()->table('rice_crops')
                ->where('id', $crop_id)
                ->where('user_id', $user_id)
                ->get();
    if (!$crop) { $_SESSION['flash_error'] = 'Crop not found.'; header('Location: ' . url('crops')); exit; }

    // Check if harvest already recorded
    $existing = db()->table('harvest_records')->where('crop_id', $crop_id)->get();
    if ($existing) {
        $_SESSION['flash_success'] = 'Harvest already recorded for this crop.';
        header('Location: ' . url('crops/' . $crop_id)); exit;
    }

    $plot = $crop['plot_id']
        ? db()->table('field_plots')->where('id', $crop['plot_id'])->get()
        : null;

} catch (Exception $e) {
    error_log('Harvest create load error: ' . $e->getMessage());
    header('Location: ' . url('crops')); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $harvest_date  = trim($_POST['harvest_date']   ?? '');
    $qty_kg        = trim($_POST['quantity_kg']    ?? '');
    $qty_cav       = trim($_POST['quantity_cavans']?? '');
    $grade         = trim($_POST['quality_grade']  ?? 'standard');
    $price         = trim($_POST['selling_price']  ?? '');
    $notes         = trim($_POST['notes']          ?? '');
    $old = compact('harvest_date','qty_kg','qty_cav','grade','price','notes');

    // Validation
    if (empty($harvest_date))                              $errors[] = 'Harvest date is required.';
    if (empty($qty_kg) || !is_numeric($qty_kg) || (float)$qty_kg <= 0) $errors[] = 'Yield in kg must be a positive number.';

    // Auto-calculate cavans if not provided (1 cavan ≈ 50 kg)
    if (empty($qty_cav) || !is_numeric($qty_cav)) {
        $qty_cav = is_numeric($qty_kg) ? round((float)$qty_kg / 50, 2) : 0;
    }

    // Auto-calculate total income
    $total_income = null;
    if (!empty($price) && is_numeric($price) && is_numeric($qty_cav)) {
        $total_income = round((float)$price * (float)$qty_cav, 2);
    }

    if (empty($errors)) {
        try {
            // 1. Save harvest record
            db()->table('harvest_records')->insert([
                'crop_id'         => $crop_id,
                'user_id'         => $user_id,
                'harvest_date'    => $harvest_date,
                'quantity_kg'     => (float)$qty_kg,
                'quantity_cavans' => (float)$qty_cav,
                'quality_grade'   => $grade,
                'selling_price'   => $price !== '' ? (float)$price : null,
                'total_income'    => $total_income,
                'notes'           => $notes ?: null,
            ]);

            // 2. Mark crop as harvested
            db()->table('rice_crops')
                ->where('id', $crop_id)
                ->update([
                    'status'         => 'harvested',
                    'actual_harvest' => $harvest_date,
                ]);

            $_SESSION['flash_success'] = 'Harvest recorded successfully! 🎉';
            header('Location: ' . url('crops/' . $crop_id)); exit;

        } catch (Exception $e) {
            $errors[] = 'Could not save harvest. Please try again.';
            error_log('Harvest insert error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Harvest — <?= htmlspecialchars($crop['rice_variety']) ?></title>
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
                <div class="text-xs text-gray-400">FarmLog — Log Harvest</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('crops/' . $crop_id) ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">← Back to Crop</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-2 text-green-400 text-xs mb-3">
            <a href="<?= url('crops') ?>" class="hover:text-white">🌾 My Crops</a>
            <span>›</span>
            <a href="<?= url('crops/' . $crop_id) ?>" class="hover:text-white"><?= htmlspecialchars($crop['rice_variety']) ?></a>
            <span>›</span><span class="text-green-200">Log Harvest</span>
        </div>
        <h1 class="text-3xl font-bold mb-1">🧺 Log Harvest</h1>
        <p class="text-green-300 text-sm">
            Recording yield for:
            <strong class="text-white"><?= htmlspecialchars($crop['rice_variety']) ?></strong>
            <?php if ($plot): ?> · <?= htmlspecialchars($plot['plot_name']) ?> (<?= $plot['area_hectares'] ?> ha)<?php endif; ?>
        </p>
    </div>
</div>

<main class="max-w-2xl mx-auto px-4 py-8">

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up">
            <p class="text-sm font-semibold text-red-700 mb-2">⚠️ Please fix:</p>
            <?php foreach ($errors as $e): ?><p class="text-sm text-red-600">· <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Crop summary banner -->
    <div class="bg-green-50 border border-green-100 rounded-2xl p-4 mb-5 fade-up flex items-center gap-4">
        <div class="text-3xl">🌾</div>
        <div>
            <p class="font-semibold text-green-800 text-sm"><?= htmlspecialchars($crop['rice_variety']) ?></p>
            <p class="text-xs text-green-600">
                Planted <?= date('M j, Y', strtotime($crop['planting_date'])) ?>
                <?php if ($crop['expected_harvest']): ?> · Expected harvest <?= date('M j, Y', strtotime($crop['expected_harvest'])) ?><?php endif; ?>
            </p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-up">
        <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 px-6 py-4 flex items-center gap-3">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center"><span>🧺</span></div>
            <div>
                <h2 class="text-white font-semibold text-base">Harvest Details</h2>
                <p class="text-yellow-100 text-xs">Cavans will be auto-calculated (1 cavan = 50 kg)</p>
            </div>
        </div>
        <div class="p-7">
            <form method="POST" action="<?= url('harvest/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="crop_id" value="<?= $crop_id ?>">

                <!-- Harvest Date -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">📅 Harvest Date <span class="text-red-400">*</span></label>
                    <input type="date" name="harvest_date"
                           value="<?= htmlspecialchars($old['harvest_date'] ?? date('Y-m-d')) ?>"
                           max="<?= date('Y-m-d') ?>"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Yield kg + cavans -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">⚖️ Yield (kg) <span class="text-red-400">*</span></label>
                        <input type="number" name="quantity_kg" id="qty_kg"
                               value="<?= htmlspecialchars($old['qty_kg'] ?? '') ?>"
                               placeholder="e.g. 2500" step="0.01" min="0.01"
                               oninput="autoCavan(this.value)"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                        <p class="text-xs text-gray-400 mt-1">Total kilograms harvested</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">🌾 Cavans <span class="text-gray-400 font-normal text-xs">(auto)</span></label>
                        <input type="number" name="quantity_cavans" id="qty_cav"
                               value="<?= htmlspecialchars($old['qty_cav'] ?? '') ?>"
                               placeholder="auto-calculated" step="0.01" min="0"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                        <p class="text-xs text-gray-400 mt-1">1 cavan ≈ 50 kg · edit if needed</p>
                    </div>
                </div>

                <!-- Quality grade -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">⭐ Quality Grade</label>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach (['premium' => '⭐ Premium', 'standard' => '✅ Standard', 'poor' => '⚠️ Poor'] as $val => $lbl): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="quality_grade" value="<?= $val ?>"
                                   <?= ($old['grade'] ?? 'standard') === $val ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="border-2 border-gray-200 rounded-xl px-3 py-2.5 text-center text-sm font-medium text-gray-600
                                        peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition-all">
                                <?= $lbl ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Selling Price + Total Income -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">💰 Selling Price <span class="text-gray-400 font-normal text-xs">(per cavan, optional)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">₱</span>
                            <input type="number" name="selling_price" id="price"
                                   value="<?= htmlspecialchars($old['price'] ?? '') ?>"
                                   placeholder="e.g. 1200" step="0.01" min="0"
                                   oninput="calcIncome()"
                                   class="w-full border border-gray-200 rounded-xl pl-7 pr-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">💵 Estimated Income <span class="text-gray-400 font-normal text-xs">(auto)</span></label>
                        <div class="bg-green-50 border border-green-100 rounded-xl px-4 py-3 text-sm font-semibold text-green-700" id="income-display">
                            ₱ —
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-7">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">📝 Notes <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                    <textarea name="notes" rows="2"
                              placeholder="e.g. Sold to NFA, good weather during harvest..."
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors resize-none"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
                </div>

                <div class="flex gap-3">
                    <a href="<?= url('crops/' . $crop_id) ?>" class="flex-1 text-center border border-gray-200 text-gray-600 font-medium py-3 rounded-xl text-sm hover:bg-gray-50 transition-colors">← Cancel</a>
                    <button type="submit" class="btn-primary flex-1 text-white font-semibold py-3 rounded-xl text-sm">🧺 Save Harvest Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info tip -->
    <div class="mt-5 bg-yellow-50 border border-yellow-100 rounded-2xl p-4 text-xs text-yellow-700">
        <p class="font-semibold mb-1">💡 After logging harvest:</p>
        <p>The crop status will automatically be updated to <strong>Harvested</strong>. You can view all harvest records in the <a href="<?= url('harvest') ?>" class="underline">Harvest page</a>.</p>
    </div>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>

<script>
    // Auto-fill cavans when kg is entered (1 cavan = 50 kg)
    function autoCavan(kg) {
        const cav = document.getElementById('qty_cav');
        if (kg && !isNaN(kg)) {
            cav.value = (parseFloat(kg) / 50).toFixed(2);
            calcIncome();
        }
    }

    // Auto-calculate total income = price × cavans
    function calcIncome() {
        const price = parseFloat(document.getElementById('price').value) || 0;
        const cav   = parseFloat(document.getElementById('qty_cav').value) || 0;
        const el    = document.getElementById('income-display');
        if (price > 0 && cav > 0) {
            el.textContent = '₱ ' + (price * cav).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            el.textContent = '₱ —';
        }
    }

    // Also trigger income calc when cavans is manually edited
    document.getElementById('qty_cav').addEventListener('input', calcIncome);
</script>
</body>
</html>