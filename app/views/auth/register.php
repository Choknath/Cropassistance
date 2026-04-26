<?php
/**
 * SMART CROP ASSISTANT - Register Page
 * File: app/views/auth/register.php
 *
 * Handles both GET (show form) and POST (process form).
 * Creates a new farmer account in the users table.
 */

// Start session to store flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// HANDLE FORM SUBMISSION (POST)
// =============================================
$errors   = [];
$success  = '';
$old      = []; // keeps form values if there's an error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize form inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim(strtolower($_POST['email'] ?? ''));
    $farm_name = trim($_POST['farm_name'] ?? '');
    $location  = trim($_POST['location']  ?? 'Bicol');
    $password  = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    // Keep values for re-filling form on error
    $old = compact('full_name', 'email', 'farm_name', 'location');

    // ---- VALIDATION ----

    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (empty($farm_name)) {
        $errors[] = 'Farm name is required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // ---- CHECK IF EMAIL ALREADY EXISTS ----
    if (empty($errors)) {
        try {
            $existing = db()->table('users')
                            ->where('email', $email)
                            ->get();

            if ($existing) {
                $errors[] = 'This email is already registered. Please login instead.';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error. Please try again.';
            error_log('Register DB error: ' . $e->getMessage());
        }
    }

    // ---- CREATE THE ACCOUNT ----
    if (empty($errors)) {
        try {
            // password_hash() securely encrypts the password
            // NEVER store plain text passwords!
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $user_id = db()->table('users')->insert([
                'full_name' => $full_name,
                'email'     => $email,
                'password'  => $hashed_password,
                'farm_name' => $farm_name,
                'location'  => $location,
            ]);

            if ($user_id) {
                // Auto-login after registration
                $_SESSION['user'] = [
                    'id'        => $user_id,
                    'full_name' => $full_name,
                    'email'     => $email,
                    'farm_name' => $farm_name,
                    'location'  => $location,
                ];

                // Redirect to dashboard
                header('Location: ' . url('dashboard'));
                exit;
            } else {
                $errors[] = 'Could not create account. Please try again.';
            }

        } catch (Exception $e) {
            $errors[] = 'Registration failed. Please try again.';
            error_log('Register insert error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Smart Crop Assistant</title>
    <link rel="stylesheet" href="<?= base_url() ?>/public/css/tailwind.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body     { font-family: 'DM Sans', sans-serif; }
        h1,h2,h3 { font-family: 'Playfair Display', Georgia, serif; }
        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(60px); opacity: 0.12; pointer-events: none;
        }
        input:focus, select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
            border-color: #4ade80 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(22,163,74,0.35);
        }
        .fade-up { animation: fadeUp 0.5s ease forwards; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-stone-50 min-h-screen flex flex-col">

<!-- TOP BAR -->
<div class="bg-green-900 py-3 px-5">
    <div class="max-w-5xl mx-auto flex items-center justify-between">
        <a href="<?= url('/') ?>" class="flex items-center gap-2 text-white">
            <span class="text-xl">🌾</span>
            <span class="font-semibold text-sm"
                  style="font-family:'Playfair Display',serif">Smart Crop Assistant</span>
        </a>
        <a href="<?= url('login') ?>"
           class="text-green-200 hover:text-white text-sm transition-colors">
            Already have an account? Login →
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="flex-1 flex items-center justify-center px-4 py-12 relative overflow-hidden">

    <!-- Background blobs -->
    <div class="blob bg-green-400 w-96 h-96 -top-20 -left-20"></div>
    <div class="blob bg-green-300 w-72 h-72 bottom-0 right-0"></div>

    <div class="w-full max-w-lg relative z-10 fade-up">

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🌾</div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1">Create your account</h1>
            <p class="text-gray-500 text-sm">Join Smart Crop Assistant — free for Filipino rice farmers</p>
        </div>

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                <p class="text-sm font-semibold text-red-700 mb-2">⚠️ Please fix the following:</p>
                <?php foreach ($errors as $error): ?>
                    <p class="text-sm text-red-600">· <?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Register Form -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">

            <form method="POST" action="<?= url('register') ?>">
                <?= csrf_field() ?>

                <!-- Full Name -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        👤 Full Name
                    </label>
                    <input type="text" name="full_name"
                           value="<?= htmlspecialchars($old['full_name'] ?? '') ?>"
                           placeholder="e.g. Juan dela Cruz"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        📧 Email Address
                    </label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           placeholder="e.g. juan@email.com"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Farm Name + Location -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            🏡 Farm Name
                        </label>
                        <input type="text" name="farm_name"
                               value="<?= htmlspecialchars($old['farm_name'] ?? '') ?>"
                               placeholder="e.g. Dela Cruz Farm"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3
                                      text-sm bg-gray-50 focus:bg-white transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            📍 Location
                        </label>
                        <input type="text" name="location"
                               value="<?= htmlspecialchars($old['location'] ?? 'Bicol') ?>"
                               placeholder="e.g. Bicol"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3
                                      text-sm bg-gray-50 focus:bg-white transition-colors">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        🔒 Password
                        <span class="text-gray-400 font-normal text-xs ml-1">(min. 6 characters)</span>
                    </label>
                    <input type="password" name="password"
                           placeholder="Create a strong password"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        🔒 Confirm Password
                    </label>
                    <input type="password" name="confirm"
                           placeholder="Repeat your password"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="btn-primary w-full text-white font-semibold
                               py-3.5 rounded-xl text-base">
                    🌾 Create My Account
                </button>

            </form>

            <p class="text-center text-sm text-gray-400 mt-5">
                Already have an account?
                <a href="<?= url('login') ?>"
                   class="text-green-600 font-medium hover:text-green-700">
                    Login here
                </a>
            </p>

        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="py-4 text-center text-xs text-gray-400 border-t border-gray-100 bg-white">
    Smart Crop Assistant — Rice Health Monitoring System
</footer>

</body>
</html>