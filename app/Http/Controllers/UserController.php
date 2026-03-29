<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadProfilePictureRequest;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Verify2fa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $user = Auth::user();

        if ($user->is_2fa_enabled) {
            try {
                EmailController::sendLoginVerification($user);

                return response()->json([
                    "requires_verification" => true, 
                    "message"=>"Verification code sent to email"
                ], 202);
                
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
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Retrieve user details by ID.
     */
    public function show(User $user)
    {
        return response()->json(UserResource::make($user), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * List the threads that have the user as a member.
     */
    public function threadsOfUser(User $user)
    {
        return response()->json(ThreadResource::collection($user->threads->loadCount('users')), 200);
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
     * Verify the 2FA token sent to the user's email for either login verification or toggling 2FA. The function checks the provided token against the stored hashed token in the Verify2fa model, ensuring it is valid and not expired. If the verification is for login, it issues an authentication token upon success. If it's for toggling 2FA, it updates the user's 2FA status accordingly.
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'verification_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $verification = Verify2fa::where('user_id', $user->id)->first();

        if (!$verification || !Hash::check($request->verification_code, $verification->token) || $verification->expires_at->isPast()) {
            return response()->json(['message' => 'Verification record not found or expired'], 404);
        }

        $isLoggingIn = $verification->enables_2fa === null;

        if($isLoggingIn) {
            $verification->delete(); // Delete the verification record after successful verification
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } else {
            // Update the user's 2FA status based on the enables_2fa field in the verification record
            $user->is_2fa_enabled = $verification->enables_2fa ?? $user->is_2fa_enabled;
            $user->save();

            $verification->delete(); // Delete the verification record after successful verification

            return response()->json(['message' => '2FA verification successful']);
        }

        return response()->json(['message' => 'Verification record found', 'verification' => $verification], 200);
    }

    /**
     * Toggle 2FA for the authenticated user. This function sends an email with a verification code to the user's email address, which they must verify to complete the toggle action. The email content and the verification process are handled based on whether 2FA is being enabled or disabled.
     */
    public function toggle2fa(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $enables2fa = !$user->is_2fa_enabled;

        try {
            EmailController::sendToggle2faEmail($user, $enables2fa);

            return response()->json([
                    "requires_verification" => true, 
                    "message"=>"Verification code sent to email"
            ], 202);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'Failed to send toggle 2FA email: ' . $exception->getMessage()], 418);
        }

        return response()->json(['message' => 'Toggle 2FA verification code sent to email. Please verify to complete the action.']);
    }

    /**
     * Check if 2FA is enabled for the authenticated user. This function returns a JSON response indicating whether 2FA is currently enabled for the user, allowing the frontend to adjust its behavior accordingly (e.g., prompting for a verification code during login if 2FA is enabled).
     */
    public function is2faEnabled(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json(['is_2fa_enabled' => $user->is_2fa_enabled]);
    }
}
