<?php
/**
 * SMART CROP ASSISTANT - Login Page
 * File: app/views/auth/login.php
 *
 * Handles both GET (show form) and POST (process login).
 * Verifies email + password and creates a session.
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user']['id'])) {
    header('Location: ' . url('dashboard'));
    exit;
}

// =============================================
// HANDLE FORM SUBMISSION (POST)
// =============================================
$error = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim(strtolower($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';
    $old_email = $email;

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {

        try {
            // Look up user by email
            $user = db()->table('users')
                        ->where('email', $email)
                        ->get();

            if (!$user) {
                // User not found — vague message for security
                $error = 'Invalid email or password. Please try again.';

            } elseif (!password_verify($password, $user['password'])) {
                // Wrong password
                $error = 'Invalid email or password. Please try again.';

            } else {
                // ✅ Login successful!
                // Store user info in session
                $_SESSION['user'] = [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'farm_name' => $user['farm_name'],
                    'location'  => $user['location'],
                ];

                // Redirect to where they wanted to go,
                // or default to dashboard
                $redirect = $_SESSION['redirect_after_login'] ?? url('dashboard');
                unset($_SESSION['redirect_after_login']);

                header('Location: ' . $redirect);
                exit;
            }

        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Smart Crop Assistant</title>
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
        input:focus {
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
        <a href="<?= url('register') ?>"
           class="text-green-200 hover:text-white text-sm transition-colors">
            No account yet? Register →
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="flex-1 flex items-center justify-center px-4 py-12 relative overflow-hidden">

    <div class="blob bg-green-400 w-96 h-96 -top-20 -left-20"></div>
    <div class="blob bg-green-300 w-72 h-72 bottom-0 right-0"></div>

    <div class="w-full max-w-md relative z-10 fade-up">

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🌾</div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1">Welcome back!</h1>
            <p class="text-gray-500 text-sm">Login to your Smart Crop Assistant account</p>
        </div>

        <!-- Error message -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                <p class="text-sm text-red-600">❌ <?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Success flash message -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-5">
                <p class="text-sm text-green-700">
                    ✅ <?= htmlspecialchars($_SESSION['flash_success']) ?>
                </p>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">

            <form method="POST" action="<?= url('login') ?>">
                <?= csrf_field() ?>

                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        📧 Email Address
                    </label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($old_email) ?>"
                           placeholder="Enter your email"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        🔒 Password
                    </label>
                    <input type="password" name="password"
                           placeholder="Enter your password"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3
                                  text-sm bg-gray-50 focus:bg-white transition-colors">
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="btn-primary w-full text-white font-semibold
                               py-3.5 rounded-xl text-base">
                    🔑 Login to My Account
                </button>

            </form>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-5">
                <div class="flex-1 border-t border-gray-200"></div>
                <span class="text-xs text-gray-400">or</span>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>

            <a href="<?= url('register') ?>"
               class="block w-full text-center border-2 border-green-600
                      text-green-700 font-semibold py-3 rounded-xl text-sm
                      hover:bg-green-50 transition-colors">
                🌾 Create a New Account
            </a>

            <p class="text-center text-xs text-gray-400 mt-4">
                <a href="<?= url('/') ?>" class="hover:text-gray-600 transition-colors">
                    ← Back to homepage
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