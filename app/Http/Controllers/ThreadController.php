<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use Illuminate\Http\Request;
use App\Http\Requests\StoreThreadRequest;
use App\Http\Requests\CreatePostRequest;
use Illuminate\Support\Facades\Gate;

class ThreadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->filled('search')) {
            $threads = Thread::search($request->input('search'))->get();
            if ($threads->isEmpty()) {
                return response()->json(Thread::all());
            }
            return response()->json($threads);
        }
        // it works funny
        return response()->json(Thread::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreThreadRequest $request)
    {
        $thread = Thread::create($request->validated());
        $thread->users()->attach($request->user()->id, ['role_id' => 1]);
        return response()->json($thread, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Thread $thread)
    {
        return response()->json($thread, 200);
    }

    public function join(Request $request, Thread $thread)
    {
        if ($thread->users->contains($request->user())) {
            return response()->json(['message' => 'You are already a member of this thread'], 409);
        }
        $thread->users()->syncWithoutDetaching([$request->user()->id, ['role_id' => 3]]);
        return response()->json(['message' => 'You joined the thread'], 200);
    }

    public function leave(Request $request, Thread $thread)
    {
        if (!$thread->users->contains($request->user())) {
            return response()->json(['message' => 'You are not a member of this thread'], 422);
        }
        $thread->users()->detach($request->user()->id);
        return response()->json(['message' => 'You left the thread'], 200);
    }

    public function postsOfThread(Thread $thread, Request $request)
    {
        $posts = $thread->posts()->withCount(['upvotes', 'downvotes'])->when($request->query('sort') === 'trending', function ($query) {
            return $query->orderByRaw('(upvotes_count - downvotes_count) / (TIMESTAMPDIFF(HOUR, created_at, NOW()) + 2) DESC');
        }, function ($query) {
            return $query->latest();
        })->get();
        return response()->json($posts, 200);
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
