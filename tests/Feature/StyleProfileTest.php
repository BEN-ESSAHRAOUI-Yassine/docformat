<?php

use App\Models\StyleProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->token = $this->user->createToken('auth-token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

it('lists style profiles for authenticated user', function () {
    StyleProfile::factory()->system()->create(['name' => 'Academic Default']);
    StyleProfile::factory()->create(['user_id' => $this->user->id, 'name' => 'My Profile']);

    $this->withHeaders($this->headers)
        ->getJson('/api/v1/style-profiles')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates a style profile', function () {
    $this->withHeaders($this->headers)
        ->postJson('/api/v1/style-profiles', [
            'name' => 'Test Profile',
            'type' => 'university',
            'rules' => ['body' => ['font_family' => 'Times New Roman']],
        ])
        ->assertCreated()
        ->assertJsonFragment(['name' => 'Test Profile']);
});

it('validates required fields on create', function () {
    $this->withHeaders($this->headers)
        ->postJson('/api/v1/style-profiles', [])
        ->assertUnprocessable();
});

it('shows a style profile', function () {
    $profile = StyleProfile::factory()->create(['user_id' => $this->user->id]);

    $this->withHeaders($this->headers)
        ->getJson("/api/v1/style-profiles/{$profile->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $profile->id]);
});

it('updates a style profile with new version', function () {
    $profile = StyleProfile::factory()->create(['user_id' => $this->user->id, 'version' => 1]);

    $this->withHeaders($this->headers)
        ->putJson("/api/v1/style-profiles/{$profile->id}", [
            'name' => 'Updated Profile',
        ])
        ->assertOk()
        ->assertJsonFragment(['name' => 'Updated Profile', 'version' => 2]);
});

it('deletes a style profile', function () {
    $profile = StyleProfile::factory()->create(['user_id' => $this->user->id]);

    $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/style-profiles/{$profile->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('style_profiles', ['id' => $profile->id]);
});

it('prevents deletion of system profiles', function () {
    $profile = StyleProfile::factory()->system()->create(['name' => 'Academic Default']);

    $this->withHeaders($this->headers)
        ->deleteJson("/api/v1/style-profiles/{$profile->id}")
        ->assertForbidden();
});

it('prevents non-owner from updating profile', function () {
    $other = User::factory()->create();
    $profile = StyleProfile::factory()->create(['user_id' => $other->id]);

    $this->withHeaders($this->headers)
        ->putJson("/api/v1/style-profiles/{$profile->id}", [
            'name' => 'Hacked Profile',
        ])
        ->assertForbidden();
});

it('exports style profile as json', function () {
    $profile = StyleProfile::factory()->create(['user_id' => $this->user->id]);

    $this->withHeaders($this->headers)
        ->getJson("/api/v1/style-profiles/{$profile->id}/export")
        ->assertOk()
        ->assertJsonFragment(['name' => $profile->name]);
});

it('imports style profile from json', function () {
    $jsonData = json_encode([
        'name' => 'Imported Profile',
        'type' => 'thesis',
        'rules' => ['body' => ['font_family' => 'Arial']],
    ]);

    $this->withHeaders($this->headers)
        ->postJson('/api/v1/style-profiles/import', ['profile' => $jsonData])
        ->assertCreated()
        ->assertJsonFragment(['name' => 'Imported Profile']);
});
