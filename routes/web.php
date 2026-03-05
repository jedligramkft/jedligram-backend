<?php

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
