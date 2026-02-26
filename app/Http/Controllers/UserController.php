<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadProfilePictureRequest;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function register(RegisterUserRequest $request)
    {
        $user = User::create($request->validated());
        return response()->json(UserResource::make($user), 201, [], JSON_UNESCAPED_SLASHES);
    }

    public function login(LoginUserRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user)
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Display the specified resource.
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
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return response()->json(UserResource::make($user), 200, [], JSON_UNESCAPED_SLASHES);

    }

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
}
