<?php
/**
 * SMART CROP ASSISTANT — Admin Dashboard
 * File: app/views/admin/dashboard.php
 * Route: GET admin
 * Access: admin role only (authorize middleware)
 *
 * Shows cross-farm analytics: all users, all scans,
 * disease trends, and FarmLog summary.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']['id'])) { header('Location: ' . url('login')); exit; }
if (($_SESSION['user']['role'] ?? '') !== 'admin') { http_response_code(403); echo '<p style="font-family:sans-serif;padding:40px;text-align:center">403 — Admin access only. <a href="'.url('dashboard').'">Go back</a></p>'; exit; }

$admin = $_SESSION['user'];

try {
    // ── Platform totals ──────────────────────────────────
    $total_users = db()->table('users')->select_count('id','c')->get()['c'] ?? 0;
    $total_scans = db()->table('scans')->select_count('id','c')->get()['c'] ?? 0;
    $total_crops = db()->table('rice_crops')->select_count('id','c')->get()['c'] ?? 0;
    $total_harvests = db()->table('harvest_records')->select_count('id','c')->get()['c'] ?? 0;

    // ── Disease breakdown (all users) ────────────────────
    $all_scans = db()->table('scans')->order_by('created_at','DESC')->get_all();
    if (!$all_scans) $all_scans = [];

    $disease_counts = [];
    $healthy_count  = 0;
    foreach ($all_scans as $s) {
        if ($s['status'] === 'Healthy') { $healthy_count++; continue; }
        $c = $s['condition_name'];
        $disease_counts[$c] = ($disease_counts[$c] ?? 0) + 1;
    }
    arsort($disease_counts);
    $top_diseases = array_slice($disease_counts, 0, 5, true);

    // ── Recent users ─────────────────────────────────────
    $recent_users = db()->table('users')->order_by('created_at','DESC')->limit(8)->get_all();
    if (!$recent_users) $recent_users = [];

    // ── Active crops count ───────────────────────────────
    $active_crops = db()->table('rice_crops')->where('status','active')->select_count('id','c')->get()['c'] ?? 0;

    // ── Harvest total kg ─────────────────────────────────
    $harvest_kg = db()->table('harvest_records')->get_all();
    $total_yield_kg = $harvest_kg ? array_sum(array_column($harvest_kg,'quantity_kg')) : 0;

    // ── Scans per day last 7 days ────────────────────────
    $chart_labels = $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $chart_labels[] = date('M j', strtotime("-{$i} days"));
        $chart_data[date('Y-m-d', strtotime("-{$i} days"))] = 0;
    }
    foreach ($all_scans as $s) {
        $d = substr($s['created_at'], 0, 10);
        if (isset($chart_data[$d])) $chart_data[$d]++;
    }

} catch (Exception $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
    $total_users = $total_scans = $total_crops = $total_harvests = 0;
    $recent_users = $top_diseases = $all_scans = [];
    $healthy_count = $active_crops = $total_yield_kg = 0;
    $chart_labels = $chart_data = [];
}

$disease_rate = $total_scans > 0 ? round((($total_scans - $healthy_count) / $total_scans) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <script src="<?= base_url() ?>/public/js/main.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'DM Sans',sans-serif;}h1,h2,h3{font-family:'Playfair Display',Georgia,serif;}
        .nav-glass{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);}
        .fade-up{opacity:0;animation:fadeUp .5s ease forwards;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
        .stat-card{transition:transform .2s,box-shadow .2s;}.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.08);}
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- NAV -->
<nav class="nav-glass sticky top-0 z-50 border-b border-purple-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-purple-700 rounded-xl flex items-center justify-center shadow-sm"><span class="text-lg">👑</span></div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-purple-900" style="font-family:'Playfair Display',serif">Admin Panel</div>
                <div class="text-xs text-gray-400">Smart Crop Assistant</div>
            </div>
        </a>
        <div class="flex items-center gap-1 flex-wrap">
            <a href="<?= url('admin') ?>" class="px-3 py-2 rounded-lg bg-purple-50 text-purple-700 font-medium text-sm border border-purple-200">👑 Overview</a>
            <a href="<?= url('admin/users') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">👥 Users</a>
            <a href="<?= url('admin/crops') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">🌾 All Crops</a>
            <a href="<?= url('dashboard') ?>" class="px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 My Dashboard</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-red-50 transition-colors ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HEADER -->
<div class="bg-purple-900 text-white py-10 px-4">
    <div class="max-w-6xl mx-auto">
        <p class="text-purple-300 text-sm font-medium mb-1">👑 Admin · <?= htmlspecialchars($admin['full_name']) ?></p>
        <h1 class="text-3xl font-bold mb-1">Platform Overview</h1>
        <p class="text-purple-300 text-sm">Cross-farm analytics — all farmers · <?= date('F j, Y') ?></p>
    </div>
</div>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- STAT CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 fade-up">
        <?php foreach ([
            ['👥', $total_users,   'Registered Farmers', 'bg-purple-100 border-purple-200', 'text-purple-700'],
            ['🔬', $total_scans,   'Total Scans',         'bg-blue-50 border-blue-200',     'text-blue-700'],
            ['🌾', $active_crops,  'Active Crops',        'bg-green-50 border-green-200',   'text-green-700'],
            ['🧺', $total_harvests,'Harvest Records',     'bg-yellow-50 border-yellow-200', 'text-yellow-700'],
        ] as [$icon,$val,$lbl,$bg,$tc]): ?>
        <div class="stat-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 <?= $bg ?> border rounded-xl flex items-center justify-center text-xl"><?= $icon ?></div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1"><?= number_format($val) ?></div>
            <div class="text-xs text-gray-500"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CHARTS ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 fade-up">

        <!-- Scan activity line chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 text-sm mb-1">📈 Platform Scan Activity</h3>
            <p class="text-xs text-gray-400 mb-4">All farmers — last 7 days</p>
            <?php if ($total_scans > 0): ?>
            <div style="height:200px;position:relative;">
                <canvas id="adminLineChart"></canvas>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center h-48 text-gray-300"><div class="text-4xl mb-2">📊</div><p class="text-sm">No scans yet</p></div>
            <?php endif; ?>
        </div>

        <!-- Disease rate donut -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 text-sm mb-1">🔬 Disease Rate</h3>
            <p class="text-xs text-gray-400 mb-4">Platform-wide health summary</p>
            <?php if ($total_scans > 0): ?>
            <div style="height:180px;position:relative;">
                <canvas id="adminDonut"></canvas>
            </div>
            <div class="mt-4 space-y-1.5">
                <div class="flex justify-between text-xs"><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>Healthy</span><span class="font-semibold"><?= $healthy_count ?></span></div>
                <div class="flex justify-between text-xs"><span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>Disease / Stress</span><span class="font-semibold"><?= $total_scans - $healthy_count ?></span></div>
                <div class="flex justify-between text-xs text-gray-400"><span>Disease rate</span><span class="font-semibold text-red-500"><?= $disease_rate ?>%</span></div>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center h-48 text-gray-300"><div class="text-4xl mb-2">🥧</div><p class="text-sm">No data yet</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BOTTOM ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 fade-up">

        <!-- Top diseases bar -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 text-sm mb-1">🍄 Most Common Diseases</h3>
            <p class="text-xs text-gray-400 mb-4">Across all farmers</p>
            <?php if (!empty($top_diseases)): ?>
            <div style="height:200px;position:relative;">
                <canvas id="adminBar"></canvas>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center h-36 text-gray-300"><div class="text-4xl mb-2">🍄</div><p class="text-sm">No disease data yet</p></div>
            <?php endif; ?>
        </div>

        <!-- Recent users -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="bg-purple-700 px-5 py-4 flex items-center justify-between">
                <span class="text-white font-medium text-sm">👥 Recent Farmers</span>
                <a href="<?= url('admin/users') ?>" class="text-xs text-purple-200 hover:text-white transition-colors">View all →</a>
            </div>
            <div class="divide-y divide-gray-50">
                <?php if (empty($recent_users)): ?>
                <div class="p-6 text-center text-gray-400 text-sm">No users yet.</div>
                <?php else: ?>
                <?php foreach ($recent_users as $u): ?>
                <div class="px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 bg-purple-100 border border-purple-200 rounded-xl flex items-center justify-center text-sm flex-shrink-0">👨‍🌾</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($u['full_name']) ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($u['farm_name'] ?? '—') ?> · <?= htmlspecialchars($u['location'] ?? '—') ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <?php if (($u['role'] ?? '') === 'admin'): ?><span class="text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full">admin</span><?php endif; ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?= date('M j', strtotime($u['created_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick admin links -->
    <div class="bg-purple-50 border border-purple-100 rounded-2xl p-5 fade-up">
        <p class="text-xs font-semibold text-purple-800 mb-3">⚡ Admin Quick Actions</p>
        <div class="flex flex-wrap gap-3">
            <a href="<?= url('admin/users') ?>" class="flex items-center gap-2 bg-purple-700 hover:bg-purple-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">👥 Manage Users</a>
            <a href="<?= url('admin/crops') ?>" class="flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">🌾 View All Crops</a>
            <a href="<?= url('crop-assistant') ?>" class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors">🔍 Disease Scanner</a>
        </div>
    </div>

</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4"><div class="max-w-6xl mx-auto text-xs text-gray-400 text-center">Smart Crop Assistant — Admin Panel</div></footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Line chart
    const lc = document.getElementById('adminLineChart');
    if (lc) {
        new Chart(lc, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_values($chart_labels)) ?>,
                datasets: [{ label:'Scans', data: <?= json_encode(array_values($chart_data)) ?>,
                    borderColor:'#7c3aed', backgroundColor:'rgba(124,58,237,.08)',
                    borderWidth:2.5, pointBackgroundColor:'#7c3aed', pointRadius:4, tension:.4, fill:true }]
            },
            options: { responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false} },
                scales:{ y:{beginAtZero:true,ticks:{stepSize:1,font:{size:11}},grid:{color:'rgba(0,0,0,.05)'}}, x:{ticks:{font:{size:11}},grid:{display:false}} } }
        });
    }

    // Donut
    const dc = document.getElementById('adminDonut');
    if (dc) {
        new Chart(dc, {
            type:'doughnut',
            data:{ labels:['Healthy','Disease/Stress'],
                datasets:[{ data:[<?= $healthy_count ?>,<?= $total_scans - $healthy_count ?>],
                    backgroundColor:['#22c55e','#ef4444'], borderWidth:2, hoverOffset:6 }] },
            options:{ responsive:true, maintainAspectRatio:false, cutout:'70%', plugins:{legend:{display:false}} }
        });
    }

    // Bar chart
    const bc = document.getElementById('adminBar');
    if (bc) {
        new Chart(bc, {
            type:'bar',
            data:{ labels: <?= json_encode(array_keys($top_diseases)) ?>,
                datasets:[{ label:'Times', data: <?= json_encode(array_values($top_diseases)) ?>,
                    backgroundColor:['rgba(239,68,68,.75)','rgba(234,179,8,.75)','rgba(249,115,22,.75)','rgba(168,85,247,.75)','rgba(59,130,246,.75)'],
                    borderRadius:8, borderSkipped:false }] },
            options:{ responsive:true, maintainAspectRatio:false, indexAxis:'y',
                plugins:{legend:{display:false}},
                scales:{ x:{beginAtZero:true,ticks:{stepSize:1,font:{size:10}},grid:{color:'rgba(0,0,0,.05)'}},
                    y:{ticks:{font:{size:9}, callback:function(v){const l=this.getLabelForValue(v);return l.length>18?l.slice(0,18)+'…':l;}},grid:{display:false}} } }
        });
    }
});
</script>
</body>
</html>