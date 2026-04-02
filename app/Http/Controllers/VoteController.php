<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use Illuminate\Http\Request;
use App\Http\Requests\VoteRequest;
use App\Models\Post;

class VoteController extends Controller
{
    /**
     * Cast or update a vote on a post. If the same vote already exists, it will be removed (toggle behavior).
     */
    public function vote(VoteRequest $request, Post $post)
    {
        $validated = $request->validated();
        $userId = $request->user()->id;

        $existingVote = Vote::where('post_id', $post->id)->where('user_id', $userId)->first();
        if ($existingVote && $existingVote->is_upvote === $validated['is_upvote']) {
            $existingVote->delete();
            return response()->noContent();
        }

        $vote = Vote::updateOrCreate(
            ['post_id' => $post->id, 'user_id' => $userId],
            ['is_upvote' => $validated['is_upvote']]
        );

        return response()->json($vote, 201);
    }

    public function have_i_voted(Request $request, Post $post){
        $userId = $request->user()->id;
        $existingVote = Vote::where('post_id', $post->id)->where('user_id', $userId)->first();

        if (!$existingVote) {
            return response()->json(['message' => 'No vote found'], 404);
        }

        return response()->json($existingVote, 200);
    }
}
