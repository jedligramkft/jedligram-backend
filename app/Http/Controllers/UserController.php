<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadProfilePictureRequest;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\UserResource;
use App\Models\User;
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

        // if (!Auth::attempt($credentials)) {
        //     return response()->json(['message' => 'Invalid credentials'], 401);
        // }
        // $user = Auth::user();
        if (!Auth::attempt($credentials)) {
            return $this->tryAuthWithoutLdap($request);
        }
        $user = Auth::user();

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
