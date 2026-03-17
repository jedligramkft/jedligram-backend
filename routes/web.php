<?php

use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::post('/send-welcome-email', function (Request $request) {
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
    ]);

    $name = $request->input('name');
    $email = $request->input('email');

    EmailController::sendWelcomeEmail($email, $name);

    return response()->json(['message' => 'Verification email sent to ' . $email . '!']);
});

Route::post('/send-toggle-2fa-email', function (Request $request) {
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
        'will_be_enabled' => 'required|boolean',
    ]);

    $name = $request->input('name');
    $email = $request->input('email');
    $willBeEnabled = $request->input('will_be_enabled');

    //TODO replace with actual user lookup instead of creating a new user every time
    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make("jelszo123"),
        ]
    );

    try{
        EmailController::sendToggle2faEmail($user, $willBeEnabled);
    }
    catch (\Exception $e) {
        Log::error('Failed to send 2FA email', [
            'email' => $email,
            'error' => $e->getMessage(),
        ]);
        return response()->json(['message' => 'Failed to send 2FA email: ' . $e->getMessage()], 500);
    }

    return response()->json(['message' => 'Verification email sent to ' . $email . '!']);
});

Route::post('/send-2fa', function (Request $request) {
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
    ]);

    $name = $request->input('name');
    $email = $request->input('email');

    //TODO replace with actual user lookup instead of creating a new user every time
    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make("jelszo123"),
        ]
    );

    EmailController::sendLoginVerification($user);

    return response()->json(['message' => 'Verification email sent to ' . $email . '!']);
});

Route::post('/verify-2fa', [UserController::class, 'verify2fa']);
Route::post('/verify-login', [UserController::class, 'verifyLogin']);

Route::get('/email-test', function () {
    return view('emailtest');
});