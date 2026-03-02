<?php

use App\Http\Controllers\SysadminLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sysadmin', [SysadminLoginController::class, 'page']);

Route::post('/login', [SysadminLoginController::class, 'login']);
Route::post('/logout', [SysadminLoginController::class, 'logout']);
// Route::post('/sysadmin/migrate', [SysadminLoginController::class, 'migrate']);
// Route::post('/sysadmin/migrate_rollback', [SysadminLoginController::class, 'migrate_rollback']);
// Route::post('/sysadmin/migrate_fresh', [SysadminLoginController::class, 'migrate_fresh']);
// Route::post('/sysadmin/migrate_fresh_seed', [SysadminLoginController::class, 'migrate_fresh_seed']);
// Route::post('/sysadmin/seed', [SysadminLoginController::class, 'seed']);
// Route::post('/sysadmin/production_seeder', [SysadminLoginController::class, 'db_production_seed']);
// Route::post('/sysadmin/dummy_seeder', [SysadminLoginController::class, 'db_dummy_seed']);

Route::post('/sysadmin/run_command', [SysadminLoginController::class, 'RunCommand']);
Route::post('/sysadmin/console_clear', [SysadminLoginController::class, 'clearHistory']);
Route::get('/sysadmin/stream_command', [SysadminLoginController::class, 'StreamCommand']);