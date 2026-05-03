<?php
/**
 * SMART CROP ASSISTANT + FARMLOG
 * File: routes.php
 *
 * PUBLIC routes   → no login needed
 * PROTECTED routes → auth middleware required
 * ADMIN routes    → auth + authorize middleware
 */

// =============================================
// PUBLIC ROUTES
// =============================================
$router->get('/',         'app/views/landing');
$router->get('login',     'app/views/auth/login');
$router->post('login',    'app/views/auth/login');
$router->get('register',  'app/views/auth/register');
$router->post('register', 'app/views/auth/register');
$router->get('logout',    'app/views/auth/logout');


// =============================================
// SMART CROP — PROTECTED
// =============================================
$router->get('dashboard',     'app/views/dashboard')
       ->middleware('auth');

$router->get('crop-assistant','app/views/crop_assistant')
       ->middleware('auth');

$router->post('analyze',      'app/views/result')
       ->middleware('auth');

$router->get('history',       'app/views/history')
       ->middleware('auth');


// =============================================
// FARMLOG — FIELD PLOTS
// =============================================
$router->get('plots',          'app/views/farmlog/plots')
       ->middleware('auth');

$router->get('plots/create',   'app/views/farmlog/plots_create')
       ->middleware('auth');

$router->post('plots/store',   'app/views/farmlog/plots_create')
       ->middleware('auth');

$router->get('plots/edit/{id}','app/views/farmlog/plots_edit')
       ->middleware('auth');

$router->post('plots/update/{id}','app/views/farmlog/plots_edit')
       ->middleware('auth');

$router->post('plots/delete',  'app/views/farmlog/plots_delete')
       ->middleware('auth');


// =============================================
// FARMLOG — RICE CROPS
// =============================================
$router->get('crops',          'app/views/farmlog/crops')
       ->middleware('auth');

$router->get('crops/create',   'app/views/farmlog/crops_create')
       ->middleware('auth');

$router->post('crops/store',   'app/views/farmlog/crops_create')
       ->middleware('auth');

$router->get('crops/{id}',     'app/views/farmlog/crops_detail')
       ->middleware('auth');

$router->get('crops/edit/{id}','app/views/farmlog/crops_edit')
       ->middleware('auth');

$router->post('crops/update/{id}','app/views/farmlog/crops_edit')
       ->middleware('auth');

$router->post('crops/delete',  'app/views/farmlog/crops_delete')
       ->middleware('auth');


// =============================================
// FARMLOG — CROP PROGRESS
// =============================================
$router->post('progress/store', 'app/views/farmlog/progress_store')
       ->middleware('auth');

$router->post('progress/delete','app/views/farmlog/progress_delete')
       ->middleware('auth');


// =============================================
// FARMLOG — FERTILIZER SCHEDULE
// =============================================
$router->get('fertilizer',          'app/views/farmlog/fertilizer')
       ->middleware('auth');

$router->post('fertilizer/done',    'app/views/farmlog/fertilizer_done')
       ->middleware('auth');


// =============================================
// FARMLOG — HARVEST RECORDS
// =============================================
$router->get('harvest',            'app/views/farmlog/harvest')
       ->middleware('auth');

$router->get('harvest/create/{id}','app/views/farmlog/harvest_create')
       ->middleware('auth');

$router->post('harvest/store',     'app/views/farmlog/harvest_create')
       ->middleware('auth');


// =============================================
// ADMIN PANEL
// =============================================
$router->get('admin',          'app/views/admin/dashboard')
       ->middleware('auth')
       ->middleware('authorize');

$router->get('admin/users',    'app/views/admin/users')
       ->middleware('auth')
       ->middleware('authorize');

$router->get('admin/crops',    'app/views/admin/crops')
       ->middleware('auth')
       ->middleware('authorize');