<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Post $post)
    {
        $comments = Comment::tree()
            ->where('post_id', $post->id)
            ->with('user')
            ->whereDepth('<', 3)
            ->get()
            ->toTree();

        return response()->json(CommentResource::collection($comments), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Display the replies of a comment.
     */
    public function replies(Comment $comment)
    {
        $replies = $comment->descendants()->with('user')->get()->toTree();
        return response()->json(CommentResource::collection($replies), 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request, Post $post)
    {
        $data = $request->validated();
        $data['post_id'] = $post->id;
        $data['user_id'] = $request->user()->id;

        $comment = Comment::create($data);
        return response()->json(new CommentResource($comment->load('user')), 201, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     *  Delete comment or remove as moderator.
     */
    public function destroy(Comment $comment, Post $post, Request $request)
    {
        // Ensure the comment belongs to the specified post context
        if ($comment->post_id !== $post->id) {
            abort(404);
        }

        if($request->user()->id == $comment->user_id) {
            $comment->update(['content' => '[deleted]']);
            return response()->json(['message' => 'Comment deleted'], 200);
        }

        $comment->update(['content' => '[removed]']);
        return response()->json(['message' => 'Comment removed'], 200);
    }
}
