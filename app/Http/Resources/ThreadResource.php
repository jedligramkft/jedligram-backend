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
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'header' => $this->header ? asset('storage/' . $this->header) : null,
            'my_role' => $this->when($this->relationLoaded('membership'), function () {
                return $this->membership?->role?->id;
            }),
        ];
    }
}
