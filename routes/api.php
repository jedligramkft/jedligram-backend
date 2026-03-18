<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ThreadUserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\LdapTestController;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('threads/{thread}/join', [ThreadUserController::class, 'join']);
    Route::delete('threads/{thread}/leave', [ThreadUserController::class, 'leave']);
    Route::get('threads/{thread}/posts', [ThreadController::class, 'postsOfThread']);
    Route::delete('threads/{thread}/posts/{post}', [PostController::class, 'destroy'])->middleware('can:delete,post');
    Route::get('threads/search', [ThreadController::class, 'search']);
    Route::post('threads/{thread}/post', [PostController::class, 'store'])->middleware('can:create,App\Models\Post,thread');
    Route::post('threads', [ThreadController::class, 'store']);
    Route::get('threads/{thread}', [ThreadController::class, 'show']);
    Route::apiResource('posts', PostController::class);
    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [CommentController::class, 'store'])->middleware('can:create,App\Models\Comment,post');
    //TODO: TEST THIS
    Route::delete('posts/{post}/comments/{comment}', [CommentController::class, 'destroy'])->middleware('can:delete,comment');
    Route::get('comments/{comment}/replies', [CommentController::class, 'replies']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::post('users/profile-picture', [UserController::class, 'uploadPfP']);
    Route::post('posts/{post}/vote', [VoteController::class, 'vote']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('threads/{thread}/members', [ThreadUserController::class, 'index'])->middleware('can:viewMembers,thread');
    Route::patch('threads/{thread}/members/{user}', [ThreadUserController::class, 'assignRole'])->middleware('can:updateRole,thread');
    Route::patch('threads/{thread}/members/{user}/ban', [ThreadUserController::class, 'ban'])->middleware('can:ban,thread,user');
});

Route::apiResource('threads', ThreadController::class)->only(['index']);
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::get('users/{user}/threads', [UserController::class, 'postOfUser']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::get('users', [UserController::class, 'index']);
Route::apiResource('roles', RoleController::class);
Route::apiResource('votes', VoteController::class);
