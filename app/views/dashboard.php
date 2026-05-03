<?php
/**
 * SMART CROP ASSISTANT - Farmer Dashboard
 * File: app/views/dashboard.php
 *
 * Personal dashboard with Chart.js analytics.
 * Shows only the logged-in farmer's own data.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id'])) {
    header('Location: ' . url('login'));
    exit;
}

$user    = $_SESSION['user'];
$user_id = $user['id'];

// =============================================
// FETCH THIS FARMER'S DATA
// =============================================
try {
    // Total scans
    $total_obj = db()->table('scans')
                     ->where('user_id', $user_id)
                     ->select_count('id', 'total')
                     ->get();
    $total_scans = $total_obj['total'] ?? 0;

    // Healthy count
    $healthy_obj = db()->table('scans')
                       ->where('user_id', $user_id)
                       ->where('status', 'Healthy')
                       ->select_count('id', 'total')
                       ->get();
    $healthy_count = $healthy_obj['total'] ?? 0;

    // Disease count
    $disease_obj = db()->table('scans')
                       ->where('user_id', $user_id)
                       ->where('status', 'Disease Detected')
                       ->select_count('id', 'total')
                       ->get();
    $disease_count = $disease_obj['total'] ?? 0;

    $warning_count = max(0, $total_scans - $healthy_count - $disease_count);

    // Recent 5 scans
    $recent_scans = db()->table('scans')
                        ->where('user_id', $user_id)
                        ->order_by('created_at', 'DESC')
                        ->limit(5)
                        ->get_all();
    if (!$recent_scans) $recent_scans = [];

    // Last disease found
    $last_disease = null;
    if ($disease_count > 0) {
        $last_disease = db()->table('scans')
                            ->where('user_id', $user_id)
                            ->where('status', 'Disease Detected')
                            ->order_by('created_at', 'DESC')
                            ->get();
    }

    // ---- CHART DATA ----

    // 1. Scans per day — last 7 days
    $all_scans = db()->table('scans')
                     ->where('user_id', $user_id)
                     ->order_by('created_at', 'DESC')
                     ->get_all();
    if (!$all_scans) $all_scans = [];

    // Build last 7 days labels + counts
    $days_labels  = [];
    $days_counts  = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('M j', strtotime("-{$i} days"));
        $key  = date('Y-m-d', strtotime("-{$i} days"));
        $days_labels[] = $date;
        $days_counts[$key] = 0;
    }
    foreach ($all_scans as $s) {
        $d = substr($s['created_at'], 0, 10);
        if (isset($days_counts[$d])) {
            $days_counts[$d]++;
        }
    }

    // 2. Condition breakdown — top conditions
    $condition_counts = [];
    foreach ($all_scans as $s) {
        $c = $s['condition_name'];
        $condition_counts[$c] = ($condition_counts[$c] ?? 0) + 1;
    }
    arsort($condition_counts);
    $top_conditions = array_slice($condition_counts, 0, 5, true);

} catch (Exception $e) {
    error_log('Dashboard DB error: ' . $e->getMessage());
    $total_scans = $healthy_count = $disease_count = $warning_count = 0;
    $recent_scans = $all_scans = [];
    $last_disease = null;
    $days_labels = $days_counts = $top_conditions = [];
}

// Weather
require_once __DIR__ . '/weather.php';
if (!defined('WEATHER_API_KEY')) {
    define('WEATHER_API_KEY', '7967f548bdab9a42f7448c05c9635a25');
}
$weather = getWeatherData($user['location'], WEATHER_API_KEY);

$health_score = $total_scans > 0
    ? round(($healthy_count / $total_scans) * 100)
    : 0;

// Prepare chart data as JSON for JavaScript
$chart_days_labels     = json_encode(array_values($days_labels));
$chart_days_data       = json_encode(array_values($days_counts));
$chart_donut_labels    = json_encode(['Healthy', 'Disease Detected', 'Needs Attention']);
$chart_donut_data      = json_encode([$healthy_count, $disease_count, $warning_count]);
$chart_disease_labels  = json_encode(array_keys($top_conditions));
$chart_disease_data    = json_encode(array_values($top_conditions));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — <?= htmlspecialchars($user['full_name']) ?></title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Chart.js compiled bundle -->
    <script src="<?= base_url() ?>/public/js/main.js"></script>
    <style>
        body     { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3 { font-family: 'Playfair Display', Georgia, serif; }
        .nav-glass {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .fade-up { opacity: 0; animation: fadeUp 0.5s ease forwards; }
        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.10s; }
        .delay-3 { animation-delay: 0.15s; }
        .delay-4 { animation-delay: 0.20s; }
        .delay-5 { animation-delay: 0.25s; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .scan-row { transition: background 0.15s; }
        .scan-row:hover { background: #f8fdf9; }
        .health-bar { transition: width 1s ease; }
        .chart-container { position: relative; }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- NAV -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm">
                <span class="text-lg">🌾</span>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900"
                     style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">Rice Health Monitoring</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('dashboard') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-green-50
                      text-green-700 font-medium text-sm border border-green-200">
                📊 Dashboard
            </a>
            <a href="<?= url('crop-assistant') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                      text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                🔍 Analyze
            </a>
            <a href="<?= url('plots') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                      text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                🗺️ Field Plots
            </a>
            <a href="<?= url('history') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                      text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">
                📋 History
            </a>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <a href="<?= url('admin') ?>"
               class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                      text-purple-600 hover:bg-purple-50 font-medium text-sm transition-colors">
                👑 Admin
            </a>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden md:block">
                <div class="text-sm font-medium text-gray-800"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="text-xs text-gray-400"><?= htmlspecialchars($user['farm_name']) ?></div>
            </div>
            <div class="w-9 h-9 bg-green-100 border border-green-200 rounded-xl
                        flex items-center justify-center text-lg">👨‍🌾</div>
            <a href="<?= url('logout') ?>"
               class="text-xs text-gray-400 hover:text-red-500 transition-colors
                      px-2 py-1 rounded-lg hover:bg-red-50">
                Logout
            </a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-green-900 px-4 py-10">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-start
                md:items-center justify-between gap-4">
        <div>
            <p class="text-green-400 text-sm font-medium mb-1">Welcome back 👋</p>
            <h1 class="text-3xl font-bold text-white mb-1">
                <?= htmlspecialchars($user['full_name']) ?>
            </h1>
            <p class="text-green-300 text-sm">
                🏡 <?= htmlspecialchars($user['farm_name']) ?>
                &nbsp;·&nbsp; 📍 <?= htmlspecialchars($user['location']) ?>
                &nbsp;·&nbsp; 🕐 <?= date('F j, Y') ?>
            </p>
        </div>
        <a href="<?= url('crop-assistant') ?>"
           class="flex items-center gap-2 bg-green-600 hover:bg-green-500
                  text-white font-semibold px-5 py-3 rounded-xl text-sm
                  transition-colors shadow-lg">
            <span>🔍</span> Analyze New Plant
        </a>
    </div>
</div>

<!-- MAIN -->
<main class="max-w-6xl mx-auto px-4 py-8">

    <!-- STAT CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 fade-up delay-1">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-xl border border-green-200">🔬</div>
                <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full">Total</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1"><?= $total_scans ?></div>
            <div class="text-xs text-gray-500">Plant scans</div>
        </div>
        <div class="stat-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 fade-up delay-2">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-xl border border-green-200">✅</div>
                <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Good</span>
            </div>
            <div class="text-3xl font-bold text-green-600 mb-1"><?= $healthy_count ?></div>
            <div class="text-xs text-gray-500">Healthy plants</div>
        </div>
        <div class="stat-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 fade-up delay-3">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-xl border border-red-100">🚨</div>
                <span class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded-full">Alert</span>
            </div>
            <div class="text-3xl font-bold text-red-500 mb-1"><?= $disease_count ?></div>
            <div class="text-xs text-gray-500">Disease detected</div>
        </div>
        <div class="stat-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5 fade-up delay-4">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-yellow-50 rounded-xl flex items-center justify-center text-xl border border-yellow-100">📈</div>
                <span class="text-xs text-yellow-700 bg-yellow-50 px-2 py-1 rounded-full">Score</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1"><?= $health_score ?>%</div>
            <div class="text-xs text-gray-500">Farm health score</div>
            <?php if ($total_scans > 0): ?>
            <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="health-bar h-full rounded-full
                             <?= $health_score >= 70 ? 'bg-green-500' : ($health_score >= 40 ? 'bg-yellow-400' : 'bg-red-500') ?>"
                     style="width: <?= $health_score ?>%"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Line Chart: Scans per day -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 fade-up delay-3">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800 text-sm">📈 My Scan Activity</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Number of scans per day — last 7 days</p>
                </div>
                <?php if ($total_scans === 0): ?>
                    <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full">No data yet</span>
                <?php endif; ?>
            </div>
            <?php if ($total_scans > 0): ?>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="lineChart"></canvas>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-48 text-gray-300">
                    <div class="text-4xl mb-2">📊</div>
                    <p class="text-sm">Analyze plants to see your activity chart</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Donut Chart: Health breakdown -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 fade-up delay-4">
            <div class="mb-4">
                <h3 class="font-semibold text-gray-800 text-sm">🥧 Health Breakdown</h3>
                <p class="text-xs text-gray-400 mt-0.5">Overall plant condition summary</p>
            </div>
            <?php if ($total_scans > 0): ?>
                <div class="chart-container" style="height: 180px;">
                    <canvas id="donutChart"></canvas>
                </div>
                <!-- Legend -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                            Healthy
                        </span>
                        <span class="font-semibold text-gray-700"><?= $healthy_count ?></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                            Disease Detected
                        </span>
                        <span class="font-semibold text-gray-700"><?= $disease_count ?></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span>
                            Needs Attention
                        </span>
                        <span class="font-semibold text-gray-700"><?= $warning_count ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-48 text-gray-300">
                    <div class="text-4xl mb-2">🥧</div>
                    <p class="text-sm text-center">No scans yet</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- BOTTOM ROW: Disease bar chart + Recent scans + Weather -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Bar Chart: Top conditions -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 fade-up delay-4">
            <div class="mb-4">
                <h3 class="font-semibold text-gray-800 text-sm">🍄 My Disease History</h3>
                <p class="text-xs text-gray-400 mt-0.5">Most detected conditions in your farm</p>
            </div>
            <?php if (!empty($top_conditions)): ?>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="barChart"></canvas>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-48 text-gray-300">
                    <div class="text-4xl mb-2">🍄</div>
                    <p class="text-sm text-center">No disease data yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Scans -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden fade-up delay-4">
            <div class="bg-green-700 px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-white">📋</span>
                    <span class="text-white font-medium text-sm">Recent Scans</span>
                </div>
                <a href="<?= url('history') ?>"
                   class="text-xs text-green-200 hover:text-white transition-colors">
                    View all →
                </a>
            </div>
            <?php if (empty($recent_scans)): ?>
                <div class="p-8 text-center">
                    <div class="text-3xl mb-2">🌾</div>
                    <p class="text-gray-400 text-sm mb-3">No scans yet</p>
                    <a href="<?= url('crop-assistant') ?>"
                       class="inline-flex items-center gap-1 bg-green-700 hover:bg-green-600
                              text-white text-xs font-medium px-4 py-2 rounded-xl transition-colors">
                        🔍 Analyze First Plant
                    </a>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-50">
                    <?php foreach ($recent_scans as $scan):
                        $badge = match($scan['status']) {
                            'Healthy'          => 'bg-green-100 text-green-700 border-green-200',
                            'Disease Detected' => 'bg-red-50    text-red-700   border-red-200',
                            default            => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                        };
                        $sicon = match($scan['status']) {
                            'Healthy' => '✅', 'Disease Detected' => '🚨', default => '⚠️'
                        };
                        $img = base_url() . '/public/uploads/' . htmlspecialchars($scan['image_filename']);
                    ?>
                    <div class="scan-row px-4 py-3 flex items-center gap-3">
                        <img src="<?= $img ?>" alt="scan"
                             class="w-10 h-10 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 mb-0.5">
                                <span class="text-xs font-medium text-gray-800 truncate">
                                    <?= htmlspecialchars($scan['plant_name']) ?>
                                </span>
                                <span class="text-xs px-1.5 py-0.5 rounded-full border flex-shrink-0 <?= $badge ?>">
                                    <?= $sicon ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 truncate">
                                <?= htmlspecialchars($scan['condition_name']) ?>
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-xs font-semibold text-gray-700"><?= $scan['confidence'] ?>%</div>
                            <div class="text-xs text-gray-400"><?= date('M j', strtotime($scan['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                    <a href="<?= url('history') ?>"
                       class="text-sm text-green-600 hover:text-green-700 font-medium transition-colors">
                        View all <?= $total_scans ?> scans →
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Weather + Health Summary + Quick Actions -->
        <div class="space-y-4 fade-up delay-5">

            <!-- Weather -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-yellow-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span>🌤️</span>
                        <span class="text-yellow-800 font-medium text-sm">
                            <?= htmlspecialchars($user['location']) ?>
                        </span>
                    </div>
                    <?php if ($weather['success']): ?>
                        <span class="text-xs bg-green-100 text-green-700
                                     border border-green-200 px-2 py-0.5 rounded-full">✅ Live</span>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="text-center bg-yellow-50 rounded-xl p-2.5 border border-yellow-100">
                            <div class="text-xl mb-0.5">🌡️</div>
                            <div class="text-base font-bold text-gray-700"><?= $weather['temp'] ?>°</div>
                            <div class="text-xs text-gray-400">Temp</div>
                        </div>
                        <div class="text-center bg-yellow-50 rounded-xl p-2.5 border border-yellow-100">
                            <div class="text-xl mb-0.5">💧</div>
                            <div class="text-base font-bold text-gray-700"><?= $weather['humidity'] ?>%</div>
                            <div class="text-xs text-gray-400">Humidity</div>
                        </div>
                        <div class="text-center bg-yellow-50 rounded-xl p-2.5 border border-yellow-100">
                            <div class="text-xl mb-0.5"><?= $weather['icon'] ?></div>
                            <div class="text-xs font-medium text-gray-600 leading-tight">
                                <?= htmlspecialchars($weather['condition']) ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    $alert = $weather['farming_alert'];
                    $ac = match($alert['type']) {
                        'danger'  => 'bg-red-50    border-red-200    text-red-700',
                        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                        'good'    => 'bg-green-50  border-green-200  text-green-700',
                        default   => 'bg-blue-50   border-blue-200   text-blue-700',
                    };
                    ?>
                    <div class="rounded-lg border p-2.5 text-xs <?= $ac ?> flex items-start gap-2">
                        <span><?= $alert['icon'] ?></span>
                        <span class="leading-relaxed"><?= htmlspecialchars($alert['message']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Farm health + last disease -->
            <?php if ($total_scans > 0): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-semibold text-gray-700 mb-3">📊 Farm health overview</p>
                <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                    <span>Overall health</span>
                    <span class="font-semibold <?= $health_score >= 70 ? 'text-green-600' : ($health_score >= 40 ? 'text-yellow-600' : 'text-red-500') ?>">
                        <?= $health_score ?>%
                    </span>
                </div>
                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden mb-3">
                    <div class="health-bar h-full rounded-full
                                <?= $health_score >= 70 ? 'bg-green-500' : ($health_score >= 40 ? 'bg-yellow-400' : 'bg-red-500') ?>"
                         style="width: <?= $health_score ?>%"></div>
                </div>
                <?php if ($last_disease): ?>
                <div class="bg-red-50 border border-red-100 rounded-xl p-3">
                    <p class="text-xs font-medium text-red-600 mb-1">⚠️ Latest disease:</p>
                    <p class="text-xs text-red-700"><?= htmlspecialchars($last_disease['condition_name']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Quick actions -->
            <div class="bg-green-50 border border-green-100 rounded-2xl p-4">
                <p class="text-xs font-semibold text-green-800 mb-3">⚡ Quick actions</p>
                <div class="space-y-2">
                    <a href="<?= url('crop-assistant') ?>"
                       class="flex items-center gap-2 text-sm text-green-700
                              hover:text-green-900 transition-colors font-medium">
                        <span class="w-7 h-7 bg-green-700 text-white rounded-lg
                                     flex items-center justify-center text-xs">🔍</span>
                        Analyze a plant
                    </a>
                    <a href="<?= url('history') ?>"
                       class="flex items-center gap-2 text-sm text-green-700
                              hover:text-green-900 transition-colors font-medium">
                        <span class="w-7 h-7 bg-green-100 border border-green-200 rounded-lg
                                     flex items-center justify-center text-xs">📋</span>
                        View scan history
                    </a>
                </div>
            </div>

        </div>
    </div>

</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center
                justify-between gap-2 text-xs text-gray-400">
        <div class="flex items-center gap-2">
            <span>🌾</span>
            <span>Smart Crop Assistant — Rice Health Monitoring System</span>
        </div>
        <div>Built with LavaLite Framework &amp; Tailwind CSS</div>
    </div>
</footer>

<!-- ============================================================
     CHART.JS INITIALIZATION
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- 1. LINE CHART — Scan activity last 7 days ----
    const lineCanvas = document.getElementById('lineChart');
    if (lineCanvas) {
        new Chart(lineCanvas, {
            type: 'line',
            data: {
                labels: <?= $chart_days_labels ?>,
                datasets: [{
                    label: 'Scans',
                    data: <?= $chart_days_data ?>,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#16a34a',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} scan${ctx.parsed.y !== 1 ? 's' : ''}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // ---- 2. DONUT CHART — Health breakdown ----
    const donutCanvas = document.getElementById('donutChart');
    if (donutCanvas) {
        new Chart(donutCanvas, {
            type: 'doughnut',
            data: {
                labels: <?= $chart_donut_labels ?>,
                datasets: [{
                    data: <?= $chart_donut_data ?>,
                    backgroundColor: ['#22c55e', '#ef4444', '#facc15'],
                    borderColor: ['#16a34a', '#dc2626', '#eab308'],
                    borderWidth: 2,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed}`
                        }
                    }
                }
            }
        });
    }

    // ---- 3. BAR CHART — Top disease conditions ----
    const barCanvas = document.getElementById('barChart');
    if (barCanvas) {
        new Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: <?= $chart_disease_labels ?>,
                datasets: [{
                    label: 'Times detected',
                    data: <?= $chart_disease_data ?>,
                    backgroundColor: [
                        'rgba(239,68,68,0.75)',
                        'rgba(234,179,8,0.75)',
                        'rgba(249,115,22,0.75)',
                        'rgba(168,85,247,0.75)',
                        'rgba(59,130,246,0.75)',
                    ],
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: {
                        ticks: {
                            font: { size: 9 },
                            callback: function(val, idx) {
                                // Truncate long labels
                                const label = this.getLabelForValue(val);
                                return label.length > 18 ? label.slice(0,18) + '…' : label;
                            }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

});
</script>

</body>
</html>