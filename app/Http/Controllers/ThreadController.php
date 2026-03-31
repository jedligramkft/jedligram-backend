<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use Illuminate\Http\Request;
use App\Http\Requests\StoreThreadRequest;
use App\Http\Requests\UpdateThreadRequest;
use App\Http\Requests\UploadHeaderImageRequest;
use App\Http\Requests\UploadThreadImageRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\ThreadResource;
use Illuminate\Support\Facades\Storage;

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
                return response()->json(ThreadResource::collection($threads->loadCount('users')), 200, [], JSON_UNESCAPED_SLASHES);
            }
        }
        $allthreads = Thread::withCount('users')->get();
        return response()->json(ThreadResource::collection($allthreads), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create a new thread and attach the creator as a member.
     */
    public function store(StoreThreadRequest $request)
    {
        $thread = Thread::create($request->validated());
        $thread->users()->attach($request->user()->id, ['role_id' => 1]);
        return response()->json(ThreadResource::make($thread), 201, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Update the thread
     */

    public function update(Thread $thread, UpdateThreadRequest $request)
    {
        $validated = $request->validated();

        $thread->update($validated);

        return response()->json(ThreadResource::make($thread), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get thread details, including member count.
     */
    public function show(Thread $thread)
    {
        return response()->json(ThreadResource::make($thread->loadCount('users')), 200, [], JSON_UNESCAPED_SLASHES);
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
        return response()->json(PostResource::collection($posts), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Upload thread image
     */
    public function threadImage(UploadThreadImageRequest $request, Thread $thread)
    {
        $validated = $request->validated();

        if ($thread->image) {
            Storage::disk('public')->delete($thread->image);
        }

        $path = $validated['image']->store('threadImages', 'public');

        $thread->update(['image' => $path]);

        return response()->json([
            'message' => 'Thread image updated successfully',
            'thread' => ThreadResource::make($thread),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Upload a header image for the thread
     */
    public function headerImage(UploadHeaderImageRequest $request, Thread $thread)
    {
        $validated = $request->validated();

        if ($thread->header) {
            Storage::disk('public')->delete($thread->header);
        }

        $path = $validated['header']->store('headers', 'public');

        $thread->update(['header' => $path]);

        return response()->json([
            'message' => 'Thread header updated successfully',
            'thread' => ThreadResource::make($thread),
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
