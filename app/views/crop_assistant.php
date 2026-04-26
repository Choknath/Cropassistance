<?php
/**
 * SMART CROP ASSISTANT - Main Upload Page v2.1
 * File: app/views/crop_assistant.php
 * Now includes user session in nav + saves user_id with scans
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyze Plant — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body      { font-family: 'DM Sans', sans-serif; background: #f8f7f4; }
        h1,h2,h3  { font-family: 'Playfair Display', Georgia, serif; }
        .nav-glass {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
        }
        .blob { position: absolute; border-radius: 50%; filter: blur(50px); opacity: 0.15; pointer-events: none; }
        .drop-zone { border: 2.5px dashed #86efac; transition: border-color 0.25s, background 0.25s, transform 0.2s; }
        .drop-zone:hover, .drop-zone.dragover { border-color: #16a34a; background-color: #f0fdf4; transform: scale(1.01); }
        .drop-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        #preview-wrap { display: none; }
        .fade-up { animation: fadeUp 0.5s ease forwards; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .float { animation: float 3s ease-in-out infinite; }
        .btn-submit { background: linear-gradient(135deg, #16a34a 0%, #15803d 60%, #166534 100%); transition: all 0.25s ease; position: relative; overflow: hidden; }
        .btn-submit::after { content: ''; position: absolute; top: -50%; left: -60%; width: 40%; height: 200%; background: rgba(255,255,255,0.15); transform: skewX(-20deg); transition: left 0.5s ease; }
        .btn-submit:hover::after { left: 130%; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(22,163,74,0.35); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        input:focus, select:focus { outline: none; box-shadow: 0 0 0 3px rgba(34,197,94,0.2); border-color: #4ade80 !important; }
        .variety-tag { display: inline-block; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; border-radius: 9999px; padding: 2px 10px; font-size: 11px; font-weight: 500; cursor: pointer; transition: all 0.15s; }
        .variety-tag:hover { background: #dcfce7; border-color: #86efac; }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

<!-- NAV -->
<nav class="nav-glass sticky top-0 z-50 border-b border-green-100 shadow-sm">
    <div class="max-w-5xl mx-auto px-5 py-3 flex items-center justify-between">
        <a href="<?= url('dashboard') ?>" class="flex items-center gap-3 group">
            <div class="w-9 h-9 bg-green-700 rounded-xl flex items-center justify-center group-hover:bg-green-600 transition-colors shadow-sm">
                <span class="text-lg">🌾</span>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-green-900" style="font-family:'Playfair Display',serif">Smart Crop Assistant</div>
                <div class="text-xs text-gray-400">Rice Health Monitoring</div>
            </div>
        </a>
        <div class="flex items-center gap-1">
            <a href="<?= url('dashboard') ?>" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors">📊 Dashboard</a>
            <a href="<?= url('crop-assistant') ?>" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-green-50 text-green-700 font-medium text-sm border border-green-200"><span>🔍</span> Analyze</a>
            <a href="<?= url('history') ?>" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-gray-500 hover:bg-gray-100 font-medium text-sm transition-colors"><span>📋</span> History</a>
            <a href="<?= url('logout') ?>" class="text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50 ml-1">Logout</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="relative bg-green-900 overflow-hidden pt-14 pb-20 px-4">
    <div class="blob bg-green-400 w-72 h-72 -top-10 -left-10"></div>
    <div class="blob bg-green-500 w-96 h-96 top-10 -right-20"></div>
    <div class="blob bg-green-300 w-56 h-56 bottom-0 left-1/3"></div>
    <div class="max-w-3xl mx-auto text-center relative z-10">
        <div class="float inline-block text-6xl mb-5">🌾</div>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 leading-tight">
            Rice Crop<br><span class="text-green-300">Health Analyzer</span>
        </h1>
        <?php if ($user): ?>
        <p class="text-green-300 text-sm mb-4">
            👨‍🌾 <?= htmlspecialchars($user['full_name']) ?> · <?= htmlspecialchars($user['farm_name']) ?>
        </p>
        <?php endif; ?>
        <p class="text-green-200 text-lg max-w-xl mx-auto leading-relaxed mb-3">
            Upload a photo of your rice plant and get instant disease detection,
            harvest readiness assessment, and expert recommendations.
        </p>
        <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-green-200 text-xs font-medium mb-10">
            <span class="w-1.5 h-1.5 bg-green-400 rounded-full inline-block"></span>
            Detects: Rice Blast · Bacterial Blight · Brown Spot · Sheath Blight · Nitrogen Deficiency
        </div>
        <div class="grid grid-cols-4 gap-3 max-w-lg mx-auto">
            <?php foreach ([['🔬','Disease Detection'],['🌾','Harvest Check'],['🌤️','Live Weather'],['📊','Scan History']] as $f): ?>
                <div class="bg-white/10 border border-white/20 rounded-xl py-3 px-2 text-center">
                    <div class="text-2xl mb-1"><?= $f[0] ?></div>
                    <div class="text-green-200 text-xs font-medium"><?= $f[1] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- MAIN -->
<main class="max-w-3xl mx-auto px-4 -mt-6 pb-16 relative z-10">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden fade-up">
        <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4 flex items-center gap-3">
            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center"><span>🌿</span></div>
            <div>
                <h2 class="text-white font-semibold text-base">Upload Rice Plant Photo</h2>
                <p class="text-green-200 text-xs">JPG, PNG or WEBP · Max 5MB · Rice crops only</p>
            </div>
        </div>
        <div class="p-7">
            <form id="upload-form" action="<?= url('analyze') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <!-- Row 1: Variety + Location -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">🌾 Rice Variety <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span></label>
                        <input type="text" name="plant_name" id="plant_name_input"
                               placeholder="e.g. NSIC Rc222, IR64, PSB Rc82..."
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 bg-gray-50 focus:bg-white transition-colors">
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <?php foreach (['NSIC Rc222','NSIC Rc160','IR64','PSB Rc82','Dinorado','NSIC Rc238'] as $v): ?>
                                <span class="variety-tag" onclick="document.getElementById('plant_name_input').value='<?= $v ?>'"><?= $v ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">📍 Field Location <span class="text-gray-400 font-normal text-xs ml-1">(for weather)</span></label>
                        <input type="text" name="location"
                               placeholder="e.g. Bicol, Nueva Ecija..."
                               value="<?= htmlspecialchars($user['location'] ?? 'Bicol') ?>"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 bg-gray-50 focus:bg-white transition-colors">
                        <p class="text-xs text-gray-400 mt-1.5">Used to fetch real-time weather for your farm.</p>
                    </div>
                </div>

                <!-- Row 2: Growth Stage + Days -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">🌱 Growth Stage <span class="text-red-400 text-xs ml-1">*needed for harvest check</span></label>
                        <select name="growth_stage" id="growth_stage" onchange="updateStageInfo(this.value)"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 bg-gray-50 focus:bg-white transition-colors cursor-pointer">
                            <option value="">-- Select growth stage --</option>
                            <option value="seedling">🌱 Seedling (0–21 days)</option>
                            <option value="tillering">🌿 Tillering (22–55 days)</option>
                            <option value="flowering">🌸 Flowering / Booting (56–85 days)</option>
                            <option value="ripening">🌾 Ripening / Grain filling (86–110 days)</option>
                            <option value="harvest">🟡 Maturity / Ready to harvest (110+ days)</option>
                        </select>
                        <div id="stage-info" class="mt-2 text-xs text-green-700 bg-green-50 border border-green-100 rounded-lg px-3 py-2 hidden"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">📅 Days Since Transplant <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span></label>
                        <input type="number" name="days_planted" id="days_planted" placeholder="e.g. 75" min="0" max="150"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 bg-gray-50 focus:bg-white transition-colors">
                        <p class="text-xs text-gray-400 mt-1.5">Helps determine harvest readiness more accurately.</p>
                    </div>
                </div>

                <!-- Drop Zone -->
                <div id="drop-zone" class="drop-zone relative rounded-2xl p-10 text-center cursor-pointer mb-5">
                    <input type="file" name="plant_image" id="plant_image" accept="image/*" onchange="previewImage(this)">
                    <div id="upload-placeholder">
                        <div class="w-16 h-16 bg-green-50 border-2 border-green-200 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl">📷</div>
                        <p class="text-gray-700 font-semibold text-base mb-1">Click or drag your rice plant photo here</p>
                        <p class="text-gray-400 text-sm">Supports JPG, PNG, WEBP — max 5MB</p>
                        <p class="text-green-600 text-xs mt-2 font-medium">🌾 Best results: photograph leaves, sheaths, or panicles clearly</p>
                    </div>
                </div>

                <!-- Preview -->
                <div id="preview-wrap" class="mb-5">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-medium text-gray-700">📸 Photo Preview</p>
                        <button type="button" onclick="removeImage()" class="text-xs text-red-400 hover:text-red-600 transition-colors">✕ Remove photo</button>
                    </div>
                    <div class="relative rounded-xl overflow-hidden border border-green-200 shadow-sm">
                        <img id="preview-img" src="" alt="Rice plant preview" class="w-full max-h-64 object-cover">
                        <div class="absolute bottom-3 left-3 bg-green-700/90 text-white text-xs px-3 py-1.5 rounded-full font-medium flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full inline-block animate-pulse"></span>
                            Ready to analyze
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="submit-btn"
                        class="btn-submit w-full text-white font-semibold py-3.5 px-6 rounded-xl text-base flex items-center justify-center gap-2">
                    <span id="btn-icon">🔍</span>
                    <span id="btn-text">Analyze Rice Plant</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Tips Card -->
    <div class="mt-5 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <span class="w-7 h-7 bg-yellow-100 rounded-lg flex items-center justify-center text-sm border border-yellow-200">💡</span>
            Tips for accurate rice disease detection
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <?php foreach ([
                ['✅','Focus on affected leaves',  'Show yellowing, brown spots, or lesions clearly'],
                ['✅','Include the leaf sheath',   'Capture the base area near the waterline'],
                ['✅','Use natural daylight',      'Outdoor bright light shows disease colors best'],
                ['✅','One clear leaf per photo',  'Avoid cluttered backgrounds for best detection'],
            ] as $tip): ?>
                <div class="flex items-start gap-3 bg-green-50 rounded-xl p-3.5 border border-green-100">
                    <span class="text-base mt-0.5"><?= $tip[0] ?></span>
                    <div>
                        <p class="text-sm font-medium text-green-800"><?= $tip[1] ?></p>
                        <p class="text-xs text-green-600 mt-0.5"><?= $tip[2] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <p class="text-xs font-semibold text-yellow-800 mb-2">🔬 Rice diseases this system can detect:</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-1.5">
                <?php foreach (['🍄 Rice Blast','🦠 Bacterial Leaf Blight','🟤 Brown Spot','🌿 Sheath Blight','🌿 Nitrogen Deficiency','💧 Drought Stress'] as $d): ?>
                    <div class="text-xs text-yellow-700 bg-yellow-100 rounded-lg px-2.5 py-1.5 border border-yellow-200"><?= $d ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<footer class="border-t border-gray-200 bg-white py-5 px-4 mt-4">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-gray-400">
        <div class="flex items-center gap-2"><span>🌾</span><span>Smart Crop Assistant — Rice Health Monitoring System</span></div>
        <div>Built with LavaLite Framework &amp; Tailwind CSS</div>
    </div>
</footer>

<script>
    function updateStageInfo(stage) {
        const info = document.getElementById('stage-info');
        const days = document.getElementById('days_planted');
        const stageData = {
            'seedling':  { tip: '🌱 Seedling stage: Focus on nursery care, proper spacing, and pest monitoring.', mid: 10 },
            'tillering': { tip: '🌿 Tillering stage: Apply nitrogen fertilizer now. Watch for stem borers.', mid: 38 },
            'flowering': { tip: '🌸 Flowering stage: Critical period! Avoid pesticides. Watch for blast.', mid: 70 },
            'ripening':  { tip: '🌾 Ripening stage: Reduce irrigation. Monitor for planthoppers. Harvest soon!', mid: 98 },
            'harvest':   { tip: '🟡 Maturity stage: 80-85% golden grains = ready to harvest!', mid: 115 },
        };
        if (stage && stageData[stage]) {
            info.textContent = stageData[stage].tip;
            info.classList.remove('hidden');
            if (!days.value) days.value = stageData[stage].mid;
        } else {
            info.classList.add('hidden');
        }
    }
    function previewImage(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) { alert('⚠️ File too large! Max 5MB.'); removeImage(); return; }
        const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!allowed.includes(file.type)) { alert('⚠️ Invalid file type! Use JPG, PNG or WEBP.'); removeImage(); return; }
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('upload-placeholder').style.display = 'none';
            document.getElementById('preview-wrap').style.display = 'block';
            document.getElementById('btn-icon').textContent = '🚀';
            document.getElementById('btn-text').textContent = 'Analyze Rice Plant';
        };
        reader.readAsDataURL(file);
    }
    function removeImage() {
        document.getElementById('plant_image').value = '';
        document.getElementById('preview-img').src = '';
        document.getElementById('preview-wrap').style.display = 'none';
        document.getElementById('upload-placeholder').style.display = 'block';
        document.getElementById('btn-icon').textContent = '🔍';
        document.getElementById('btn-text').textContent = 'Analyze Rice Plant';
    }
    const dropZone = document.getElementById('drop-zone');
    dropZone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault(); this.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) { const input = document.getElementById('plant_image'); const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; previewImage(input); }
    });
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('plant_image');
        if (!fileInput.files || !fileInput.files[0]) { e.preventDefault(); alert('⚠️ Please select a rice plant photo first!'); return; }
        document.getElementById('btn-icon').textContent = '⏳';
        document.getElementById('btn-text').textContent = 'Analyzing... Please wait';
        document.getElementById('submit-btn').disabled = true;
    });
</script>
</body>
</html>