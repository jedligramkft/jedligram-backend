<?php

use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductionDataSeeder::class);
});


test('it can fetch a list of all threads', function () {
    Thread::factory(count: 3)->create();
    $response = $this->getJson('/api/threads');

    $response->assertStatus(200)
    ->assertJsonCount(Thread::count(), 'data');
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

    test('authenticated members see my_role as role name', function () {
        $thread = Thread::factory()->create();
        $user = User::factory()->create();

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'role_id' => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/{$thread->id}");

        $response->assertStatus(200)
            ->assertJsonPath('my_role', 'moderator');
    });

    test('authenticated non members see my_role as null', function () {
        $thread = Thread::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/threads/{$thread->id}");

        $response->assertStatus(200)
            ->assertJsonPath('my_role', null);
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

        $response->assertStatus(200);
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

describe('Viewing the posts of a thread includes my_vote', function () {
    test('my_vote is true when authenticated user upvoted', function () {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $thread = Thread::factory()->create();

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
            'role_id' => 3,
        ]);

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $viewer->id,
            'role_id' => 3,
        ]);

        $post = Post::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        Vote::create([
            'post_id' => $post->id,
            'user_id' => $viewer->id,
            'is_upvote' => true,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson("/api/threads/{$thread->id}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.my_vote', true);
    });

    test('my_vote is false when authenticated user downvoted', function () {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $thread = Thread::factory()->create();

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
            'role_id' => 3,
        ]);

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $viewer->id,
            'role_id' => 3,
        ]);

        $post = Post::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        Vote::create([
            'post_id' => $post->id,
            'user_id' => $viewer->id,
            'is_upvote' => false,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson("/api/threads/{$thread->id}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.my_vote', false);
    });

    test('my_vote is null when authenticated user has not voted', function () {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $thread = Thread::factory()->create();

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
            'role_id' => 3,
        ]);

        ThreadUser::create([
            'thread_id' => $thread->id,
            'user_id' => $viewer->id,
            'role_id' => 3,
        ]);

        $post = Post::factory()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson("/api/threads/{$thread->id}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.my_vote', null);
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

    test('it should return 422 when trying to create a thread with a name that already exists', function (array $validData) {
        $user = User::factory()->create();
        $existingThread = Thread::factory()->create([
            'name' => "New test thread"
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/threads', $validData);
        $response->assertStatus(422);
    })->with("valid_thread_data");
});
