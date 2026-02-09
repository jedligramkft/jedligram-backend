<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function sendWelcomeEmail(User $targetUser)
    {
        Mail::to($targetUser)->send(new WelcomeMail($targetUser));
        return response()->json(['message' => 'Welcome email sent to '. $targetUser->email . "!"]);
    }
}
