<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('it can fetch the list of users', function () {
    User::factory(count: 3)->create();
    $response = $this->getJson('/api/users');

    $response->assertStatus(200)
             ->assertJsonCount(3)
             ->assertJsonStructure([
                 '*' => ['id', 'name', 'email', 'image_url'],
             ]);
});

test('it can fetch a single user', function(){
    $user = User::factory()->create();
    $response = $this->getJson("/api/users/{$user->id}");

    $response->assertStatus(200)
             ->assertJson([
                 'id' => $user->id,
                 'name' => $user->name,
                 'email' => $user->email,
                 'image_url' => 'http://localhost/images/default_pfp.png',
             ]);
});

test('it returns 404 for non-existing user', function(){
    $response = $this->getJson('/api/users/999');

    $response->assertStatus(404);
});

test('authenticated user can upload a profile picture', function(){
    Storage::fake('public');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('profile.jpg');

    $response = $this->actingAs($user, 'sanctum')
                     ->postJson('/api/users/profile-picture', [
                             'image' => $file,
                         ]);

    $response->assertOk()
             ->assertJson([
                 'message' => 'Profile picture updated successfully',
             ]);
});

test('unathenticated user cannot upload a profile picture', function(){
    Storage::fake('public');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('profile.jpg');

    $response = $this->postJson('/api/users/profile-picture', [
                             'image' => $file,
                         ]);

    $response->assertStatus(401);
});

test('authenticated user cannot upload a non-image file as profile picture', function(){
    Storage::fake('public');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('document.pdf');

    $response = $this->actingAs($user, 'sanctum')
                     ->postJson('/api/users/profile-picture', [
                             'image' => $file,
                         ]);

    $response->assertStatus(422);
});
