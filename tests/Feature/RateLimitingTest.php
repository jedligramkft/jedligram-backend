<?php

use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductionDataSeeder::class);
});


test('creation rate limiting should return 429 after too many requests', function () {
    $user = User::factory()->create();
    $thread = Thread::factory()->create();

    ThreadUser::create([
        'thread_id' => $thread->id,
        'user_id' => $user->id,
        'role_id' => 3,
    ]);

    for ($i = 0; $i < 10; $i++) {
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/threads/{$thread->id}/post", [
                'content' => "Post content {$i}",
            ]);

        $response->assertStatus(201);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/threads/{$thread->id}/post", [
            'content' => "This post should be rate limited",
        ]);

    $response->assertStatus(429);
});

test('login rate limiting should return 429 after too many attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'username' => 'wronguser',
            'password' => 'wrongpassword'
        ])->assertStatus(401);
    }

    $this->postJson('/api/login', [
        'username' => 'wronguser',
        'password' => 'wrongpassword'
    ])->assertStatus(429);
});
