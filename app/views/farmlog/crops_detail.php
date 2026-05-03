<?php
/**
 * FARMLOG — Crop Detail Page
 * File: app/views/farmlog/crops_detail.php
 *
 * The hub page for one crop cycle. Shows:
 *   1. Crop summary (variety, dates, status)
 *   2. Growth stage progress timeline (log new stage here)
 *   3. Fertilizer schedule with mark-as-done
 *   4. Harvest record (if any)
 *
 * Route: GET crops/{id}
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }

$user    = $_SESSION['user'];
$user_id = $user['id'];

$crop_id = (int)($params['id'] ?? 0);
if ($crop_id <= 0) { header('Location: ' . url('crops')); exit; }

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

try {
    // Load the crop (security: must belong to this user)
    $crop = db()->table('rice_crops')
                ->where('id', $crop_id)
                ->where('user_id', $user_id)
                ->get();
    if (!$crop) { $_SESSION['flash_error'] = 'Crop not found.'; header('Location: ' . url('crops')); exit; }

    // Load field plot
    $plot = $crop['plot_id']
        ? db()->table('field_plots')->where('id', $crop['plot_id'])->get()
        : null;

    // Load growth progress logs (oldest first for timeline)
    $progress = db()->table('crop_progress')
                    ->where('crop_id', $crop_id)
                    ->order_by('date_recorded', 'ASC')
                    ->get_all();
    if (!$progress) $progress = [];

    // Load fertilizer schedule
    $fertilizer = db()->table('fertilizer_schedule')
                      ->where('crop_id', $crop_id)
                      ->order_by('application_date', 'ASC')
                      ->get_all();
    if (!$fertilizer) $fertilizer = [];

    // Load harvest record (if any)
    $harvest = db()->table('harvest_records')
                   ->where('crop_id', $crop_id)
                   ->get();

} catch (Exception $e) {
    error_log('Crop detail error: ' . $e->getMessage());
    header('Location: ' . url('crops')); exit;
}

// ── Helpers ────────────────────────────────────────────────
$stages_order = ['land_preparation','seedling','transplanting','tillering','panicle_initiation','flowering','ripening','harvested'];
$stage_labels = [
    'land_preparation'  => '🚜 Land Preparation',
    'seedling'          => '🌱 Seedling',
    'transplanting'     => '🌿 Transplanting',
    'tillering'         => '🌿 Tillering',
    'panicle_initiation'=> '🌸 Panicle Initiation',
    'flowering'         => '🌸 Flowering',
    'ripening'          => '🌾 Ripening',
    'harvested'         => '🟡 Harvested',
];

// Logged stages set
$logged_stages = array_column($progress, 'growth_stage');
$latest_stage  = end($logged_stages) ?: '';

// Days planted
$days_planted = 0;
if ($crop['planting_date']) {
    $days_planted = (int)(new DateTime())->diff(new DateTime($crop['planting_date']))->days;
}

// Pending fertilizer count
$pending_fert = count(array_filter($fertilizer, fn($f) => !$f['is_done']));
$overdue_fert = count(array_filter($fertilizer, fn($f) => !$f['is_done'] && $f['application_date'] < date('Y-m-d')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($crop['rice_variety']) ?> — Crop Detail</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        input:focus,select:focus,textarea:focus{outline:none;box-shadow:0 0 0 3px rgba(34,197,94,.2);border-color:#4ade80!important;}
        .timeline-dot{width:16px;height:16px;border-radius:50%;flex-shrink:0;margin-top:2px;}
        .timeline-line{width:2px;background:#e5e7eb;flex-shrink:0;min-height:32px;}
        .btn-primary{background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);transition:all .2s;}
        .btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(22,163,74,.35);}
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
                <div class="text-xs text-gray-400">FarmLog — Crop Detail</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('dashboard') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 Dashboard</a>
            <a href="<?= url('crops') ?>" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200">🌾 My Crops</a>
            <a href="<?= url('fertilizer') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">💊 Fertilizer</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center gap-2 text-green-400 text-xs mb-3">
            <a href="<?= url('crops') ?>" class="hover:text-white">🌾 My Crops</a>
            <span>›</span><span class="text-green-200"><?= htmlspecialchars($crop['rice_variety']) ?></span>
        </div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-1"><?= htmlspecialchars($crop['rice_variety']) ?></h1>
                <div class="flex flex-wrap gap-3 text-green-300 text-sm">
                    <span>🗺️ <?= htmlspecialchars($plot['plot_name'] ?? '—') ?></span>
                    <span>📅 Planted <?= date('M j, Y', strtotime($crop['planting_date'])) ?></span>
                    <span>📆 Day <?= $days_planted ?> of growth</span>
                    <?php if ($crop['expected_harvest']): ?>
                        <span>🌾 Expected <?= date('M j, Y', strtotime($crop['expected_harvest'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            $sb = match($crop['status']) {
                'active'    => 'bg-green-100 text-green-800',
                'harvested' => 'bg-yellow-100 text-yellow-800',
                'failed'    => 'bg-red-100 text-red-800',
                default     => 'bg-gray-100 text-gray-700',
            };
            ?>
            <span class="px-4 py-2 rounded-xl text-sm font-semibold <?= $sb ?>">
                <?= ucfirst($crop['status']) ?>
            </span>
        </div>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ── LEFT COLUMN ──────────────────────────────────── -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Flash messages -->
        <?php if ($flash_success): ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 fade-up"><p class="text-sm text-green-700">✅ <?= htmlspecialchars($flash_success) ?></p></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 fade-up"><p class="text-sm text-red-700">❌ <?= htmlspecialchars($flash_error) ?></p></div>
        <?php endif; ?>

        <!-- ── GROWTH STAGE TIMELINE ──────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
            <div class="bg-green-700 px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2"><span class="text-white">🌱</span><span class="text-white font-medium text-sm">Growth Stage Progress</span></div>
                <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full"><?= count($logged_stages) ?>/<?= count($stages_order) ?> stages</span>
            </div>
            <div class="p-5">
                <!-- Timeline visualization -->
                <div class="space-y-0 mb-5">
                    <?php foreach ($stages_order as $i => $stage):
                        $is_done   = in_array($stage, $logged_stages);
                        $is_latest = $stage === $latest_stage;
                        $is_last   = $i === count($stages_order) - 1;
                        // Find the log entry for this stage
                        $log_entry = null;
                        foreach ($progress as $p) {
                            if ($p['growth_stage'] === $stage) { $log_entry = $p; break; }
                        }
                    ?>
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="timeline-dot <?= $is_done ? 'bg-green-500 border-2 border-green-600' : ($is_latest ? 'bg-yellow-400' : 'bg-gray-200 border-2 border-gray-300') ?>"></div>
                            <?php if (!$is_last): ?><div class="timeline-line mx-auto <?= $is_done ? 'bg-green-200' : 'bg-gray-100' ?>"></div><?php endif; ?>
                        </div>
                        <div class="pb-4 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium <?= $is_done ? 'text-green-700' : 'text-gray-400' ?>">
                                    <?= $stage_labels[$stage] ?>
                                </span>
                                <?php if ($is_done): ?>
                                    <span class="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full">✓ Done</span>
                                <?php elseif ($is_latest): ?>
                                    <span class="text-xs bg-yellow-100 text-yellow-600 px-2 py-0.5 rounded-full">← Current</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($log_entry): ?>
                                <p class="text-xs text-gray-400 mt-0.5">📅 <?= date('M j, Y', strtotime($log_entry['date_recorded'])) ?></p>
                                <?php if ($log_entry['notes']): ?><p class="text-xs text-gray-400 truncate">📝 <?= htmlspecialchars($log_entry['notes']) ?></p><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Log new growth stage form -->
                <?php if ($crop['status'] === 'active'): ?>
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold text-gray-600 mb-3">📋 Log a new growth stage:</p>
                    <form method="POST" action="<?= url('progress/store') ?>" class="flex flex-wrap gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="crop_id" value="<?= $crop_id ?>">
                        <select name="growth_stage" required
                                class="flex-1 min-w-32 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:bg-white transition-colors cursor-pointer">
                            <option value="">-- Select stage --</option>
                            <?php foreach ($stages_order as $s): ?>
                            <option value="<?= $s ?>"><?= $stage_labels[$s] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="date_recorded"
                               value="<?= date('Y-m-d') ?>"
                               max="<?= date('Y-m-d') ?>"
                               class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:bg-white transition-colors">
                        <input type="text" name="notes" placeholder="Notes (optional)"
                               class="flex-1 min-w-40 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:bg-white transition-colors">
                        <button type="submit" class="btn-primary text-white text-sm font-medium px-4 py-2 rounded-xl">+ Log</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── FERTILIZER SCHEDULE ─────────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
            <div class="bg-green-800 px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2"><span class="text-white">💊</span><span class="text-white font-medium text-sm">Fertilizer Schedule</span></div>
                <div class="flex items-center gap-2">
                    <?php if ($overdue_fert > 0): ?><span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full"><?= $overdue_fert ?> overdue</span><?php endif; ?>
                    <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full"><?= $pending_fert ?> pending</span>
                </div>
            </div>
            <?php if (empty($fertilizer)): ?>
                <div class="p-8 text-center text-gray-400 text-sm">No fertilizer schedule yet.</div>
            <?php else: ?>
            <div class="divide-y divide-gray-50">
                <?php foreach ($fertilizer as $f):
                    $is_overdue = !$f['is_done'] && $f['application_date'] < date('Y-m-d');
                    $row_bg = $f['is_done'] ? 'bg-green-50/50' : ($is_overdue ? 'bg-red-50/50' : '');
                ?>
                <div class="px-5 py-3.5 flex items-center gap-4 <?= $row_bg ?>">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                            <span class="text-sm font-medium <?= $f['is_done'] ? 'text-gray-400 line-through' : 'text-gray-800' ?>">
                                <?= htmlspecialchars($f['application_name']) ?>
                            </span>
                            <?php if ($is_overdue): ?><span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Overdue</span><?php endif; ?>
                            <?php if ($f['is_done']): ?><span class="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full">✓ Done</span><?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 flex flex-wrap gap-x-3">
                            <span>💊 <?= htmlspecialchars($f['fertilizer_type']) ?></span>
                            <span>⚖️ <?= $f['amount_kg'] ?> kg/ha</span>
                            <span>📅 <?= date('M j, Y', strtotime($f['application_date'])) ?></span>
                        </div>
                    </div>
                    <?php if (!$f['is_done']): ?>
                    <form method="POST" action="<?= url('fertilizer/done') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="fertilizer_id" value="<?= $f['id'] ?>">
                        <input type="hidden" name="crop_id" value="<?= $crop_id ?>">
                        <button type="submit" class="text-xs bg-green-700 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">✓ Mark Done</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── RIGHT COLUMN ─────────────────────────────────── -->
    <div class="space-y-5">

        <!-- Crop Summary Card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">
            <div class="bg-gradient-to-br from-green-700 to-green-800 p-5">
                <div class="text-4xl mb-2 text-center">🌾</div>
                <h3 class="text-white font-bold text-center text-lg"><?= htmlspecialchars($crop['rice_variety']) ?></h3>
                <?php if ($plot): ?>
                    <p class="text-green-300 text-xs text-center mt-0.5">📍 <?= htmlspecialchars($plot['plot_name']) ?> · <?= $plot['area_hectares'] ?> ha</p>
                <?php endif; ?>
            </div>
            <div class="p-4 space-y-2.5">
                <div class="flex justify-between text-sm"><span class="text-gray-400">📅 Planted</span><span class="font-medium text-gray-700"><?= date('M j, Y', strtotime($crop['planting_date'])) ?></span></div>
                <?php if ($crop['expected_harvest']): ?>
                <div class="flex justify-between text-sm"><span class="text-gray-400">🌾 Exp. Harvest</span><span class="font-medium text-gray-700"><?= date('M j, Y', strtotime($crop['expected_harvest'])) ?></span></div>
                <?php endif; ?>
                <?php if ($crop['actual_harvest']): ?>
                <div class="flex justify-between text-sm"><span class="text-gray-400">✅ Harvested</span><span class="font-medium text-green-700"><?= date('M j, Y', strtotime($crop['actual_harvest'])) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between text-sm"><span class="text-gray-400">📆 Day</span><span class="font-bold text-green-700"><?= $days_planted ?></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-400">📋 Stage</span><span class="font-medium text-gray-700"><?= $latest_stage ? $stage_labels[$latest_stage] : '—' ?></span></div>
                <?php if ($crop['notes']): ?><div class="bg-gray-50 rounded-xl p-2.5 border border-gray-100"><p class="text-xs text-gray-500">📝 <?= htmlspecialchars($crop['notes']) ?></p></div><?php endif; ?>
            </div>
            <div class="px-4 pb-4 flex gap-2">
                <a href="<?= url('crops/edit/' . $crop_id) ?>" class="flex-1 text-center border border-gray-200 text-gray-600 text-xs font-medium py-2 rounded-xl hover:bg-gray-50 transition-colors">✏️ Edit</a>
                <?php if ($crop['status'] === 'active' && !$harvest): ?>
                <a href="<?= url('harvest/create/' . $crop_id) ?>" class="flex-1 text-center bg-green-700 hover:bg-green-600 text-white text-xs font-medium py-2 rounded-xl transition-colors">🧺 Log Harvest</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Harvest Record -->
        <?php if ($harvest): ?>
        <div class="bg-white rounded-2xl border border-yellow-200 shadow-sm overflow-hidden fade-up">
            <div class="bg-yellow-100 px-5 py-3 flex items-center gap-2 border-b border-yellow-200">
                <span>🧺</span><span class="font-medium text-yellow-800 text-sm">Harvest Record</span>
            </div>
            <div class="p-4 space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-400">📅 Date</span><span class="font-medium"><?= date('M j, Y', strtotime($harvest['harvest_date'])) ?></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-400">⚖️ Yield (kg)</span><span class="font-bold text-green-700"><?= number_format($harvest['quantity_kg'], 2) ?> kg</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-400">🌾 Cavans</span><span class="font-medium"><?= number_format($harvest['quantity_cavans'], 2) ?></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-400">⭐ Grade</span><span class="font-medium capitalize"><?= $harvest['quality_grade'] ?></span></div>
                <?php if ($harvest['selling_price']): ?><div class="flex justify-between text-sm"><span class="text-gray-400">💰 Price/cavan</span><span class="font-medium">₱<?= number_format($harvest['selling_price'], 2) ?></span></div><?php endif; ?>
                <?php if ($harvest['total_income']): ?><div class="flex justify-between text-sm"><span class="text-gray-400">💵 Total Income</span><span class="font-bold text-green-700">₱<?= number_format($harvest['total_income'], 2) ?></span></div><?php endif; ?>
                <?php if ($harvest['notes']): ?><p class="text-xs text-gray-400 pt-1">📝 <?= htmlspecialchars($harvest['notes']) ?></p><?php endif; ?>
            </div>
        </div>
        <?php elseif ($crop['status'] === 'active'): ?>
        <div class="bg-green-50 border border-green-100 rounded-2xl p-5 text-center fade-up">
            <div class="text-3xl mb-2">🧺</div>
            <p class="text-sm text-green-700 font-medium mb-3">Ready to harvest?</p>
            <a href="<?= url('harvest/create/' . $crop_id) ?>" class="inline-block bg-green-700 hover:bg-green-600 text-white text-xs font-medium px-4 py-2 rounded-xl transition-colors">Log Harvest Record</a>
        </div>
        <?php endif; ?>

        <!-- Quick links -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 fade-up">
            <p class="text-xs font-semibold text-gray-600 mb-3">⚡ Quick Actions</p>
            <div class="space-y-2">
                <a href="<?= url('crop-assistant') ?>" class="flex items-center gap-2 text-sm text-green-700 hover:text-green-900 font-medium"><span class="w-7 h-7 bg-green-700 text-white rounded-lg flex items-center justify-center text-xs">🔍</span>Scan plant disease</a>
                <a href="<?= url('fertilizer') ?>" class="flex items-center gap-2 text-sm text-green-700 hover:text-green-900 font-medium"><span class="w-7 h-7 bg-green-100 border border-green-200 rounded-lg flex items-center justify-center text-xs">💊</span>All fertilizer schedules</a>
                <a href="<?= url('harvest') ?>" class="flex items-center gap-2 text-sm text-green-700 hover:text-green-900 font-medium"><span class="w-7 h-7 bg-green-100 border border-green-200 rounded-lg flex items-center justify-center text-xs">🧺</span>All harvest records</a>
            </div>
        </div>

    </div>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — FarmLog Module</div></footer>
</body>
</html>