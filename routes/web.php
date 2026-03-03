<?php

use App\Http\Controllers\SysadminLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sysadmin', [SysadminLoginController::class, 'page']);

Route::post('/login', [SysadminLoginController::class, 'login']);
Route::post('/logout', [SysadminLoginController::class, 'logout']);
Route::post('/sysadmin/run_command', [SysadminLoginController::class, 'RunCommand']);
Route::post('/sysadmin/console_clear', [SysadminLoginController::class, 'clearHistory']);
Route::get('/sysadmin/stream_command', [SysadminLoginController::class, 'StreamCommand']);