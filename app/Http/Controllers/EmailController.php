<?php

namespace App\Http\Controllers;

use App\Mail\Disable2faMail;
use App\Mail\EmailVerificationCodeMail;
use App\Mail\Enable2faMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\Verify2fa;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    /**
     * Generates a random token of the specified length.
     * @param int $length The length of the token to generate (default is 10).
     * @return string The generated token.
     */
    private static function generateToken(int $length = 10)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Sends a welcome email to the specified user.
     * @param User $targetUser The user to whom the welcome email will be sent.
     */
    public static function sendWelcomeEmail(string $email, string $name)
    {
        Mail::to($email)->send(new WelcomeMail($name));
    }

    public static function sendEnable2faEmail(User $targetUser)
    {
        $token = self::generateToken(6);
        $expiryMinutes = 15;

        Verify2fa::updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'token' => $token,
                'expires_at' => now()->addMinutes($expiryMinutes),
                'enables_2fa' => true,
            ]
        );

        Mail::to($targetUser)->send(new Enable2faMail($token, $targetUser->email, $targetUser->name));
    }

    public static function sendDisable2faEmail(User $targetUser)
    {
        $token = self::generateToken(6);
        $expiryMinutes = 15;

        Verify2fa::updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'token' => $token,
                'expires_at' => now()->addMinutes($expiryMinutes),
                'enables_2fa' => false,
            ]
        );

        Mail::to($targetUser)->send(new Disable2faMail($token, $targetUser->email, $targetUser->name));
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
}
