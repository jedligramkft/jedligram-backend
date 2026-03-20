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
    test('authenticated member can create a post inside the thread', function (array $validData) {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 3,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/threads/{$thread->id}/post", $validData);

        $response->assertStatus(201)
            ->assertJson([
                'content' => $validData['content'],
            ]);
    })->with('valid_post_data');

    test('unathenticated user cannot create a post inside the thread', function(array $validData) {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 3,
        ]);

        $response = $this->postJson("/api/threads/{$thread->id}/post", $validData);

        $response->assertStatus(401);
    })->with('valid_post_data');

    test('authenticated unauthorized user cannot create a post inside of a thread', function(array $validData){
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 4,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/threads/{$thread->id}/post", $validData);

        $response->assertStatus(403);
    })->with('valid_post_data');

    test('trying to create a post inside of a non existent thread should return 404', function(array $validData) {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/threads/999/post", $validData);

        $response->assertStatus(404);
    })->with('valid_post_data');

    test('it validates the post data when creating a new post', function (array $invalidData, string $missingField) {
        $user = User::factory()->create();
        $thread = Thread::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/threads/{$thread->id}/post", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($missingField);
    })->with('invalid_post_data');
});
