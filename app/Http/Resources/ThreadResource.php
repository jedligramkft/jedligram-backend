<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'rules' => $this->rules,
            'users_count' => $this->whenCounted('users'),
            'my_role' => $this->when($this->relationLoaded('membership'), function () {
                return $this->membership?->role_id;
            }),
            'image' => $this->resolveImageUrl($this->image),
            'header' => $this->resolveImageUrl($this->header)
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'storage/') || str_starts_with($normalizedPath, 'images/')) {
            return asset($normalizedPath);
        }

        return asset('storage/' . $normalizedPath);
    }
}
