<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use Illuminate\Http\Request;
use App\Http\Requests\VoteRequest;
use App\Models\Post;

class VoteController extends Controller
{
    public function vote(VoteRequest $request, Post $post){
        $validated = $request->validated();
        $userId = $request->user()->id;

        $existingVote = Vote::where('post_id', $post->id)->where('user_id', $userId)->first();
        if($existingVote && $existingVote->is_upvote == $request->is_upvote){
            $existingVote->delete();
            return response()->noContent();
        }

        $vote = Vote::updateOrCreate(
            ['post_id' => $post->id, 'user_id' => $userId],
            ['is_upvote' => $request->is_upvote]
        );

        return response()->json($vote, 201);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
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
    public function show(Vote $vote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vote $vote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vote $vote)
    {
        //
    }
}
