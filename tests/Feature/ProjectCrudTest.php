<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $this->token = $this->user->createToken('auth-token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

it('allows authenticated user to list projects', function () {
    Project::factory()->count(3)->create(['owner_id' => $this->user->id]);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/projects');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('allows authenticated user to create a project', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/projects', [
        'name' => 'My Project',
        'description' => 'A test project',
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'My Project']);

    $this->assertDatabaseHas('projects', ['name' => 'My Project']);
});

it('rejects project creation without name', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/projects', [
        'description' => 'No name',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('name');
});

it('allows owner to view their project', function () {
    $project = Project::factory()->create(['owner_id' => $this->user->id]);

    $response = $this->withHeaders($this->headers)->getJson("/api/v1/projects/{$project->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $project->id]);
});

it('rejects non-owner from viewing project', function () {
    $project = Project::factory()->create();

    $response = $this->withHeaders($this->headers)->getJson("/api/v1/projects/{$project->id}");

    $response->assertForbidden();
});

it('allows owner to update their project', function () {
    $project = Project::factory()->create(['owner_id' => $this->user->id]);

    $response = $this->withHeaders($this->headers)->putJson("/api/v1/projects/{$project->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Name']);
});

it('allows owner to delete their project', function () {
    $project = Project::factory()->create(['owner_id' => $this->user->id]);

    $response = $this->withHeaders($this->headers)->deleteJson("/api/v1/projects/{$project->id}");

    $response->assertOk();
    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

it('rejects unauthenticated access', function () {
    $response = $this->getJson('/api/v1/projects');

    $response->assertUnauthorized();
});
