<?php

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/send-welcome-email', function () {
    $user = new User([
        'name' => 'Gehér Marcell',
        'email' => 'darkiex03@gmail.com',
        'password' => bcrypt('password123')
    ]);

    Mail::to($user->email)->send(new WelcomeMail($user));
    return 'Welcome email sent!';
});