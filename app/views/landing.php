<?php
/**
 * SMART CROP ASSISTANT - Landing Page
 * File: app/views/landing.php
 *
 * Public homepage. Shows features and CTAs.
 * If already logged in, shows a "Go to Dashboard" button.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logged_in = isset($_SESSION['user']['id']);
$user      = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Crop Assistant — Rice Health Monitoring for Filipino Farmers</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body     { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3 { font-family: 'Playfair Display', Georgia, serif; }

        .nav-glass {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(70px); opacity: 0.13; pointer-events: none;
        }
        @keyframes float {
            0%,100% { transform: translateY(0px); }
            50%      { transform: translateY(-12px); }
        }
        .float { animation: float 4s ease-in-out infinite; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up   { animation: fadeUp 0.6s ease forwards; }
        .delay-1   { animation-delay: 0.1s; opacity: 0; }
        .delay-2   { animation-delay: 0.2s; opacity: 0; }
        .delay-3   { animation-delay: 0.3s; opacity: 0; }
        .delay-4   { animation-delay: 0.4s; opacity: 0; }
        .delay-5   { animation-delay: 0.5s; opacity: 0; }

        .btn-primary {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            transition: all 0.25s ease;
            position: relative; overflow: hidden;
        }
        .btn-primary::after {
            content: ''; position: absolute;
            top: -50%; left: -60%; width: 40%; height: 200%;
            background: rgba(255,255,255,0.15);
            transform: skewX(-20deg); transition: left 0.5s ease;
        }
        .btn-primary:hover::after { left: 130%; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(22,163,74,0.4); }

        .feature-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }

        .disease-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 9999px;
            font-size: 13px; font-weight: 500;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            color: #bbf7d0;
        }

        .stat-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
        }
    </style>
</head>
<body class="bg-white">

<!-- ============================================================
     NAVIGATION
     ============================================================ -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 py-3.5 flex items-center justify-between">

        <!-- Brand -->
        <a href="<?= url('/') ?>" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center shadow-sm">
                <span class="text-lg">🌾</span>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900"
                     style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">Rice Health Monitoring</div>
            </div>
        </a>

        <!-- Nav links -->
        <div class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
            <a href="#features" class="hover:text-green-700 transition-colors">Features</a>
            <a href="#diseases" class="hover:text-green-700 transition-colors">Diseases</a>
            <a href="#how-it-works" class="hover:text-green-700 transition-colors">How It Works</a>
        </div>

        <!-- Auth buttons -->
        <div class="flex items-center gap-2">
            <?php if ($logged_in): ?>
                <span class="text-sm text-gray-500 hidden md:block">
                    Welcome, <?= htmlspecialchars($user['full_name']) ?>!
                </span>
                <a href="<?= url('dashboard') ?>"
                   class="btn-primary px-4 py-2 rounded-lg text-white text-sm font-medium">
                    🌾 My Dashboard
                </a>
            <?php else: ?>
                <a href="<?= url('login') ?>"
                   class="px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100
                          text-sm font-medium transition-colors">
                    Login
                </a>
                <a href="<?= url('register') ?>"
                   class="btn-primary px-4 py-2 rounded-lg text-white text-sm font-medium">
                    Get Started Free
                </a>
            <?php endif; ?>
        </div>

    </div>
</nav>


<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="relative bg-green-900 overflow-hidden pt-20 pb-28 px-4">

    <!-- Decorative blobs -->
    <div class="blob bg-green-400 w-96 h-96 -top-20 -left-20"></div>
    <div class="blob bg-green-300 w-80 h-80 top-10 right-0"></div>
    <div class="blob bg-green-500 w-64 h-64 bottom-0 left-1/2"></div>

    <div class="max-w-4xl mx-auto text-center relative z-10">

        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-green-800/60 border border-green-700
                    rounded-full px-4 py-1.5 text-green-300 text-xs font-medium mb-6 fade-up">
            <span class="w-1.5 h-1.5 bg-green-400 rounded-full inline-block animate-pulse"></span>
            Built for Philippine Rice Farmers · Powered by AI Color Analysis
        </div>

        <!-- Heading -->
        <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight fade-up delay-1">
            Protect Your<br>
            <span class="text-green-300">Rice Harvest</span>
        </h1>

        <p class="text-green-200 text-xl max-w-2xl mx-auto leading-relaxed mb-4 fade-up delay-2">
            Upload a photo of your rice plant and instantly detect diseases,
            check harvest readiness, and get expert farming recommendations
            — all tailored to Philippine conditions.
        </p>

        <!-- Disease pills -->
        <div class="flex flex-wrap justify-center gap-2 mb-10 fade-up delay-3">
            <?php foreach ([
                '🍄 Rice Blast', '🦠 Bacterial Blight', '🟤 Brown Spot',
                '🌿 Sheath Blight', '🌿 Nitrogen Deficiency', '💧 Drought Stress'
            ] as $d): ?>
                <span class="disease-pill"><?= $d ?></span>
            <?php endforeach; ?>
        </div>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 fade-up delay-4">
            <?php if ($logged_in): ?>
                <a href="<?= url('crop-assistant') ?>"
                   class="btn-primary px-8 py-4 rounded-xl text-white font-semibold text-lg">
                    🔍 Analyze My Rice Plant
                </a>
                <a href="<?= url('dashboard') ?>"
                   class="px-8 py-4 rounded-xl bg-white/10 border border-white/20
                          text-white font-semibold text-lg hover:bg-white/20 transition-colors">
                    📊 View My Dashboard
                </a>
            <?php else: ?>
                <a href="<?= url('register') ?>"
                   class="btn-primary px-8 py-4 rounded-xl text-white font-semibold text-lg">
                    🌾 Start for Free
                </a>
                <a href="<?= url('login') ?>"
                   class="px-8 py-4 rounded-xl bg-white/10 border border-white/20
                          text-white font-semibold text-lg hover:bg-white/20 transition-colors">
                    🔑 Login
                </a>
            <?php endif; ?>
        </div>

        <!-- Floating rice emoji -->
        <div class="float inline-block text-7xl mt-12 fade-up delay-5">🌾</div>

    </div>
</section>


<!-- ============================================================
     STATS BAR
     ============================================================ -->
<section class="bg-green-800 py-8 px-4">
    <div class="max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ([
            ['6',   'Rice diseases detected'],
            ['6+',  'Philippine varieties supported'],
            ['100%','Free for farmers'],
            ['Real','Weather API integration'],
        ] as $stat): ?>
            <div class="text-center">
                <div class="text-3xl font-bold text-white mb-1"><?= $stat[0] ?></div>
                <div class="text-green-300 text-xs"><?= $stat[1] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>


<!-- ============================================================
     FEATURES SECTION
     ============================================================ -->
<section id="features" class="py-20 px-4 bg-stone-50">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
            <p class="text-green-600 text-sm font-semibold mb-2">WHY CHOOSE US</p>
            <h2 class="text-4xl font-bold text-gray-900">
                Everything a rice farmer needs
            </h2>
            <p class="text-gray-500 mt-3 max-w-xl mx-auto">
                Smart Crop Assistant combines AI analysis, real weather data,
                and expert knowledge from PhilRice into one easy-to-use tool.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ([
                ['🔬', 'Disease Detection',    'bg-red-50',    'border-red-100',
                 'Instantly identifies 6 major rice diseases using AI color analysis of your plant photo.'],
                ['🌾', 'Harvest Readiness',    'bg-yellow-50', 'border-yellow-100',
                 'Know exactly when your rice is ready to harvest based on growth stage and variety maturity.'],
                ['🌤️', 'Live Weather Alerts',  'bg-blue-50',   'border-blue-100',
                 'Real-time weather data for your farm location with rice-specific farming alerts.'],
                ['💡', 'Expert Recommendations','bg-green-50', 'border-green-100',
                 'Get specific advice on fungicides, fertilizers, and farming practices from PhilRice guidelines.'],
                ['📊', 'Personal Dashboard',   'bg-purple-50', 'border-purple-100',
                 'Track all your scans in one place. See your farm health trends over time.'],
                ['🌾', 'Variety Profiles',     'bg-orange-50', 'border-orange-100',
                 'Supports NSIC Rc222, IR64, PSB Rc82, Dinorado and more with variety-specific disease risks.'],
            ] as $f): ?>
                <div class="feature-card bg-white rounded-2xl border <?= $f[3] ?>
                            p-6 shadow-sm">
                    <div class="w-12 h-12 <?= $f[2] ?> border <?= $f[3] ?>
                                rounded-xl flex items-center justify-center text-2xl mb-4">
                        <?= $f[0] ?>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2"><?= $f[1] ?></h3>
                    <p class="text-gray-500 text-sm leading-relaxed"><?= $f[4] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>


<!-- ============================================================
     HOW IT WORKS
     ============================================================ -->
<section id="how-it-works" class="py-20 px-4 bg-white">
    <div class="max-w-4xl mx-auto">

        <div class="text-center mb-14">
            <p class="text-green-600 text-sm font-semibold mb-2">SIMPLE PROCESS</p>
            <h2 class="text-4xl font-bold text-gray-900">How it works</h2>
            <p class="text-gray-500 mt-3">Get your rice plant analyzed in 3 simple steps</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ([
                ['01', '📷', 'Take a photo',
                 'Photograph your rice plant — leaves, sheaths, or panicles showing symptoms.'],
                ['02', '🔬', 'Upload & analyze',
                 'Upload the photo and select your variety and growth stage for accurate results.'],
                ['03', '💡', 'Get recommendations',
                 'Receive instant disease diagnosis, harvest status, weather alerts, and expert tips.'],
            ] as $step): ?>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-900 rounded-2xl flex items-center
                                justify-center text-3xl mx-auto mb-4 shadow-lg">
                        <?= $step[1] ?>
                    </div>
                    <div class="text-xs font-bold text-green-600 mb-2">STEP <?= $step[0] ?></div>
                    <h3 class="font-semibold text-gray-900 mb-2 text-lg"><?= $step[2] ?></h3>
                    <p class="text-gray-500 text-sm leading-relaxed"><?= $step[3] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>


<!-- ============================================================
     DISEASES SECTION
     ============================================================ -->
<section id="diseases" class="py-20 px-4 bg-green-900">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
            <p class="text-green-400 text-sm font-semibold mb-2">DETECTION COVERAGE</p>
            <h2 class="text-4xl font-bold text-white">Rice diseases we detect</h2>
            <p class="text-green-300 mt-3 max-w-xl mx-auto">
                All major diseases affecting Philippine rice crops based on
                PhilRice research data.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ([
                ['🍄', 'Rice Blast',              'High',   'Pyricularia oryzae fungus',
                 'Diamond-shaped lesions on leaves and neck. Most destructive rice disease in PH.'],
                ['🦠', 'Bacterial Leaf Blight',   'High',   'Xanthomonas oryzae',
                 'Yellow-white stripes on leaf edges. Spreads rapidly in flooded fields.'],
                ['🟤', 'Brown Spot',               'Medium', 'Bipolaris oryzae fungus',
                 'Oval brown spots with yellow halo. Often linked to low soil potassium.'],
                ['🌿', 'Sheath Blight',            'High',   'Rhizoctonia solani fungus',
                 'Gray-green lesions on leaf sheath near waterline. Common in dense paddies.'],
                ['🌿', 'Nitrogen Deficiency',      'Medium', 'Nutrient deficiency',
                 'Yellowing of older leaves from tips. Reduces tillering and grain yield.'],
                ['💧', 'Drought / Water Stress',   'Medium', 'Environmental stress',
                 'Pale rolled leaves. Critical during flowering — causes 50% yield loss.'],
            ] as $d): ?>
                <div class="bg-white/10 border border-white/15 rounded-2xl p-5">
                    <div class="flex items-start justify-between mb-3">
                        <span class="text-3xl"><?= $d[0] ?></span>
                        <span class="text-xs px-2 py-1 rounded-full font-medium
                                     <?= $d[2] === 'High' ? 'bg-red-500/20 text-red-300' : 'bg-yellow-500/20 text-yellow-300' ?>">
                            <?= $d[2] ?> severity
                        </span>
                    </div>
                    <h3 class="font-semibold text-white mb-1"><?= $d[1] ?></h3>
                    <p class="text-xs text-green-400 mb-2 italic"><?= $d[3] ?></p>
                    <p class="text-green-200 text-xs leading-relaxed"><?= $d[4] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>


<!-- ============================================================
     CTA SECTION
     ============================================================ -->
<section class="py-20 px-4 bg-white">
    <div class="max-w-2xl mx-auto text-center">
        <div class="text-5xl mb-5">🌾</div>
        <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Ready to protect your rice crop?
        </h2>
        <p class="text-gray-500 text-lg mb-8">
            Join Filipino rice farmers using Smart Crop Assistant to detect
            diseases early and maximize their harvest.
        </p>
        <?php if ($logged_in): ?>
            <a href="<?= url('crop-assistant') ?>"
               class="btn-primary inline-block px-10 py-4 rounded-xl
                      text-white font-semibold text-lg">
                🔍 Analyze My Plant Now
            </a>
        <?php else: ?>
            <a href="<?= url('register') ?>"
               class="btn-primary inline-block px-10 py-4 rounded-xl
                      text-white font-semibold text-lg">
                🌾 Create Free Account
            </a>
            <p class="text-gray-400 text-sm mt-4">
                Already have an account?
                <a href="<?= url('login') ?>" class="text-green-600 font-medium">Login here</a>
            </p>
        <?php endif; ?>
    </div>
</section>


<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="bg-green-950 py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-green-700 rounded-lg flex items-center justify-center">
                    <span>🌾</span>
                </div>
                <div>
                    <div class="text-white text-sm font-semibold"
                         style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                    <div class="text-green-400 text-xs">Rice Health Monitoring System</div>
                </div>
            </div>
            <div class="flex items-center gap-6 text-xs text-green-400">
                <a href="<?= url('register') ?>" class="hover:text-white transition-colors">Register</a>
                <a href="<?= url('login') ?>"    class="hover:text-white transition-colors">Login</a>
                <?php if ($logged_in): ?>
                    <a href="<?= url('dashboard') ?>"      class="hover:text-white transition-colors">Dashboard</a>
                    <a href="<?= url('crop-assistant') ?>" class="hover:text-white transition-colors">Analyze</a>
                    <a href="<?= url('logout') ?>"         class="hover:text-white transition-colors">Logout</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="border-t border-green-900 mt-6 pt-6 text-center text-xs text-green-600">
            Built with LavaLite Framework &amp; Tailwind CSS · Final Project · IT Student
        </div>
    </div>
</footer>

</body>
</html>