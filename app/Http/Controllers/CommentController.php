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

    public function replies(Comment $comment){
        $replies = $comment->descendants()->with('user')->get()->toTree();
        return response()->json(CommentResource::collection($replies), 200, [], JSON_UNESCAPED_SLASHES);
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
    public function store(StoreCommentRequest $request, Post $post)
    {
        // $request['post_id'] = $post->id;
        // $request['user_id'] = $request->user()->id;
        // $data = $request->validated();
        $comment = Comment::create($request->validated());
        return response()->json(new CommentResource($comment->load('user')), 201, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        //
    }
}
