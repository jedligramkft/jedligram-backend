<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleRequest;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use Illuminate\Http\Request;

class ThreadUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ThreadUser $threadUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ThreadUser $threadUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ThreadUser $threadUser)
    {
        //
    }

    /**
     * Update the role of a user in a thread. Only admins can assign roles, and banned role cannot be assigned through this method.
     */
    public function assignRole(AssignRoleRequest $request, Thread $thread, User $user){
        $validated = $request->validated();
        if($validated['role_id'] == 4){
            return response()->json(['message' => 'Cannot assign banned role'], 400);
        }

        if (!$thread->users->contains($user->id)) {
            return response()->json(['message' => 'User is not a member of this thread'], 422);
        }

        $thread->users()->updateExistingPivot($user->id, ['role_id' => $validated['role_id']]);
        return response()->json(['message' => 'Role updated successfully' ], 200);
    }

    /**
     * Ban a user from a thread. This is done by assigning the banned role to the user in the thread. Only admins and moderators can ban users.
     */
    public function ban(AssignRoleRequest $request, Thread $thread, User $user){
        $validated = $request->validated();
        if($validated['role_id'] != 4){
            return response()->json(['message' => 'Cannot assign non-banned role'], 400);
        }
        if (!$thread->users->contains($user->id)) {
            return response()->json(['message' => 'User is not a member of this thread'], 422);
        }
        $thread->users()->updateExistingPivot($user->id, ['role_id' => $validated['role_id']]);
        return response()->json(['message' => 'User banned successfully' ], 200);

        //TODO : test if the prevention of self-ban works correctly
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ThreadUser $threadUser)
    {
        //
    }
}
