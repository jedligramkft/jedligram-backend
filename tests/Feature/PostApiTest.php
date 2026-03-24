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
});

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

    test('unathenticated user cannot create a post inside the thread', function (array $validData) {
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

    test('authenticated unauthorized user cannot create a post inside of a thread', function (array $validData) {
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

    test('trying to create a post inside of a non existent thread should return 404', function (array $validData) {
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

    test('banned users cannot create a post in the thread', function (array $validData) {
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
});

describe('Post deletion', function () {
    beforeEach(function () {
        $this->thread =  Thread::create([
            'name' => 'Test Thread',
            'description' => 'A thread for testing post deletion',
            'rules' => 'Be respectful',
        ]);

        $this->user = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'role_id' => 3,
        ]);

        $this->post = Post::factory()->create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test post',
        ]);
    });

    test('authenticated members can delete their own posts', function () {
        dbHas();
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(200);

        dbMissing();
    });

    test('admin users can remove any post', function () {
        $adminUser = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $adminUser->id,
            'role_id' => 1,
        ]);

        dbHas();

        $response = $this->actingAs($adminUser, 'sanctum')
            ->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(200);

        dbMissing();
    });

    test('moderator users can remove any post', function () {
        $moderatorUser = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $moderatorUser->id,
            'role_id' => 2,
        ]);

        dbHas();

        $response = $this->actingAs($moderatorUser, 'sanctum')
            ->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(200);

        dbMissing();
    });

    test('users cannot delete other users posts', function () {
        $otherUser = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $otherUser->id,
            'role_id' => 3,
        ]);

        dbHas();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(403);

        dbHas();
    });

    test('unauthenticated users cannot delete posts', function () {
        dbHas();

        $response = $this->deleteJson("/api/posts/{$this->post->id}");

        $response->assertStatus(401);
        dbHas();
    });

    test('trying to delete non-existent post should return 404', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/posts/999");

        $response->assertStatus(404);
    });
});

describe('Fetching a single post', function(){
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->thread = Thread::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'role_id' => 3,
        ]);

        $this->post = Post::factory()->create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
        ]);
    });

    test('authenticated members can fetch a single post of the thread', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/posts/{$this->post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->post->id,
                'content' => $this->post->content,
                'user_id' => $this->post->user_id,
                'thread_id' => $this->post->thread_id,
                'score' => 0,
            ]);
    });

    test('unauthenticated users cannot fetch a single post of the thread', function () {
        $response = $this->getJson("/api/posts/{$this->post->id}");

        $response->assertStatus(401);
    });

    test('non members cannot fetch a single post of the thread', function () {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/posts/{$this->post->id}");

        $response->assertStatus(403);
    });

    test('trying to fetch a non existent post should return 404', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/posts/999");

        $response->assertStatus(404);
    });
});

function dbHas()
{
    test()->assertDatabaseHas('posts', [
        'id' => test()->thread->id,
    ]);
}

function dbMissing()
{
    test()->assertSoftDeleted('posts', ['id' => test()->post->id]);
}
