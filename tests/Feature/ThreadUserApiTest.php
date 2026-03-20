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

        dbHas();
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

describe('Leaving threads', function () {
    beforeEach(function () {
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'role_id' => 3,
        ]);
    });

    test('authenticated members can leave the thread', function () {
        dbHas();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/{$this->thread->id}/leave");

        $response->assertStatus(200);

        dbMissing();
    });

    test('unauthenticated users cannot leave a thread', function () {
        $response = $this->deleteJson("/api/threads/{$this->thread->id}/leave");
        dbHas();
        $response->assertStatus(401);
    });

    test('users cannot leave a thread they are not a member of', function () {
        ThreadUser::where([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
        ])->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/{$this->thread->id}/leave");

        dbMissing();
        $response->assertStatus(403);
    });

    test('users cannot leave a non-existing thread', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/999/leave");

        dbHas();
        $response->assertStatus(404);
    });

    test('banned users cannot leave a thread', function () {
        ThreadUser::where([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
        ])->update(['role_id' => 4]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/{$this->thread->id}/leave");

        dbHas();
        $response->assertStatus(403);
    });
});

function dbHas()
{
    test()->assertDatabaseHas('thread_user', [
        'thread_id' => test()->thread->id,
        'user_id'   => test()->user->id,
    ]);
}

function dbMissing()
{
    test()->assertDatabaseMissing('thread_user', [
        'thread_id' => test()->thread->id,
        'user_id'   => test()->user->id,
    ]);
}
