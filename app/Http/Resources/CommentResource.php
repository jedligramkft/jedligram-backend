<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'depth' => $this->depth,
            'user' => new UserResource($this->user),
            'age' => $this->created_at->diffForHumans(),
            'replies_count' => $this->whenCounted('children'),
            'replies' => CommentResource::collection($this->whenLoaded('children'))
        ];
    }

    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }
}
