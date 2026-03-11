<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph(),
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null
        ];
    }

    public function reply(?Comment $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parent = $parent ?? Comment::factory()->create();

            return [
                'parent_id' => $parent->id,
                'post_id' => $parent->post_id,
            ];
        });
    }
}
