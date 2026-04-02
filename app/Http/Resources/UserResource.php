<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [
                'id' => null,
                'name' => "[Deleted User]",
                'email' => null,
                'image_url' => asset('images/default_pfp.png')
            ];
        }
        return [
            'id' => $this->id,
            'name' => $this->display_name ?? $this->name,
            'email' => $this->display_email ?? $this->email,
            'image_url' => $this->image
                ? asset('storage/' . $this->image)
                : asset('images/default_pfp.png'),
            'bio' => $this->bio,
            'role_id' => $this->whenPivotLoaded('thread_user', function () {
                return $this->pivot->role_id;
            }),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }
}
