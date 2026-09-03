<?php

use App\Models\Document;
use App\Models\Project;
use App\Models\User;

it('validates references for owned document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/validate-references");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'issues' => ['errors', 'warnings', 'info'],
            'summary',
        ]);
});

it('returns reference issues for owned document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/reference-issues");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'summary',
            'issues',
        ]);
});

it('rejects validation for unowned document', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/validate-references");

    $response->assertForbidden();
});
