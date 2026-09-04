<?php

use App\Enums\BatchStatus;
use App\Jobs\BatchJob;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use App\Services\BatchProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('docformat');
});

it('creates a batch with items and dispatches processing', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $doc = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    Queue::fake();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/batches', [
            'name' => 'My batch',
            'project_id' => $project->id,
            'document_ids' => [$doc->id],
        ])
        ->assertStatus(201);

    Queue::assertPushed(BatchJob::class);

    $batchId = $response->json('batch.id');
    expect(BatchItem::where('batch_id', $batchId)->count())->toBe(1);
    expect(Batch::find($batchId)->status)->toBe(BatchStatus::Queued);
});

it('rejects a batch containing a non-project document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $otherProject = Project::factory()->create(['owner_id' => $user->id]);
    $otherDoc = Document::factory()->create(['project_id' => $otherProject->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/batches', [
            'name' => 'bad batch',
            'project_id' => $project->id,
            'document_ids' => [$otherDoc->id],
        ])
        ->assertStatus(422);
});

it('lists batches for the owner', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    Batch::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/batches')
        ->assertOk()
        ->assertJsonCount(1, 'batches');
});

it('rejects viewing a batch for a non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $batch = Batch::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/batches/'.$batch->id)
        ->assertForbidden();
});

it('recomputes summary from items', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $batch = Batch::factory()->create(['project_id' => $project->id]);

    BatchItem::factory()->completed()->create([
        'batch_id' => $batch->id,
        'quality_score' => 80,
    ]);
    BatchItem::factory()->failed()->create(['batch_id' => $batch->id]);

    app(BatchProcessingService::class)->recomputeSummary($batch);

    $batch->refresh();
    expect($batch->status)->toBe(BatchStatus::Partial);
    expect($batch->summary['total'])->toBe(2);
    expect($batch->summary['completed'])->toBe(1);
    expect($batch->summary['failed'])->toBe(1);
    expect($batch->summary['average_score'])->toBe(80);
});

it('exports a batch to a ZIP archive', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $batch = Batch::factory()->create(['project_id' => $project->id]);
    BatchItem::factory()->completed()->create([
        'batch_id' => $batch->id,
        'document_id' => $document->id,
    ]);

    $exportPath = 'exports/test-doc.docx';
    Storage::disk('docformat')->put($exportPath, file_get_contents(fixturePath('simple.docx')));

    $version = $document->versions()->create([
        'version_number' => 1,
        'file_path' => $exportPath,
        'file_size' => filesize(fixturePath('simple.docx')),
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'uploaded_by' => $user->id,
        'kind' => 'export',
    ]);
    $document->update(['current_version_id' => $version->id]);

    $token = $user->createToken('t')->plainTextToken;
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->get('/api/v1/batches/'.$batch->id.'/export/download');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('zip');
});
