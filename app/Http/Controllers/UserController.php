<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index()
    {
        //
    }

    public function register(RegisterUserRequest $request)
    {
        $user = User::create($request->validated());

        EmailController::sendEmailVerificationCode($user);

        return response()->json($user, 201);
    }

    public function login(LoginUserRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if(!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if(!$user->email_verified_at) {
            return response()->json(['message' => 'Email not verified'], 403);
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

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        $verification = EmailVerification::where('user_id', $user->id)->first();

        if (!$verification || !Hash::check($request->verification_code, $verification->token) || $verification->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired verification code'], 400);
        }

        $user->email_verified_at = now();
        $user->save();

        // Delete the verification record after successful verification
        $verification->delete();

        return response()->json(['message' => 'Email verified successfully']);
    }
}
