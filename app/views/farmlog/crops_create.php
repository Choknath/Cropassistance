<?php
/**
 * FARMLOG — Add Crop Cycle
 * File: app/views/farmlog/crops_create.php
 *
 * GET  → show form
 * POST → save crop + auto-generate fertilizer schedule
 *
 * PhilRice fertilizer schedule (per hectare):
 *   Basal     → Day 0  (planting): 45kg Urea + 30kg 0-18-0
 *   Topdress1 → Day 21 (tillering): 45kg Urea
 *   Topdress2 → Day 45 (panicle): 30kg Urea + 30kg MOP
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];
$errors  = [];
$old     = [];

// Load plots for dropdown
try {
    $plots = db()->table('field_plots')->where('user_id', $user_id)->get_all();
    if (!$plots) $plots = [];
} catch (Exception $e) { $plots = []; }

// Redirect if no plots
if (empty($plots)) {
    $_SESSION['flash_error'] = 'Please add a Field Plot before creating a crop cycle.';
    header('Location: ' . url('plots/create')); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $variety       = trim($_POST['rice_variety']   ?? '');
    $plot_id       = (int)($_POST['plot_id']       ?? 0);
    $planting_date = trim($_POST['planting_date']  ?? '');
    $notes         = trim($_POST['notes']          ?? '');
    $old = compact('variety','plot_id','planting_date','notes');

    // Validation
    if (empty($variety))       $errors[] = 'Rice variety is required.';
    if ($plot_id <= 0)         $errors[] = 'Please select a field plot.';
    if (empty($planting_date)) $errors[] = 'Planting date is required.';

    if (empty($errors)) {
        try {
            // Auto-calculate expected harvest: planting + 115 days (average)
            $harvest_date = date('Y-m-d', strtotime($planting_date . ' +115 days'));

            // 1. Insert crop record
            $crop_id = db()->table('rice_crops')->insert([
                'user_id'          => $user_id,
                'plot_id'          => $plot_id,
                'rice_variety'     => $variety,
                'planting_date'    => $planting_date,
                'expected_harvest' => $harvest_date,
                'status'           => 'active',
                'notes'            => $notes ?: null,
            ]);

            // 2. Auto-generate PhilRice fertilizer schedule
            if ($crop_id) {
                $schedule = [
                    [
                        'name'  => 'Basal Application',
                        'days'  => 0,
                        'type'  => 'Urea (46-0-0)',
                        'kg'    => 45.00,
                    ],
                    [
                        'name'  => 'Basal Application',
                        'days'  => 0,
                        'type'  => 'Ammonium Phosphate (0-18-0)',
                        'kg'    => 30.00,
                    ],
                    [
                        'name'  => 'Top Dress 1 (Tillering)',
                        'days'  => 21,
                        'type'  => 'Urea (46-0-0)',
                        'kg'    => 45.00,
                    ],
                    [
                        'name'  => 'Top Dress 2 (Panicle Initiation)',
                        'days'  => 45,
                        'type'  => 'Urea (46-0-0)',
                        'kg'    => 30.00,
                    ],
                    [
                        'name'  => 'Top Dress 2 (Panicle Initiation)',
                        'days'  => 45,
                        'type'  => 'Muriate of Potash (0-0-60)',
                        'kg'    => 30.00,
                    ],
                ];

                foreach ($schedule as $item) {
                    $apply_date = date('Y-m-d', strtotime($planting_date . ' +' . $item['days'] . ' days'));
                    db()->table('fertilizer_schedule')->insert([
                        'crop_id'          => $crop_id,
                        'user_id'          => $user_id,
                        'application_date' => $apply_date,
                        'fertilizer_type'  => $item['type'],
                        'amount_kg'        => $item['kg'],
                        'application_name' => $item['name'],
                        'is_done'          => 0,
                    ]);
                }
            }

            $_SESSION['flash_success'] = "Crop cycle for \"{$variety}\" added! Fertilizer schedule auto-generated.";
            header('Location: ' . url('crops')); exit;

        } catch (Exception $e) {
            $errors[] = 'Could not save. Please try again.';
            error_log('Crop create error: ' . $e->getMessage());
        }
    }
}

// Common PH varieties
$varieties = ['NSIC Rc222 (Tubigan 18)','NSIC Rc160 (Tubigan 7)','NSIC Rc238 (Tubigan 23)','IR64','PSB Rc82 (Mestiso 20)','Dinorado','NSIC Rc9','NSIC Rc10','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Crop Cycle — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        input:focus,select:focus,textarea:focus{outline:none;box-shadow:0 0 0 3px rgba(34,197,94,.2);border-color:#4ade80!important;}
        .btn-primary{background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);transition:all .2s ease;}
        .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.35);}
        .variety-tag{display:inline-block;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:9999px;padding:2px 10px;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;}
        .variety-tag:hover{background:#dcfce7;}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">🌾</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900" style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">FarmLog — Add Crop</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('crops') ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">🌾 My Crops</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-2 text-green-400 text-xs mb-3">
            <a href="<?= url('crops') ?>" class="hover:text-white transition-colors">🌾 My Crops</a>
            <span>›</span><span class="text-green-200">Add Crop Cycle</span>
        </div>
        <h1 class="text-3xl font-bold mb-1">Add Crop Cycle</h1>
        <p class="text-green-300 text-sm">A fertilizer schedule will be auto-generated based on PhilRice guidelines.</p>
    </div>
</div>

<main class="max-w-2xl mx-auto px-4 py-8">
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 fade-up">
            <p class="text-sm font-semibold text-red-700 mb-2">⚠️ Please fix the following:</p>
            <?php foreach ($errors as $e): ?><p class="text-sm text-red-600">· <?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-up">
        <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex items-center gap-3">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center"><span>🌾</span></div>
            <div>
                <h2 class="text-white font-semibold text-base">New Crop Cycle Details</h2>
                <p class="text-green-200 text-xs">Fertilizer schedule auto-generates after saving</p>
            </div>
        </div>
        <div class="p-7">
            <form method="POST" action="<?= url('crops/store') ?>">
                <?= csrf_field() ?>

                <!-- Rice Variety -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">🌾 Rice Variety <span class="text-red-400">*</span></label>
                    <input type="text" name="rice_variety" id="variety_input"
                           value="<?= htmlspecialchars($old['variety'] ?? '') ?>"
                           placeholder="e.g. NSIC Rc222, IR64, Dinorado..."
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors mb-2">
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach (['NSIC Rc222','NSIC Rc160','IR64','PSB Rc82','Dinorado','NSIC Rc238'] as $v): ?>
                        <span class="variety-tag" onclick="document.getElementById('variety_input').value='<?= $v ?>'"><?= $v ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Field Plot -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">🗺️ Field Plot <span class="text-red-400">*</span></label>
                    <select name="plot_id" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors cursor-pointer">
                        <option value="">-- Select your field plot --</option>
                        <?php foreach ($plots as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($old['plot_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['plot_name']) ?> (<?= $p['area_hectares'] ?> ha · <?= $p['location'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Planting Date -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">📅 Planting / Transplanting Date <span class="text-red-400">*</span></label>
                    <input type="date" name="planting_date"
                           value="<?= htmlspecialchars($old['planting_date'] ?? '') ?>"
                           max="<?= date('Y-m-d') ?>"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors">
                    <p class="text-xs text-gray-400 mt-1">Expected harvest will be auto-calculated as 115 days from this date.</p>
                </div>

                <!-- Notes -->
                <div class="mb-7">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">📝 Notes <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                    <textarea name="notes" rows="2"
                              placeholder="e.g. Wet season planting, used certified seeds..."
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white transition-colors resize-none"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
                </div>

                <!-- Auto-schedule notice -->
                <div class="bg-green-50 border border-green-100 rounded-xl p-4 mb-6">
                    <p class="text-xs font-semibold text-green-800 mb-2">💊 Auto-Generated Fertilizer Schedule (PhilRice):</p>
                    <div class="space-y-1 text-xs text-green-700">
                        <div class="flex justify-between"><span>Basal (Day 0)</span><span>45kg Urea + 30kg 0-18-0</span></div>
                        <div class="flex justify-between"><span>Top Dress 1 (Day 21)</span><span>45kg Urea</span></div>
                        <div class="flex justify-between"><span>Top Dress 2 (Day 45)</span><span>30kg Urea + 30kg MOP</span></div>
                    </div>
                    <p class="text-xs text-green-500 mt-2">Amounts are per hectare. Adjust as needed after saving.</p>
                </div>

                <div class="flex gap-3">
                    <a href="<?= url('crops') ?>" class="flex-1 text-center border border-gray-200 text-gray-600 font-medium py-3 rounded-xl text-sm hover:bg-gray-50 transition-colors">← Cancel</a>
                    <button type="submit" class="btn-primary flex-1 text-white font-semibold py-3 rounded-xl text-sm">🌾 Save Crop Cycle</button>
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>
</body>
</html>