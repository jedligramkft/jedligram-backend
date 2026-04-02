<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ThreadUserController;
use App\Http\Controllers\VoteController;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::group(['middleware' => ['throttle:uploads']], function () {
        Route::post('threads/{thread}/image', [ThreadController::class, 'threadImage'])->middleware('can:upload,thread');
        Route::post('threads/{thread}/header', [ThreadController::class, 'headerImage'])->middleware('can:upload,thread');
        Route::post('users/profile-picture', [UserController::class, 'uploadPfP']);
        Route::post('threads/{thread}/post', [PostController::class, 'store'])->middleware('can:create,App\Models\Post,thread');
    });
    Route::post('threads/{thread}/join', [ThreadUserController::class, 'join']);
    Route::delete('threads/{thread}/leave', [ThreadUserController::class, 'leave'])->middleware('can:delete,thread');
    Route::get('threads/{thread}/posts', [ThreadController::class, 'postsOfThread'])->middleware('can:view,thread');
    Route::get('threads/search', [ThreadController::class, 'search']);
    Route::get('threads/{thread}', [ThreadController::class, 'show'])->middleware('can:view,thread');
    Route::put('threads/{thread}', [ThreadController::class, 'update'])->middleware('can:update,thread');
    Route::put('posts/{post}', [PostController::class, 'update']);
    // FIXED WITH TDD
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->middleware('can:delete,post');
    Route::get('posts/{post}', [PostController::class, 'show'])->middleware('can:view,post');
    Route::get('posts/{post}/comments', [CommentController::class, 'index'])->middleware('can:viewAny,post');
    Route::delete('posts/{post}/comments/{comment}', [CommentController::class, 'destroy'])->middleware('can:delete,comment');
    Route::get('comments/{comment}/replies', [CommentController::class, 'replies'])->middleware('can:view,comment');
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::post('posts/{post}/vote', [VoteController::class, 'vote']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('threads/{thread}/members', [ThreadUserController::class, 'index'])->middleware('can:viewMembers,thread');
    Route::patch('threads/{thread}/members/{user}', [ThreadUserController::class, 'assignRole'])->middleware('can:updateRole,thread');
    Route::patch('threads/{thread}/members/{user}/ban', [ThreadUserController::class, 'ban'])->middleware('can:ban,thread,user');
    Route::group(['middleware' => ['throttle:content-creation']], function () {
        Route::post('threads', [ThreadController::class, 'store']);
        Route::post('posts/{post}/comments', [CommentController::class, 'store'])->middleware('can:create,App\Models\Comment,post');
    });

    Route::post('/toggle-2fa', [UserController::class, 'toggle2fa']);
    Route::get('/is-2fa-enabled', [UserController::class, 'is2faEnabled']);
});

Route::apiResource('threads', ThreadController::class)->only(['index']);
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login'])->middleware('throttle:login');
Route::get('users/{user}/threads', [UserController::class, 'threadsOfUser']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::get('users', [UserController::class, 'index']);

Route::post('/verify-2fa', [UserController::class, 'verifyToken'])->middleware('throttle:login');
