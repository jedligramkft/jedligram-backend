<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    private static function generateToken(int $length = 10)
    {
        return bin2hex(random_bytes($length));
    }

    public static function sendWelcomeEmail(User $targetUser)
    {
        Mail::to($targetUser)->send(new WelcomeMail($targetUser));
        return response()->json(['message' => 'Welcome email sent to '. $targetUser->email . "!"]);
    }

    /**
     * Generates an n-digit verification code, stores it in the database, and sends it to the user's email.
     * @param User $targetUser The user to whom the verification code will be sent.
     */
    public static function sendEmailVerificationCode(User $targetUser)
    {
        $token = self::generateToken(6);
        $expiryMinutes = 15;

        //Add to the EmailVerification table
        EmailVerification::updateOrCreate([
            'user_id' => $targetUser->id,
            'token' => $token,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        Mail::to($targetUser)->send(new EmailVerificationCodeMail($token, $targetUser->email));
    }

    public static function sendPasswordResetCode(User $targetUser)
    {
        $token = self::generateToken(2);
        $expiryMinutes = 15;

        // //Add to the EmailVerification table
        // EmailVerification::updateOrCreate([
        //     'user_id' => $targetUser->id,
        //     'token' => $token,
        //     'expires_at' => now()->addMinutes($expiryMinutes),
        // ]);

        // die($targetUser->email);

        Mail::to($targetUser)->send(new PasswordResetMail($token, $targetUser->email));
    }

}
