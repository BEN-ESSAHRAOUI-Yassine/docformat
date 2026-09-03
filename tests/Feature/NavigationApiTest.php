<?php

use App\Models\BibliographyEntry;
use App\Models\Citation;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;

it('returns citations for owned document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    Citation::factory()->count(3)->create([
        'document_id' => $document->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/citations");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'citations',
        ]);
});

it('returns bibliography entry linked to citation', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
    ]);

    $citation = Citation::factory()->create([
        'document_id' => $document->id,
        'bibliography_entry_id' => $entry->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/citations/{$citation->id}/bibliography-entry");

    $response->assertOk()
        ->assertJsonStructure([
            'citation',
            'bibliography_entry',
        ]);
});

it('returns 404 for citation without bibliography entry', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $citation = Citation::factory()->create([
        'document_id' => $document->id,
        'bibliography_entry_id' => null,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/citations/{$citation->id}/bibliography-entry");

    $response->assertNotFound();
});

it('returns bibliography for owned document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    BibliographyEntry::factory()->count(3)->create([
        'document_id' => $document->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/bibliography");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'bibliography',
        ]);
});

it('returns citations linked to bibliography entry', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
    ]);

    Citation::factory()->count(2)->create([
        'document_id' => $document->id,
        'bibliography_entry_id' => $entry->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/bibliography/{$entry->id}/citations");

    $response->assertOk()
        ->assertJsonStructure([
            'bibliography_entry',
            'citations',
        ]);
});

it('rejects citation access for unowned document', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/citations");

    $response->assertForbidden();
});
