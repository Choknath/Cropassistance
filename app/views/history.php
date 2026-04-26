<?php
/**
 * SMART CROP ASSISTANT - My Scan History
 * File: app/views/history.php
 *
 * ✅ FIXED: Only shows scans belonging to the logged-in user.
 * Uses $_SESSION['user']['id'] to filter the database query.
 * Protected by auth middleware.
 */

// =============================================
// START SESSION + GET LOGGED-IN USER
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the logged-in user from session
// The auth middleware already ensures this exists,
// but we double-check just to be safe
if (!isset($_SESSION['user']['id'])) {
    header('Location: ' . url('login'));
    exit;
}

$user    = $_SESSION['user'];
$user_id = $user['id']; // ← This is the key filter!

// =============================================
// FETCH ONLY THIS USER'S SCANS
// =============================================
try {
    // ✅ THE FIX: ->where('user_id', $user_id)
    // This ensures we ONLY get scans that belong
    // to the currently logged-in farmer.
    $scans = db()->table('scans')
                 ->where('user_id', $user_id)
                 ->order_by('created_at', 'DESC')
                 ->get_all();

    if (!$scans) {
        $scans = [];
    }

} catch (Exception $e) {
    $scans = [];
    error_log('History DB error: ' . $e->getMessage());
}

// =============================================
// COUNT STATISTICS FOR THIS USER ONLY
// =============================================
$total_scans   = count($scans);
$healthy_count = 0;
$disease_count = 0;
$warning_count = 0;

