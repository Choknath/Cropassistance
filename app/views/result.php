<?php
/**
 * SMART CROP ASSISTANT - Result Page v2.1
 * File: app/views/result.php
 * Now saves user_id with every scan for per-farmer history.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('crop-assistant'));
    exit;
}

/* ---- Collect form data ---- */
$plant_name   = trim($_POST['plant_name']   ?? '');
$location     = trim($_POST['location']     ?? 'Bicol');
$growth_stage = trim($_POST['growth_stage'] ?? '');
$days_planted = (int)($_POST['days_planted'] ?? 0);

if (empty($plant_name)) $plant_name = 'Rice Plant';
if (empty($location))   $location   = 'Bicol';

/* ---- Validate & save image ---- */
$upload_error   = '';
$saved_filename = '';

if (!isset($_FILES['plant_image']) || $_FILES['plant_image']['error'] === UPLOAD_ERR_NO_FILE) {
    $upload_error = 'No image was uploaded. Please go back and select a photo.';
} elseif ($_FILES['plant_image']['error'] !== UPLOAD_ERR_OK) {
    $upload_error = 'Upload failed with error code: ' . $_FILES['plant_image']['error'];
} else {
    $original_name = $_FILES['plant_image']['name'];
    $file_tmp      = $_FILES['plant_image']['tmp_name'];
    $file_size     = $_FILES['plant_image']['size'];
    $image_info    = getimagesize($file_tmp);
    if ($image_info === false) {
        $upload_error = 'The uploaded file is not a valid image.';
    } elseif ($file_size > 5 * 1024 * 1024) {
        $upload_error = 'Image is too large. Maximum size is 5MB.';
    } else {
        $extension      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $saved_filename = 'plant_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $upload_dir     = __DIR__ . '/../../public/uploads/';
        $upload_path    = $upload_dir . $saved_filename;
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!move_uploaded_file($file_tmp, $upload_path)) {
            $upload_error = 'Could not save the image. Check folder permissions.';
        }
    }
}

/* ---- Build image URL ---- */
$image_url = '';
if (!empty($saved_filename)) {
    $image_url = base_url() . '/public/uploads/' . $saved_filename;
}

/* ---- Run analysis ---- */
require_once __DIR__ . '/analyzer.php';
$image_server_path = __DIR__ . '/../../public/uploads/' . $saved_filename;
$analysis = analyzePlantImage($image_server_path, $plant_name, $growth_stage, $days_planted);

/* ---- Fetch weather ---- */
require_once __DIR__ . '/weather.php';
if (!defined('WEATHER_API_KEY')) {
    define('WEATHER_API_KEY', '7967f548bdab9a42f7448c05c9635a25');
}
$weather = getWeatherData($location, WEATHER_API_KEY);

/* ---- Generate recommendations ---- */
$recommendations = generateRiceRecommendations($analysis, $weather);

/* ---- Save to database WITH user_id ---- */
$scan_id = null;
if (empty($upload_error) && !empty($saved_filename)) {
    try {
        $scan_id = db()->table('scans')->insert([
            'user_id'           => $user['id'] ?? null,  // ← links scan to farmer
            'plant_name'        => $plant_name,
            'location'          => $location,
            'image_filename'    => $saved_filename,
            'condition_name'    => $analysis['condition'],
            'status'            => $analysis['status'],
            'severity'          => $analysis['severity'],
            'confidence'        => $analysis['confidence'],
            'description'       => $analysis['description'],
            'advice'            => $analysis['advice'],
            'weather_temp'      => $weather['temp'],
            'weather_humidity'  => $weather['humidity'],
            'weather_condition' => $weather['condition'],
        ]);
    } catch (Exception $e) {
        error_log('Smart Crop DB Error: ' . $e->getMessage());
    }
}

