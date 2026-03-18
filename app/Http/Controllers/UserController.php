<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadProfilePictureRequest;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Verify2fa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * List users. Supports `search` query parameter for text search.
     */
    public function index(Request $request)
    {
        if ($request->filled('search')) {
            $users = User::search($request->input('search'))->get();
            if ($users->isEmpty()) {
                return response()->json(User::all()->toResourceCollection(), 200,  [], JSON_UNESCAPED_SLASHES);
            }
            return response()->json(UserResource::collection($users), 200, [], JSON_UNESCAPED_SLASHES);
        }
        return response()->json(User::all()->toResourceCollection(), 200, [], JSON_UNESCAPED_SLASHES);
    }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function register(RegisterUserRequest $request)
    // {
    //     $user = User::create($request->validated());
    //     return response()->json($user, 201);
    // }

    /**
     * Authenticate user and return a bearer token.
     */
    public function login(LoginUserRequest $request)
    {
        $RawCredentials = $request->validated();

        $credentials = [
            'samaccountname' => $RawCredentials['username'],
            'password' => $RawCredentials['password']
        ];

        if (!Auth::attempt($credentials)) {
            return $this->tryAuthWithoutLdap($request);
        }
        $user = Auth::user();

        if ($user->is_2fa_enabled) {
            try {
                EmailController::sendLoginVerification($user);
            } catch (\Throwable $exception) {
                Log::error('Failed to send login verification email.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);

                return response()->json(['message' => 'Failed to send login verification email: ' . $exception->getMessage()], 418);
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
            } catch (\Throwable $exception) {
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
            'user' => UserResource::make($user)
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Revoke the current user's access token (logout).
     */
    public function logout(Request $request)
    {
        // TODO error message
        Auth::logout();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Retrieve user details by ID.
     */
    public function show(User $user)
    {
        return response()->json(UserResource::make($user), 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function postOfUser(User $user)
    {
        // TODO: create a resource for threads and use it here
        return response()->json(ThreadResource::collection($user->threads), 200);
    }

    /**
     * Update user profile information.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return response()->json(UserResource::make($user), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Upload or replace profile picture for authenticated user.
     */
    public function uploadPfP(UploadProfilePictureRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $path = $request->file('image')->store('pfps', 'public');

        $user->update(['image' => $path]);

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'user' => UserResource::make($user),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Remove the specified resource from storage.
     */
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

    public function tryAuthWithoutLdap(LoginUserRequest $request)
    {
        $RawCredentials = $request->validated();

        if ($RawCredentials['username'] !== 'admin' || $RawCredentials['password'] !== 'admin') {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', 'admin@example.com')
            ->orWhere('name', 'admin')
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin'),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful WITHOUT LDAP',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