foreach ($scans as $scan) {
    if ($scan['status'] === 'Healthy') {
        $healthy_count++;
    } elseif ($scan['status'] === 'Disease Detected') {
        $disease_count++;
    } else {
        $warning_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Scan History — Smart Crop Assistant</title>
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
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .scan-row { transition: background 0.15s; }
        .scan-row:hover { background: #f8fdf9; }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- ============================================================
     NAVIGATION
     ============================================================ -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-5xl mx-auto px-5 py-3 flex items-center justify-between">

        <!-- Brand -->
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3 group">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center
                        group-hover:bg-green-600 transition-colors shadow-sm">
                <span class="text-lg">🌾</span>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900"
                     style="font-family:'Playfair Display',serif">
                    Smart Crop Assistant
                </div>
                <div class="text-xs text-gray-400">Rice Health Monitoring</div>
            </div>
        </a>

        <!-- Nav links -->
        <div class="flex items-center gap-1">
            <a href="<?= url('dashboard') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                      text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                📊 Dashboard
            </a>
            <a href="<?= url('crop-assistant') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                      text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                🔍 Analyze
            </a>
            <a href="<?= url('history') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-green-50
                      text-green-700 font-medium text-sm border border-green-200">
                📋 History
            </a>
            <a href="<?= url('logout') ?>"
               class="text-xs text-gray-400 hover:text-red-500 transition-colors
                      px-2 py-1 rounded-lg hover:bg-red-50 ml-1">
                Logout
            </a>
        </div>

    </div>
</nav>


<!-- ============================================================
     PAGE HEADER
     ============================================================ -->
<div class="bg-green-900 text-white py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <p class="text-green-300 text-sm font-medium mb-1">
            🌾 <?= htmlspecialchars($user['farm_name']) ?>
        </p>
        <h1 class="text-3xl font-bold mb-1">My Scan History</h1>
        <p class="text-green-300 text-sm">
            Showing scans for
            <strong class="text-white"><?= htmlspecialchars($user['full_name']) ?></strong>
            — most recent first
        </p>
    </div>
</div>


<!-- ============================================================
     SUMMARY STATS — only this user's numbers
     ============================================================ -->
<div class="max-w-5xl mx-auto px-4 -mt-5 mb-6 relative z-10">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">

        <!-- Total scans -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center fade-up">
            <div class="text-3xl font-bold text-green-700"><?= $total_scans ?></div>
            <div class="text-xs text-gray-500 mt-1">🌾 My Total Scans</div>
        </div>

        <!-- Healthy -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center fade-up">
            <div class="text-3xl font-bold text-green-600"><?= $healthy_count ?></div>
            <div class="text-xs text-gray-500 mt-1">✅ Healthy</div>
        </div>

        <!-- Disease detected -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center fade-up">
            <div class="text-3xl font-bold text-red-500"><?= $disease_count ?></div>
            <div class="text-xs text-gray-500 mt-1">🚨 Disease Found</div>
        </div>

        <!-- Needs attention -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center fade-up">
            <div class="text-3xl font-bold text-yellow-500"><?= $warning_count ?></div>
            <div class="text-xs text-gray-500 mt-1">⚠️ Needs Attention</div>
        </div>

    </div>
</div>


<!-- ============================================================
     SCAN HISTORY LIST
     ============================================================ -->
<main class="max-w-5xl mx-auto px-4 pb-16">

    <?php if (empty($scans)): ?>
    <!-- ---- EMPTY STATE ---- -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center fade-up">
        <div class="text-6xl mb-4">🌾</div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">No scans yet</h2>
        <p class="text-gray-400 text-sm mb-2">
            You haven't analyzed any rice plants yet.
        </p>
        <p class="text-gray-400 text-sm mb-6">
            Your scan history will appear here after your first analysis.
        </p>
        <a href="<?= url('crop-assistant') ?>"
           class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-600
                  text-white font-medium px-6 py-3 rounded-xl text-sm transition-colors">
            🔍 Analyze Your First Plant
        </a>
    </div>

    <?php else: ?>
    <!-- ---- SCANS LIST ---- -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up">

        <!-- Table header -->
        <div class="bg-green-700 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>📋</span>
                <span class="text-white font-medium text-sm">
                    <?= $total_scans ?> Scan<?= $total_scans !== 1 ? 's' : '' ?> recorded
                </span>
                <!-- Small badge confirming it's personal data -->
                <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full ml-1">
                    👨‍🌾 <?= htmlspecialchars($user['full_name']) ?> only
                </span>
            </div>
            <a href="<?= url('crop-assistant') ?>"
               class="bg-white/20 hover:bg-white/30 text-white text-xs
                      font-medium px-3 py-1.5 rounded-lg transition-colors">
                + New Scan
            </a>
        </div>

        <!-- Scan rows -->
        <div class="divide-y divide-gray-100">
            <?php foreach ($scans as $index => $scan):

                /* Pick badge colors based on status */
                $badge = match($scan['status']) {
                    'Healthy'          => 'bg-green-100  text-green-700  border-green-200',
                    'Disease Detected' => 'bg-red-50     text-red-700    border-red-200',
                    default            => 'bg-yellow-50  text-yellow-700 border-yellow-200',
                };
                $status_icon = match($scan['status']) {
                    'Healthy'          => '✅',
                    'Disease Detected' => '🚨',
                    default            => '⚠️',
                };

                /* Build image URL */
                $img_url   = base_url() . '/public/uploads/'
                           . htmlspecialchars($scan['image_filename']);

                /* Format date nicely */
                $scan_date = date('M j, Y', strtotime($scan['created_at']));
                $scan_time = date('g:i A',  strtotime($scan['created_at']));
            ?>
            <div class="scan-row p-5">
                <div class="flex items-start gap-4">

                    <!-- Thumbnail -->
                    <div class="flex-shrink-0">
                        <img src="<?= $img_url ?>"
                             alt="Scan <?= $index + 1 ?>"
                             class="w-16 h-16 rounded-xl object-cover border border-gray-200">
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">

                        <!-- Plant name + status badge -->
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-semibold text-gray-800 text-sm">
                                🌾 <?= htmlspecialchars($scan['plant_name']) ?>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs
                                         font-medium border <?= $badge ?>">
                                <?= $status_icon ?>
                                <?= htmlspecialchars($scan['status']) ?>
                            </span>
                        </div>

                        <!-- Condition -->
                        <p class="text-sm text-gray-600 mb-1">
                            🔬 <?= htmlspecialchars($scan['condition_name']) ?>
                        </p>

                        <!-- Meta info row -->
                        <div class="flex items-center gap-3 text-xs text-gray-400 flex-wrap">
                            <span>📍 <?= htmlspecialchars($scan['location']) ?></span>
                            <span>📅 <?= $scan_date ?></span>
                            <span>🕐 <?= $scan_time ?></span>
                            <span>🎯 <?= $scan['confidence'] ?>% confidence</span>
                            <span>🌡️ <?= $scan['weather_temp'] ?>°C</span>
                            <span>💧 <?= $scan['weather_humidity'] ?>% humidity</span>
                        </div>

                    </div>

                    <!-- Severity (right side) -->
                    <div class="flex-shrink-0 text-right">
                        <span class="text-xs text-gray-400 block mb-1">Severity</span>
                        <span class="text-xs font-semibold px-2 py-1
                                     bg-gray-100 rounded-lg text-gray-600">
                            <?= htmlspecialchars($scan['severity']) ?>
                        </span>
                    </div>

                </div>

                <!-- Advice preview -->
                <div class="mt-3 ml-20 bg-green-50 border border-green-100 rounded-xl px-3 py-2">
                    <p class="text-xs text-green-700">
                        💊 <?= htmlspecialchars($scan['advice']) ?>
                    </p>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </div>
    <?php endif; ?>

</main>


<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center
                justify-between gap-2 text-xs text-gray-400">
        <div class="flex items-center gap-2">
            <span>🌾</span>
            <span>Smart Crop Assistant — Rice Health Monitoring System</span>
        </div>
        <div>Built with LavaLite Framework &amp; Tailwind CSS</div>
    </div>
</footer>

</body>
</html>
