<?php

use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductionDataSeeder::class);
});


test('it can fetch a list of all threads', function () {
    Thread::factory(count: 3)->create();
    $response = $this->getJson('/api/threads');

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => ['id', 'name', 'description', 'rules']
        ]);
});

describe('Viewing the details of a thread', function () {

    test('authenticated members can view the details of a thread', function () {
        $thread = Thread::factory()->create();
        $user = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 3
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/{$thread->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'rules',
                'users_count',
            ]);
    });

    test('authenticated users can view the details of a thread', function () {
        $thread = Thread::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/{$thread->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $thread->id,
                'name' => $thread->name,
                'description' => $thread->description,
                'rules' => $thread->rules,
                'users_count' => $thread->users_count,
            ]);
    });

    test('unauthenticated users cannot view the details of a thread', function () {
        $thread = Thread::factory()->create();

        $response = $this->getJson("/api/threads/{$thread->id}");

        $response->assertStatus(401);
    });

    test('users banned from the thread cannot view the details', function () {
        $thread = Thread::factory()->create();
        $user = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 4
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/{$thread->id}");

        $response->assertStatus(403);
    });

    test('it returns 404 for non existent threads', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/999");
        $response->assertStatus(404);
    });
});

describe('Viewing the posts of a thread', function () {
    beforeEach(function () {
        $users = User::factory(20)->create();

        $threads = Thread::factory(2)->create()
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

    test('authenticated users can view the posts of a thread', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/1/posts");

        $response->assertStatus(200)
            ->assertJsonStructure(['*' => [
                "id",
                "content",
                "user_id",
                "thread_id",
                "score",
                "age",
            ]]);
    });

    test('unauthenticated users cannot view the posts of a thread', function () {
        $response = $this->getJson("/api/threads/1/posts");

        $response->assertStatus(401);
    });

    test('users banned from the thread cannot view the posts', function () {
        $thread = Thread::factory()->create();
        $user = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 4,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/{$thread->id}/posts");

        $response->assertStatus(403);
    });

    test('trying to view a non existent thread should return 404', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/999/posts");

        $response->assertStatus(404);
    });
});

describe('Creating a thread', function () {
    test('authenticated members can create a thread', function (array $validData) {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('api/threads', $validData);

        $response->assertStatus(201)
            ->assertJson([
                'name'        => $validData['name'],
                'description' => $validData['description'],
                'rules'       => $validData['rules'],
            ]);
        $this->assertDatabaseHas('threads', [
            'name'        => $validData['name'],
            'description' => $validData['description'],
            'rules'       => $validData['rules'],
        ]);
    })->with("valid_thread_data");

    test('unauthenticated users cannot create a thread', function (array $validData) {
        $response = $this->postJson('api/threads', $validData);

        $response->assertStatus(401);
    })->with("valid_thread_data");

    test('it should return 422 when trying to create a thread with invalid data', function (array $invalidData, string $expectedErrorField) {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/threads', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([$expectedErrorField]);
    })->with("invalid_thread_data");


    //TODO: test for name unique constraint
    // test('it should return 4')
});
