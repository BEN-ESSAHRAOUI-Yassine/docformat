<?php

use App\Models\Abbreviation;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;

it('returns abbreviations for owned document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    Abbreviation::factory()->count(3)->create([
        'document_id' => $document->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/abbreviations");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'abbreviations',
        ]);
});

it('returns abbreviation issues for owned document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    Abbreviation::factory()->inconsistent()->create([
        'document_id' => $document->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/abbreviation-issues");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'issues',
            'summary',
        ]);
});

it('rejects abbreviation access for unowned document', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/abbreviations");

    $response->assertForbidden();
});
