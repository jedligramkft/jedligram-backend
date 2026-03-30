<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SysadminLoginController;

Route::get('/', function () {
    return view('welcome');
});

// Public sysadmin routes
Route::get('/sysadmin', [SysadminLoginController::class, 'page']);
Route::post('/logout', [SysadminLoginController::class, 'logout']);

// Login with brute-force protection: 10 attempts/min per IP
Route::post('/login', [SysadminLoginController::class, 'login'])
    ->middleware('throttle:sysadmin.login');

// Protected sysadmin action routes: require session auth + 30 req/min rate limit
Route::middleware(['sysadmin.auth', 'throttle:sysadmin'])->group(function () {
    Route::post('/sysadmin/run_command',   [SysadminLoginController::class, 'RunCommand']);
    Route::post('/sysadmin/console_clear', [SysadminLoginController::class, 'clearHistory']);
    Route::get('/sysadmin/stream_command', [SysadminLoginController::class, 'StreamCommand']);
});