<?php

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->token = $this->user->createToken('auth-token')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
    $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
});

it('triggers analysis and returns 202', function () {
    Storage::fake('docformat');

    $document = Document::factory()->create([
        'project_id' => $this->project->id,
    ])->load('project');

    DocumentVersion::factory()->create([
        'document_id' => $document->id,
    ]);

    Queue::fake();

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/documents/{$document->id}/analyze");

    $response->assertStatus(202)
        ->assertJson([
            'message' => 'Analysis has been queued.',
            'status' => 'analyzing',
        ]);

    Queue::assertPushed(AnalyzeDocumentJob::class);
});

it('returns latest analysis for a document', function () {
    $document = Document::factory()->create([
        'project_id' => $this->project->id,
    ]);

    DocumentAnalysis::factory()->completed()->create([
        'document_id' => $document->id,
    ]);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/documents/{$document->id}/analysis");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'document_id',
                'status',
                'elements',
                'created_at',
            ],
        ]);
});

it('returns 403 for analysis of non-owned document', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherProject = Project::factory()->create(['owner_id' => $otherUser->id]);

    $document = Document::factory()->create([
        'project_id' => $otherProject->id,
    ]);

    DocumentAnalysis::factory()->completed()->create([
        'document_id' => $document->id,
    ]);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/documents/{$document->id}/analysis");

    $response->assertForbidden();
});

it('returns 404 for analysis of unanalyzed document', function () {
    $document = Document::factory()->create([
        'project_id' => $this->project->id,
    ]);

    $response = $this->withHeaders($this->headers)
        ->getJson("/api/v1/documents/{$document->id}/analysis");

    $response->assertStatus(404);
});

it('returns 403 when triggering analysis for non-owned document', function () {
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $otherProject = Project::factory()->create(['owner_id' => $otherUser->id]);

    $document = Document::factory()->create([
        'project_id' => $otherProject->id,
    ]);

    $response = $this->withHeaders($this->headers)
        ->postJson("/api/v1/documents/{$document->id}/analyze");

    $response->assertForbidden();
});
