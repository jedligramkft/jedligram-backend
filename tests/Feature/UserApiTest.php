<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('it can fetch the list of users', function () {
    User::factory(count: 3)->create();
    $response = $this->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => ['id', 'name', 'email', 'image_url', 'bio'],
        ]);
});

describe('Fethcing a single users', function () {
    test('it can fetch a single user', function () {
        $user = User::factory()->create();
        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image_url' => 'http://localhost/images/default_pfp.png',
                'bio' => $user->bio,
            ]);
    });

    test('it returns 404 for non-existing user', function () {
        $response = $this->getJson('/api/users/999');

        $response->assertStatus(404);
    });
});

describe('Profile picture upload', function () {
    test('authenticated user can upload a profile picture', function (string $filename, ?int $size) {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image($filename);
        if ($size) {
            $file->size($size);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/users/profile-picture', [
                'image' => $file,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Profile picture updated successfully',
            ]);
    })->with('valid_profile_picture_data');

    test('it rejects invalid profile picture uploads', function (?string $filename, ?int $size, ?string $mime, string $errorKey) {
        Storage::fake('public');
        $user = User::factory()->create();

        $payload = [];

        if ($filename) {
            $payload['image'] = UploadedFile::fake()->create($filename, $size ?? 0, $mime);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/users/profile-picture', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([$errorKey]);
    })->with('invalid_profile_picture_data');

    test('unathenticated user cannot upload a profile picture', function () {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->postJson('/api/users/profile-picture', [
            'image' => $file,
        ]);

        $response->assertStatus(401);
    });
});

describe('Updating user profile', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    test('authenticated user can update their profile', function (array $payload) {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'name' => $payload['name'],
                'email' => $payload['email'],
                'image_url' => 'http://localhost/images/default_pfp.png',
                'bio' => $payload['bio'] ?? null,
            ]);
    })->with('valid_profile_data');

    test('authenticated user cannot update their profile with invalid data', function (array $payload, string $field, int $status) {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$this->user->id}", $payload);

        $response->assertStatus($status)
            ->assertJsonValidationErrors($field);
    })->with('invalid_profile_data');

    test('authenticated users cannot update other users profiles', function (array $payload) {
        $otherUser = User::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/users/{$otherUser->id}", $payload);

        $response->assertStatus(403);
    })->with('valid_profile_data');

    test('unauthenticated users cannot update their profiles', function (array $payload) {
        $response = $this->putJson("/api/users/{$this->user->id}", $payload);

        $response->assertStatus(401);
    })->with('valid_profile_data');

    test('it rejects an email that is already taken by another user', function () {
        $targetUser = User::factory()->create(['email' => 'target@example.com']);
        $otherUser = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($targetUser, 'sanctum')
            ->putJson("/api/users/{$targetUser->id}", [
                'name'  => 'Updated Name',
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('a user can keep their own email when updating their profile', function () {
        $user = User::factory()->create(['email' => 'my-email@example.com']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'name'  => 'Updated Name',
                'email' => 'my-email@example.com',
                'bio'   => 'New bio'
            ]);

        $response->assertStatus(200);
    });
});
