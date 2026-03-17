<?php

namespace App\Http\Controllers;

use App\Mail\Disable2faMail;
use App\Mail\EmailVerificationCodeMail;
use App\Mail\Enable2faMail;
use App\Mail\LoginVerificationMail;
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

    /**
     * Sends an email to user with a code to enable or disable 2FA, depending on the $enables2fa parameter.
     * @param User $targetUser The user to whom the email verification code will be sent.
     * @param bool $enables2fa Indicates whether the email is for enabling or disabling 2FA, used to determine the email content.
     */
    public static function sendToggle2faEmail(User $targetUser, bool $enables2fa)
    {
        $token = self::generateToken(6);
        $expiryMinutes = 15;

        Verify2fa::updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'token' => $token,
                'expires_at' => now()->addMinutes($expiryMinutes),
                'enables_2fa' => $enables2fa,
            ]
        );

        $emailToSend = $enables2fa 
            ? new Enable2faMail($token, $targetUser->email, $targetUser->name) 
            : new Disable2faMail($token, $targetUser->email, $targetUser->name);

        Mail::to($targetUser)->send($emailToSend);
    }

    /**
    * Sends a login verification email with a code to the specified user.
    * @param User $targetUser The user to whom the login verification email will be sent.
    */
    public static function sendLoginVerification(User $targetUser)
    {
        $token = self::generateToken(6);
        $expiryMinutes = 15;

        Verify2fa::updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'token' => $token,
                'expires_at' => now()->addMinutes($expiryMinutes),
                'enables_2fa' => null, // No change to 2FA status, just a login verification
            ]
        );

        Mail::to($targetUser)->send(new LoginVerificationMail($token, $targetUser->email));
    }
}
