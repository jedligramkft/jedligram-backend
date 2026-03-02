<?php

use App\Http\Controllers\SysadminLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sysadmin', function () {
    return view('sysadmin.page');
});

Route::post('/login', [SysadminLoginController::class, 'login']);
Route::post('/logout', [SysadminLoginController::class, 'logout']);
Route::post('/sysadmin/migrate', [SysadminLoginController::class, 'migrate']);
Route::post('/sysadmin/migrate_rollback', [SysadminLoginController::class, 'migrate_rollback']);
Route::post('/sysadmin/migrate_fresh', [SysadminLoginController::class, 'migrate_fresh']);
Route::post('/sysadmin/migrate_fresh_seed', [SysadminLoginController::class, 'migrate_fresh_seed']);