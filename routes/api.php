<?php

use App\Http\Controllers\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\VoteController;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('threads/{thread}/join', [ThreadController::class, 'join']);
    Route::delete('threads/{thread}/leave', [ThreadController::class, 'leave']);
    Route::get('threads/{thread}/posts', [ThreadController::class, 'postsOfThread']);
    Route::get('threads/search', [ThreadController::class, 'search']);
    Route::post('threads/{thread}/post', [PostController::class, 'store']);
    Route::post('threads', [ThreadController::class, 'store']);
    Route::get('threads/{thread}', [ThreadController::class, 'show']);
    Route::apiResource('posts', PostController::class);
    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store']);
    Route::get('comments/{comment}/replies', [CommentController::class, 'replies']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::post('users/profile-picture', [UserController::class, 'uploadPfP']);
    Route::post('posts/{post}/vote', [VoteController::class, 'vote']);
    Route::post('logout', [UserController::class, 'logout']);
});

Route::apiResource('threads', ThreadController::class)->only(['index']);
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::get('users/{user}/threads', [UserController::class, 'postOfUser']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::get('users', [UserController::class, 'index']);
Route::apiResource('roles', RoleController::class);
Route::apiResource('votes', VoteController::class);
