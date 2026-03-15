<?php

use App\Http\Controllers\SysadminLoginController;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/send-welcome-email', function () {
    $user = new User([
        'name' => 'Gehér Marcell',
        'email' => 'borsodi.koppany@students.jedlik.eu',
        'password' => bcrypt('password123')
    ]);

//    Mail::to($user->email)->send(new WelcomeMail($user));
    \App\Http\Controllers\EmailController::sendPasswordResetCode($user);
    return 'Welcome email sent!';
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