/* ---- Recommendations function ---- */
function generateRiceRecommendations($analysis, $weather) {
    $tips      = [];
    $condition = $analysis['condition'];
    $temp      = $weather['temp'];
    $humidity  = $weather['humidity'];
    $w_cond    = $weather['condition'];
    $stage     = $analysis['growth_stage'] ?? '';

    if (str_contains($condition, 'Blast')) {
        $tips[] = '🍄 Apply Tricyclazole 75WP fungicide at 0.5g/L of water. Spray early morning for best absorption.';
        $tips[] = '✂️ Remove and burn infected leaves immediately to prevent spores from spreading.';
        $tips[] = '🌾 For next season, use Blast-resistant varieties: NSIC Rc160, NSIC Rc222, or PSB Rc82.';
    } elseif (str_contains($condition, 'Bacterial')) {
        $tips[] = '🦠 Apply copper hydroxide bactericide spray. Do NOT use nitrogen fertilizer when BLB is active.';
        $tips[] = '💧 Drain your field completely for 3-5 days to reduce water-borne bacterial spread.';
        $tips[] = '🌾 Next season: use resistant varieties like NSIC Rc238 or NSIC Rc9.';
    } elseif (str_contains($condition, 'Brown Spot')) {
        $tips[] = '🧪 Apply Mancozeb 80WP fungicide at 2g/L of water every 7-10 days.';
        $tips[] = '🌱 Brown Spot often signals low soil potassium. Apply muriate of potash (0-0-60) at 30 kg/ha.';
        $tips[] = '📋 Conduct soil testing after harvest to plan next season fertilizer properly.';
    } elseif (str_contains($condition, 'Sheath')) {
        $tips[] = '💉 Apply Validamycin 3L at 2mL/L of water directly on the sheath area near the waterline.';
        $tips[] = '📏 Reduce planting density: recommended spacing is 20x20cm or 25x25cm.';
        $tips[] = '🚰 Drain field periodically — sheath blight thrives in flooded dense paddies.';
    } elseif (str_contains($condition, 'Nitrogen')) {
        $tips[] = '🌿 Apply urea fertilizer (46-0-0) at 30-45 kg/ha. Split: apply now and at panicle initiation.';
        $tips[] = '🕐 Best time to apply: early morning or late afternoon to minimize evaporation.';
        $tips[] = '📊 Use a Leaf Color Chart (LCC) weekly to monitor nitrogen status of your rice.';
    } elseif (str_contains($condition, 'Drought')) {
        $tips[] = '💧 Irrigate immediately. Maintain 2-5cm standing water especially during flowering.';
        $tips[] = '🔄 Use Alternate Wetting and Drying (AWD) — PhilRice technique that saves 30% water.';
        $tips[] = '🌅 Water early morning to reduce evaporation during hot afternoons.';
    } else {
        $tips[] = '✅ Your rice is healthy! Monitor every 3-4 days during the growing season.';
        $tips[] = '📅 Follow PhilRice fertilizer schedule: Basal → Tillering → Panicle initiation.';
        $tips[] = '🐛 Scout for pests (stem borers, leaf folders, brown planthoppers) every week.';
    }
    if ($stage === 'flowering') {
        $tips[] = '🌸 Flowering stage warning: Critical for yield. Ensure no water stress and avoid all pesticide spraying now.';
    } elseif ($stage === 'ripening') {
        $tips[] = '🌾 Ripening tip: Reduce irrigation to 1-2cm. Begin preparing harvesting equipment and storage.';
    }
    if ($w_cond === 'Rain' || $humidity > 80) {
        $tips[] = '🌧️ High humidity/rain: Apply preventive fungicide. Ensure field drainage is clear.';
    }
    if ($temp > 34) {
        $tips[] = '🌡️ High heat: Maintain 5cm water depth. Avoid fertilizer during peak heat (10am-3pm).';
    }
    if ($temp >= 24 && $temp <= 32 && $w_cond === 'Clear') {
        $tips[] = '☀️ Ideal weather for pesticide/fertilizer application. Apply in the morning.';
    }
    if ($humidity < 40) {
        $tips[] = '💦 Low humidity: Check irrigation canals and ensure consistent water supply.';
    }
    return $tips;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analysis Result — <?= htmlspecialchars($plant_name) ?></title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body     { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3 { font-family: 'Playfair Display', Georgia, serif; }
        .nav-glass { background: rgba(255,255,255,0.88); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); }
        .fade-up { opacity: 0; animation: fadeUp 0.5s ease forwards; }
        .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; } .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .bar-fill { animation: barGrow 1s ease forwards; animation-delay: 0.6s; width: 0%; }
        @keyframes barGrow { to { width: <?= $analysis['confidence'] ?>%; } }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- NAV -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-5xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3 group">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center group-hover:bg-green-600 transition-colors shadow-sm"><span class="text-lg">🌾</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900" style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">Rice Health Monitoring</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('dashboard') ?>" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 Dashboard</a>
            <a href="<?= url('crop-assistant') ?>" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors"><span>🔍</span> Analyze</a>
            <a href="<?= url('history') ?>" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors"><span>📋</span> History</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50 ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-3xl mx-auto">
        <p class="text-green-300 text-sm font-medium mb-1">🌾 Rice Analysis Complete ✅</p>
        <h1 class="text-3xl font-bold mb-1"><?= htmlspecialchars($plant_name) ?></h1>
        <div class="flex flex-wrap items-center gap-3 text-green-300 text-sm">
            <span>📍 <?= htmlspecialchars($location) ?></span>
            <span>·</span><span>🕐 <?= date('F j, Y  g:i A') ?></span>
            <?php if (!empty($growth_stage)): ?><span>·</span><span>🌱 <?= ucfirst(htmlspecialchars($growth_stage)) ?> stage</span><?php endif; ?>
            <?php if ($days_planted > 0): ?><span>·</span><span>📅 Day <?= $days_planted ?></span><?php endif; ?>
            <?php if ($scan_id): ?><span>·</span><span>🗄️ Saved #<?= $scan_id ?></span><?php endif; ?>
            <?php if ($user): ?><span>·</span><span>👨‍🌾 <?= htmlspecialchars($user['full_name']) ?></span><?php endif; ?>
        </div>
    </div>
