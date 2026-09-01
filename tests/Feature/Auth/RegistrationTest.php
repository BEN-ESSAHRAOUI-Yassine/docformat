<?php

use App\Models\User;

it('allows a user to register and returns token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    $user = User::where('email', 'test@example.com')->first();
    expect($user->role->value)->toBe('owner');
});

it('rejects registration with duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects registration with weak password', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('password');
});

it('rejects registration without required fields', function () {
    $response = $this->postJson('/api/v1/register', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
});
