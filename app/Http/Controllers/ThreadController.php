<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use Illuminate\Http\Request;
use App\Http\Requests\StoreThreadRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\ThreadResource;

class ThreadController extends Controller
{
    /**
     * List threads. Supports `search` query parameter for text search.
     */
    public function index(Request $request)
    {
        if ($request->filled('search')) {
            $threads = Thread::search($request->input('search'))->get();
            if ($threads->isNotEmpty()) {
                return response()->json(ThreadResource::collection($threads->loadCount('users')), 200);
            }
        }
        // it works funny
        $allthreads = Thread::withCount('users')->get();
        return response()->json(ThreadResource::collection($allthreads), 200);
    }

    /**
     * Create a new thread and attach the creator as a member.
     */
    public function store(StoreThreadRequest $request)
    {
        $thread = Thread::create($request->validated());
        $thread->users()->attach($request->user()->id, ['role_id' => 1]);
        return response()->json(ThreadResource::make($thread), 201);
    }

    /**
     * Get thread details, including member count.
     */
    public function show(Thread $thread)
    {
        return response()->json(ThreadResource::make($thread->loadCount('users')), 200);
    }

    /**
     * Join the authenticated user to the given thread.
     */
    public function join(Request $request, Thread $thread)
    {
        if ($thread->users->contains($request->user())) {
            return response()->json(['message' => 'You are already a member of this thread'], 409);
        }
        $thread->users()->syncWithoutDetaching([$request->user()->id, ['role_id' => 3]]);
        return response()->json(['message' => 'You joined the thread'], 200);
    }

    /**
     * Remove the authenticated user from the given thread.
     */
    public function leave(Request $request, Thread $thread)
    {
        if (!$thread->users->contains($request->user())) {
            return response()->json(['message' => 'You are not a member of this thread'], 422);
        }
        $thread->users()->detach($request->user()->id);
        return response()->json(['message' => 'You left the thread'], 200);
    }

    /**
     * List posts for a thread. Accepts `sort=trending` to sort by trending metric.
     */
    public function postsOfThread(Thread $thread, Request $request)
    {
        $posts = $thread->posts()->withCount(['upvotes', 'downvotes'])->when($request->query('sort') === 'trending', function ($query) {
            return $query->orderByRaw('(upvotes_count - downvotes_count) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2) DESC');
        }, function ($query) {
            return $query->latest();
        })->get();
        return response()->json(PostResource::collection($posts), 200);
    }

    public function members(Thread $thread)
    {
        $members = $thread->users()->withPivot('role_id')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->pivot->role_id,
            ];
        });

        return response()->json($members, 200);
        // return response()->json($thread->users, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Thread $thread)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Thread $thread)
    {
        //
    }
}
