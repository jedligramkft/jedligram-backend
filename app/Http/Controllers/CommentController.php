<?php

namespace App\Http\Controllers;

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
        $comments = Comment::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with([
                'user',
                'descendants' => function ($query) {

                    $query->where('depth', '<=', 3)->with('user');
                }
            ])
            ->get()
            ->toTree();

        return response()->json(CommentResource::collection($comments), 200);
    }

    public function replies(Comment $comment){
        $replies = $comment->descendants()->with('user')->get()->toTree();
        return response()->json(CommentResource::collection($replies), 200);
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
