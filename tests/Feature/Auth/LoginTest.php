<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('allows a user to login and returns token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('Password123!'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
});

it('rejects login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'WrongPassword!',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('rejects login with non-existent email', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

it('allows authenticated user to logout', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/logout');

    $response->assertOk();
});

it('returns user data for authenticated user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/v1/user');

    $response->assertOk()->assertJson([
        'id' => $user->id,
        'email' => $user->email,
    ]);
});
