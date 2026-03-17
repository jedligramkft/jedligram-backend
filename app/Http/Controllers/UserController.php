<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Models\Verify2fa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        //
    }

    public function register(RegisterUserRequest $request)
    {
        $user = User::create($request->validated());

        return response()->json($user, 201);
    }

    public function login(LoginUserRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if(!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        if($user->is_2fa_enabled){
            try {
                EmailController::sendLoginVerification($user);
            } catch (Throwable $exception) {
                Log::error('Failed to send login verification email.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);
                return response()->json(['message' => 'Failed to send login verification email: ' . $exception->getMessage()], 500);
            }
            
            return response()->json(['message' => 'Login verification code sent to email. Please verify to complete login.']);
        }

        // Automatically mark that the welcome email was handled to prevent duplicate sends.
        $isFirstSuccessfulLogin = User::whereKey($user->id)
            ->whereNull('welcome_email_sent_at')
            ->update(['welcome_email_sent_at' => now()]) === 1;

        if ($isFirstSuccessfulLogin) {
            try {
                EmailController::sendWelcomeEmail($user->email, $user->name);
            } catch (Throwable $exception) {
                Log::error('Failed to send welcome email on first login.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function show(User $user)
    {
        //
    }

    public function update(Request $request, User $user)
    {
        //
    }

    public function destroy(User $user)
    {
        //
    }

    public function verifyLogin(Request $request){
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $verification = Verify2fa::where('user_id', $user->id)->whereNull('enables_2fa')->first();

        if (!$verification || !Hash::check($request->verification_code, $verification->token) || $verification->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired verification code'], 400);
        }

        // Delete the verification record after successful verification
        $verification->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $verification = Verify2fa::where('user_id', $user->id)->whereNotNull("enables_2fa")->first();

        $isTokenValid = $verification
            && (Hash::check($request->verification_code, $verification->token));

        if (!$verification || !$isTokenValid || $verification->expires_at->isPast()) {
            Log::warning('2FA verification failed', [
                'email' => $request->email,
                'has_verification_row' => (bool) $verification,
                'token_match' => $isTokenValid,
                'is_expired' => $verification ? $verification->expires_at->isPast() : null,
            ]);

            return response()->json(['message' => 'Invalid or expired verification code'], 400);
        }

        // Update the user's 2FA status based on the enables_2fa field in the verification record
        $user->is_2fa_enabled = $verification->enables_2fa ?? $user->is_2fa_enabled;
        $user->save();

        // Delete the verification record after successful verification
        $verification->delete();

        return response()->json(['message' => '2FA verification successful']);
    }
}
