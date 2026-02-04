<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\VoteController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum'));

Route::apiResource('users', UserController::class);
Route::apiResource('posts', PostController::class);
Route::apiResource('threads', ThreadController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('votes', VoteController::class);

