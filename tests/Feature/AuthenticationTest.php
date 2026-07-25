<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user and returns an API token', function (): void {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('user.email', 'test@example.com')
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure(['token']);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

it('logs a user in and returns an API token', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure(['token']);
});

it('rejects unauthenticated requests for the current user', function (): void {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('rejects a login with an invalid password', function (): void {
    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid credentials');
});

it('revokes the current token when logging out', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out');

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);

    $this->flushHeaders();
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});
