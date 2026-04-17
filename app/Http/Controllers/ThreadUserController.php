<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\Request;

class ThreadUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Thread $thread)
    {
        $members = $thread->users()
            ->withPivot('role_id')
            ->withPostKarmaCounts()
            ->orderBy('users.id', 'desc')
            ->cursorPaginate(5);
        return UserResource::collection($members)
            ->response()
            ->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }

    /**
     * Join the authenticated user to the given thread.
     */
    public function join(Request $request, Thread $thread)
    {
        if ($thread->users()->whereKey($request->user()->id)->exists()) {
            return response()->json(['message' => 'You are already a member of this thread'], 409);
        }
        $thread->users()->syncWithoutDetaching([$request->user()->id => ['role_id' => 3]]);
        return response()->json(['message' => 'You joined the thread'], 200);
    }

    /**
     * Remove the authenticated user from the given thread.
     */
    public function leave(Request $request, Thread $thread)
    {
        if (!$thread->users()->whereKey($request->user()->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this thread'], 422);
        }
        $thread->users()->detach($request->user()->id);
        return response()->json(['message' => 'You left the thread'], 200);
    }

    /**
     * Update the role of a user in a thread. Only admins can assign roles, and banned role cannot be assigned through this method. Can be used to unban members
     */
    public function assignRole(AssignRoleRequest $request, Thread $thread, User $user)
    {
        $validated = $request->validated();
        if ($validated['role_id'] == 4) {
            return response()->json(['message' => 'Cannot assign banned role'], 400);
        }

        if (!$thread->users->contains($user->id)) {
            return response()->json(['message' => 'User is not a member of this thread'], 422);
        }

        $thread->users()->updateExistingPivot($user->id, ['role_id' => $validated['role_id']]);
        return response()->json(['message' => 'Role updated successfully'], 200);
    }

    /**
     * Ban a user from a thread. This is done by assigning the banned role to the user in the thread. Only admins and moderators can ban users.
     */
    public function ban(AssignRoleRequest $request, Thread $thread, User $user)
    {
        $validated = $request->validated();
        if ($validated['role_id'] != 4) {
            return response()->json(['message' => 'Cannot assign non-banned role'], 400);
        }
        if (!$thread->users->contains($user->id)) {
            return response()->json(['message' => 'User is not a member of this thread'], 422);
        }
        $thread->users()->updateExistingPivot($user->id, ['role_id' => $validated['role_id']]);
        return response()->json(['message' => 'User banned successfully'], 200);
    }
}
