<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

it('sends a password reset email', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertOk();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal if email does not exist', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertOk();
    Notification::assertNothingSent();
});

it('resets password with valid token', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    $rawToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/reset-password', [
        'token' => $rawToken,
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertOk();
    $user->refresh();
    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();
});

it('rejects expired reset token', function () {
    $user = User::factory()->create();

    $rawToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now()->subMinutes(120),
    ]);

    $response = $this->postJson('/api/v1/reset-password', [
        'token' => $rawToken,
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});
