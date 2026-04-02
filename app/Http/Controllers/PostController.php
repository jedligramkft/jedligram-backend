<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\Request;
use App\Http\Requests\CreatePostRequest;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Create a new post in the specified thread. Requires authenticated user.
     */
    public function store(CreatePostRequest $request, Thread $thread)
    {
        $thread = Thread::findOrFail($thread->id);
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;
        $data['thread_id'] = $thread->id;
        $post = Post::create($data);

        if ($data['image']) {
            $this->handleImageUpload($thread, $post, $data['image']);
        }

        return response()->json(PostResource::make($post), 201);
    }

    /**
     * Retrieve a single post by ID.
     */
    public function show(Post $post)
    {
        return response()->json(PostResource::make($post), 200);
    }

    /**
     * Delete a post or remove as moderator.
     */
    public function destroy(Request $request, Thread $thread, Post $post)
    {
        if ($request->user()->id == $post->user_id) {
            $post->update(['content' => '[deleted]', 'image' => null]);
            return response()->json(['message' => 'Post deleted'], 200);
        }

        $post->update(['content' => '[removed]', 'image' => null]);
        return response()->json(['message' => 'Post removed'], 200);
    }

    protected function handleImageUpload(Thread $thread, Post $post, $image)
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }
        $path = $image->store("threads/{$thread->id}/posts/{$post->id}", 'public');
        $post->update(['image' => $path]);
    }
}
