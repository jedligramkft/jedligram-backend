<?php

use App\Models\Thread;
use App\Models\ThreadUser;
use App\Models\User;
use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProductionDataSeeder::class);

    $this->thread = Thread::factory()->create();
    $this->user = User::factory()->create();
});

describe('Joining threads', function () {
    test('authenticated user can join a thread', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/threads/{$this->thread->id}/join");

        $response->assertStatus(200);
    });

    test('unauthenticated user cannot join a thread', function () {
        $response = $this->postJson("/api/threads/{$this->thread->id}/join");

        $response->assertStatus(401);
    });

    test('user cannot join a thread they are already a member of, and should receive a 409 Conflict response', function () {
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'role_id' => 3,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/threads/{$this->thread->id}/join");

        $response->assertStatus(409);
    });

    test('user cannot join a non-existing thread', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/threads/999/join");

        $response->assertStatus(404);
    });
});
