<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'user' => new UserResource($this->user),
            'thread_id' => $this->thread_id,
            'score' => $this->score,
            'age' => $this->created_at->diffForHumans(),
            'is_mine' => $request->user() && $request->user()->id === $this->user_id,
            'my_vote' => $this->whenLoaded('myVote', fn () => $this->myVote?->is_upvote),
        ];
    }
}
