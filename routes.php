<?php
/**
 * SMART CROP ASSISTANT - Routes
 * File: routes.php
 */

// =============================================
// PUBLIC ROUTES (no login needed)
// =============================================

// Landing / Home page
$router->get('/', 'app/views/landing');

// Login
$router->get('login',   'app/views/auth/login');
$router->post('login',  'app/views/auth/login');

// Register
$router->get('register',  'app/views/auth/register');
$router->post('register', 'app/views/auth/register');

// Logout
$router->get('logout', 'app/views/auth/logout');


// =============================================
// PROTECTED ROUTES (login required)
// =============================================

// Main crop analyzer
$router->get('crop-assistant', 'app/views/crop_assistant')
       ->middleware('auth');

// Handle image upload + analysis
$router->post('analyze', 'app/views/result')
       ->middleware('auth');

// Farmer dashboard
$router->get('dashboard', 'app/views/dashboard')
       ->middleware('auth');

// Scan history (per-farmer)
$router->get('history', 'app/views/history')
       ->middleware('auth');