</div>

<!-- MAIN -->
<main class="max-w-3xl mx-auto px-4 py-8 space-y-5">

    <?php if (!empty($upload_error)): ?>
    <div class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center fade-up">
        <div class="text-4xl mb-3">❌</div>
        <h2 class="text-lg font-semibold text-red-700 mb-2">Upload Failed</h2>
        <p class="text-red-600 text-sm mb-5"><?= htmlspecialchars($upload_error) ?></p>
        <a href="<?= url('crop-assistant') ?>" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-colors">← Go Back and Try Again</a>
    </div>

    <?php else: ?>

    <!-- Photo + Analysis -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden fade-up delay-1">
            <div class="bg-green-700 px-4 py-3 flex items-center gap-2"><span>📷</span><span class="text-white text-sm font-medium">Uploaded Photo</span></div>
            <div class="p-4">
                <img src="<?= htmlspecialchars($image_url) ?>" alt="Rice plant photo" class="w-full rounded-xl object-cover max-h-52 border border-gray-100">
                <p class="text-xs text-gray-400 mt-2 text-center truncate"><?= htmlspecialchars($saved_filename) ?></p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 fade-up delay-2">
            <div class="bg-green-700 px-4 py-3 flex items-center gap-2"><span>🔬</span><span class="text-white text-sm font-medium">Disease Analysis</span></div>
            <div class="p-5 space-y-3">
                <?php
                $badge_classes = match($analysis['color']) {
                    'green'  => 'bg-green-100 text-green-700 border-green-200',
                    'yellow' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                    'red'    => 'bg-red-50    text-red-700    border-red-200',
                    default  => 'bg-gray-100  text-gray-700   border-gray-200',
                };
                $status_icon = match($analysis['color']) { 'green' => '✅', 'yellow' => '⚠️', 'red' => '🚨', default => '🔬' };
                ?>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold border <?= $badge_classes ?>"><?= $status_icon ?> <?= htmlspecialchars($analysis['status']) ?></span>
                    <span class="text-xs text-gray-400 border border-gray-200 px-2 py-1 rounded-full"><?= htmlspecialchars($analysis['severity']) ?> severity</span>
                </div>
                <p class="text-base font-semibold text-gray-800"><?= htmlspecialchars($analysis['condition']) ?></p>
                <p class="text-xs text-gray-400 italic">Cause: <?= htmlspecialchars($analysis['cause']) ?></p>
                <p class="text-sm text-gray-500 leading-relaxed"><?= htmlspecialchars($analysis['description']) ?></p>
                <div class="bg-green-50 border border-green-100 rounded-xl p-3">
                    <p class="text-xs font-medium text-green-700 mb-0.5">💊 Suggested Action:</p>
                    <p class="text-sm text-green-800"><?= htmlspecialchars($analysis['advice']) ?></p>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                        <span>Confidence Level</span>
                        <span class="font-semibold text-green-700"><?= $analysis['confidence'] ?>%</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="bar-fill h-full bg-green-500 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Harvest Readiness -->
    <?php
    $harvest = $analysis['harvest'];
    $h_bg    = match($harvest['color']) { 'green' => 'bg-green-50 border-green-200', 'yellow' => 'bg-yellow-50 border-yellow-200', 'red' => 'bg-red-50 border-red-200', 'blue' => 'bg-blue-50 border-blue-200', default => 'bg-gray-50 border-gray-200' };
    $h_title = match($harvest['color']) { 'green' => 'text-green-800', 'yellow' => 'text-yellow-800', 'red' => 'text-red-800', 'blue' => 'text-blue-800', default => 'text-gray-700' };
    $h_text  = match($harvest['color']) { 'green' => 'text-green-700', 'yellow' => 'text-yellow-700', 'red' => 'text-red-700', 'blue' => 'text-blue-700', default => 'text-gray-500' };
    $h_bar   = match($harvest['color']) { 'green' => 'bg-green-500', 'yellow' => 'bg-yellow-400', 'red' => 'bg-red-500', 'blue' => 'bg-blue-400', default => 'bg-gray-300' };
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 fade-up delay-3">
        <div class="bg-green-800 px-4 py-3 flex items-center gap-2">
            <span>🌾</span><span class="text-white text-sm font-medium">Harvest Readiness Assessment</span>
            <?php if (!empty($growth_stage)): ?>
                <span class="ml-auto text-xs bg-white/20 text-white px-2 py-0.5 rounded-full">Stage: <?= ucfirst(htmlspecialchars($growth_stage)) ?></span>
            <?php endif; ?>
        </div>
        <div class="p-5">
            <div class="rounded-xl border p-4 mb-4 <?= $h_bg ?>">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-2xl"><?= $harvest['icon'] ?></span>
                    <h3 class="font-semibold text-base <?= $h_title ?>"><?= htmlspecialchars($harvest['title']) ?></h3>
                </div>
                <p class="text-sm <?= $h_text ?> leading-relaxed"><?= htmlspecialchars($harvest['message']) ?></p>
            </div>
            <?php if ($harvest['progress'] > 0): ?>
            <div class="mb-4">
                <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                    <span>Maturity Progress</span><span class="font-semibold"><?= $harvest['progress'] ?>%</span>
                </div>
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full <?= $h_bar ?>" style="width: <?= $harvest['progress'] ?>%; transition: width 1.2s ease;"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-1.5 px-0.5">
                    <span>🌱 Seedling</span><span>🌿 Tillering</span><span>🌸 Flowering</span><span>🌾 Ripening</span><span>🟡 Harvest</span>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($analysis['variety_info'])): ?>
            <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-green-800 mb-3 flex items-center gap-1.5">
                    🌾 Variety Profile: <span class="font-bold"><?= htmlspecialchars($analysis['variety_info']['full_name']) ?></span>
                </p>
                <div class="grid grid-cols-2 gap-3 mb-2">
                    <div class="bg-green-100 rounded-lg p-2.5">
                        <p class="text-xs font-semibold text-green-700 mb-1.5">✅ Strengths</p>
                        <?php foreach ($analysis['variety_info']['strengths'] as $s): ?><p class="text-xs text-green-700">· <?= htmlspecialchars($s) ?></p><?php endforeach; ?>
                    </div>
                    <div class="bg-red-50 rounded-lg p-2.5">
                        <p class="text-xs font-semibold text-red-600 mb-1.5">⚠️ Watch for</p>
                        <?php foreach ($analysis['variety_info']['weaknesses'] as $w): ?><p class="text-xs text-red-600">· <?= htmlspecialchars($w) ?></p><?php endforeach; ?>
                    </div>
                </div>
                <p class="text-xs text-gray-400">📅 Expected maturity: <strong><?= $analysis['variety_info']['maturity'] ?> days</strong><?php if ($days_planted > 0): ?> · Day <strong><?= $days_planted ?></strong> (<?= max(0, $analysis['variety_info']['maturity'] - $days_planted) ?> days remaining)<?php endif; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Weather -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 fade-up delay-4">
        <div class="bg-yellow-200 px-4 py-3 flex items-center gap-2">
            <span>🌤️</span>
            <span class="text-yellow-800 font-medium text-sm">Weather in <?= htmlspecialchars($location) ?></span>
            <?php if ($weather['success']): ?><span class="ml-auto text-xs bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">✅ Live data</span><?php endif; ?>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-3 gap-4 text-center mb-4">
                <div class="bg-yellow-50 rounded-xl p-3 border border-yellow-100"><div class="text-2xl mb-1">🌡️</div><div class="text-xl font-bold text-gray-700"><?= $weather['temp'] ?>°C</div><div class="text-xs text-gray-400">Feels <?= $weather['feels_like'] ?>°C</div></div>
                <div class="bg-yellow-50 rounded-xl p-3 border border-yellow-100"><div class="text-2xl mb-1">💧</div><div class="text-xl font-bold text-gray-700"><?= $weather['humidity'] ?>%</div><div class="text-xs text-gray-400">Humidity</div></div>
                <div class="bg-yellow-50 rounded-xl p-3 border border-yellow-100"><div class="text-2xl mb-1"><?= $weather['icon'] ?></div><div class="text-sm font-bold text-gray-700"><?= htmlspecialchars($weather['description']) ?></div><div class="text-xs text-gray-400">💨 <?= $weather['wind_speed'] ?> m/s</div></div>
            </div>
            <?php
            $alert = $weather['farming_alert'];
            $ac = match($alert['type']) { 'danger' => 'bg-red-50 border-red-200 text-red-800', 'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800', 'good' => 'bg-green-50 border-green-200 text-green-800', default => 'bg-blue-50 border-blue-200 text-blue-800' };
            ?>
            <div class="rounded-xl border p-3.5 flex items-start gap-3 <?= $ac ?>">
                <span class="text-xl"><?= $alert['icon'] ?></span>
                <div><p class="text-xs font-semibold uppercase tracking-wide mb-0.5">Farming Alert</p><p class="text-sm leading-relaxed"><?= htmlspecialchars($alert['message']) ?></p></div>
            </div>
            <?php if (!$weather['success']): ?><p class="text-xs text-gray-400 text-center mt-2">⚠️ Using estimated data — live weather unavailable</p><?php endif; ?>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 fade-up delay-5">
        <div class="bg-green-700 px-4 py-3 flex items-center gap-2">
            <span>💡</span><span class="text-white text-sm font-medium">Smart Rice Farming Recommendations</span>
            <span class="ml-auto text-xs bg-white/20 text-white px-2 py-0.5 rounded-full"><?= count($recommendations) ?> tips</span>
        </div>
        <div class="p-5 space-y-3">
            <?php foreach ($recommendations as $i => $rec): ?>
                <div class="flex items-start gap-3 bg-green-50 border border-green-100 rounded-xl p-3.5">
                    <span class="text-xs font-bold text-green-400 mt-0.5 w-5 text-center flex-shrink-0"><?= $i+1 ?></span>
                    <p class="text-sm text-green-800"><?= htmlspecialchars($rec) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-3 pt-1 fade-up delay-5">
        <a href="<?= url('crop-assistant') ?>" class="flex-1 bg-green-700 hover:bg-green-600 text-white font-medium py-3 rounded-xl text-sm text-center transition-colors flex items-center justify-center gap-2">🔄 Analyze Another Plant</a>
        <a href="<?= url('dashboard') ?>" class="flex-1 bg-white hover:bg-gray-50 text-gray-700 font-medium py-3 rounded-xl text-sm text-center transition-colors border border-gray-200 flex items-center justify-center gap-2">📊 My Dashboard</a>
    </div>

    <?php endif; ?>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-gray-400">
        <div class="flex items-center gap-2"><span>🌾</span><span>Smart Crop Assistant — Rice Health Monitoring System</span></div>
        <div>Built with LavaLite Framework &amp; Tailwind CSS</div>
    </div>
</footer>
</body>
</html>