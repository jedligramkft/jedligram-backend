<?php

use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductionDataSeeder::class);

    $users = User::factory(20)->create();

    Thread::factory(2)->create()
        ->each(function ($thread) use ($users) {

            $users->random(rand(3, 10))->each(function ($user) use ($thread) {
                ThreadUser::create([
                    'thread_id' => $thread->id,
                    'user_id' => $user->id,
                    'role_id' => 3,
                ]);
            });

            Post::factory(2)->create([
                'thread_id' => $thread->id,
                'user_id' => $users->random()->id,
            ]);
        });
});

//TODO: figure out how to test posts
describe('Creating a new post inside of a thread', function () {
    test('authenticated member can create a post inside the thread', function () {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 3,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonCount(4)
            ->dump();
    });
});
