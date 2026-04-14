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
    $this->adminUser = User::factory()->create();
    ThreadUser::create([
        'thread_id' => $this->thread->id,
        'user_id' => $this->adminUser->id,
        'role_id' => 1,
    ]);
    $this->modUser = User::factory()->create();
    ThreadUser::create([
        'thread_id' => $this->thread->id,
        'user_id' => $this->modUser->id,
        'role_id' => 2,
    ]);
});

describe('Joining threads', function () {
    test('authenticated user can join a thread', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/threads/{$this->thread->id}/join");

        dbHasUser();
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
        dbHasUser();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/{$this->thread->id}/leave");

        $response->assertStatus(200);

        dbMissingUser();
    });

    test('unauthenticated users cannot leave a thread', function () {
        $response = $this->deleteJson("/api/threads/{$this->thread->id}/leave");
        dbHasUser();
        $response->assertStatus(401);
    });

    test('users cannot leave a thread they are not a member of', function () {
        ThreadUser::where([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
        ])->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/{$this->thread->id}/leave");

        dbMissingUser();
        $response->assertStatus(403);
    });

    test('users cannot leave a non-existing thread', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/999/leave");

        dbHasUser();
        $response->assertStatus(404);
    });

    test('banned users cannot leave a thread', function () {
        ThreadUser::where([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
        ])->update(['role_id' => 4]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/threads/{$this->thread->id}/leave");

        dbHasUser();
        $response->assertStatus(403);
    });
});

describe('Listing the members of a thread', function () {
    beforeEach(function () {
        $this->adminUser = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->adminUser->id,
            'role_id' => 1,
        ]);
        $this->modUser = User::factory()->create();
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->modUser->id,
            'role_id' => 2,
        ]);
    });

    test('Admin members can see the list of the threads members', function () {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/threads/{$this->thread->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'email', 'role_id'],
            ]);
    });

    test('Moderator members can see the list of the threads members', function () {
        $response = $this->actingAs($this->modUser, 'sanctum')
            ->getJson("/api/threads/{$this->thread->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'email', 'role_id'],
            ]);
    });

    test('Regular members can see the list of the threads members', function () {
        $this->thread->users()->attach($this->user->id);
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/threads/{$this->thread->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'email', 'role_id'],
            ]);
    });

    test('Unauthenticated users cannot see the list of the threads members', function () {
        $response = $this->getJson("/api/threads/{$this->thread->id}/members");

        $response->assertStatus(401);
    });

    test('Users cannot see the list of the threads members if the thread does not exist', function () {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/threads/999/members");

        $response->assertStatus(404);
    });
});

describe('Assigning roles', function () {
    beforeEach(function () {
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'role_id' => 3,
        ]);
    });

    test('admin can assign roles', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}", $data);

        $response->assertStatus(200);
        dbHasUserRole($data['role_id']);
    })->with('valid_role_assignment_data');

    test('moderator cannot assign roles', function (array $data) {
        $response = $this->actingAs($this->modUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}", $data);

        $response->assertStatus(403);
        dbHasUserRole(3);
    })->with('valid_role_assignment_data');

    test('regular users cannot assign roles', function (array $data) {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}", $data);

        $response->assertStatus(403);
        dbHasUserRole(3);
    })->with('valid_role_assignment_data');

    test('admin cannot assign invalid roles', function (array $data, string $errorField, int $status) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}", $data);

        $response->assertStatus($status);
        if ($status === 422) {
            $response->assertJsonValidationErrors($errorField);
        } else {
            $response->assertJsonFragment(['message' => 'Cannot assign banned role']);
        }
        dbHasUserRole(3);
    })->with('invalid_role_assignment_data');

    test('admin cannot assign roles in a non-existing thread', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/999/members/{$this->user->id}", $data);

        $response->assertStatus(404);
        dbHasUserRole(3);
    })->with('valid_role_assignment_data');

    test('admin cannot assign roles to a non-existing user', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/999", $data);

        $response->assertStatus(404);
    })->with('valid_role_assignment_data');

    test('admin cannot assign roles if they are not a member of the thread', function (array $data) {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}", $data);

        $response->assertStatus(403);
        dbHasUserRole(3);
    })->with('valid_role_assignment_data');

    test('unathenticated admins cannot assing roles', function (array $data) {
        $response = $this->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}", $data);

        $response->assertStatus(401);
        dbHasUserRole(3);
    })->with('valid_role_assignment_data');
});

describe('Banning users', function () {
    beforeEach(function () {
        ThreadUser::create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
            'role_id' => 3,
        ]);
    });
    test('admins can ban a user', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}/ban", $data);

        $response->assertStatus(200);
        dbHasUserRole(4);
    })->with('valid_ban_data');

    test('admins cannot ban themselves', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->adminUser->id}/ban", $data);

        $response->assertStatus(403);
        $this->assertDatabaseHas('thread_user', [
            'thread_id' => $this->thread->id,
            'user_id'   => $this->adminUser->id,
            'role_id'   => 1,
        ]);
    })->with('valid_ban_data');

    test('moderators can ban users', function (array $data) {
        $response = $this->actingAs($this->modUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}/ban", $data);

        $response->assertStatus(200);
        dbHasUserRole(4);
    })->with('valid_ban_data');

    test('regular users cannot ban users', function (array $data) {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}/ban", $data);

        $response->assertStatus(403);
        dbHasUserRole(3);
    })->with('valid_ban_data');

    test('admins cannot ban users with invalid data', function (array $data, string $errorField, int $status) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}/ban", $data);

        $response->assertStatus($status);
        if ($status === 422) {
            $response->assertJsonValidationErrors($errorField);
        } else {
            $response->assertJsonFragment(['message' => 'Cannot assign non-banned role']);
        }
        dbHasUserRole(3);
    })->with('invalid_ban_data');

    test('admins cannot ban users in a non-existing thread', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/999/members/{$this->user->id}/ban", $data);

        $response->assertStatus(404);
        dbHasUserRole(3);
    })->with('valid_ban_data');

    test('admins cannot ban non-existing users', function (array $data) {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/threads/{$this->thread->id}/members/999/ban", $data);

        $response->assertStatus(404);
    })->with('valid_ban_data');

    test('unathenticated admins cannot ban users', function (array $data) {
        $response = $this->patchJson("/api/threads/{$this->thread->id}/members/{$this->user->id}/ban", $data);

        $response->assertStatus(401);
        dbHasUserRole(3);
    })->with('valid_ban_data');
});

function dbHasUser()
{
    test()->assertDatabaseHas('thread_user', [
        'thread_id' => test()->thread->id,
        'user_id'   => test()->user->id,
    ]);
}

function dbMissingUser()
{
    test()->assertDatabaseMissing('thread_user', [
        'thread_id' => test()->thread->id,
        'user_id'   => test()->user->id,
    ]);
}

function dbHasUserRole($roleId)
{
    test()->assertDatabaseHas('thread_user', [
        'thread_id' => test()->thread->id,
        'user_id'   => test()->user->id,
        'role_id'   => $roleId,
    ]);
}
