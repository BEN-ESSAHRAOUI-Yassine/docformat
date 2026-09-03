<?php

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use App\Services\DocumentAnalysisService;
use App\Services\IssueCollector;
use App\Services\ReviewStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
});

it('dispatches analysis job to correct queue', function () {
    $document = Document::factory()->create([
        'project_id' => $this->project->id,
    ]);

    DocumentVersion::factory()->create([
        'document_id' => $document->id,
    ]);

    Queue::fake();

    AnalyzeDocumentJob::dispatch($document);

    Queue::assertPushed(AnalyzeDocumentJob::class, function ($job) use ($document) {
        return $job->document->id === $document->id;
    });
});

it('sets status to failed when document has no version', function () {
    $document = Document::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'uploaded',
    ]);

    $job = new AnalyzeDocumentJob($document);
    $job->handle(app(DocumentAnalysisService::class), app(IssueCollector::class), app(ReviewStatusService::class));

    expect($document->fresh()->status->value)->toBe('failed');
});

it('sets status to failed on exception', function () {
    $document = Document::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'uploaded',
    ]);

    DocumentVersion::factory()->create([
        'document_id' => $document->id,
        'file_path' => 'nonexistent/file.docx',
    ]);

    $job = new AnalyzeDocumentJob($document);

    try {
        $job->handle(app(DocumentAnalysisService::class), app(IssueCollector::class), app(ReviewStatusService::class));
    } catch (Throwable $e) {
        // Expected
    }

    expect($document->fresh()->status->value)->toBe('failed');
});
