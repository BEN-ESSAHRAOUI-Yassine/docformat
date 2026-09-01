<?php

use App\Enums\AnalysisStatus;
use App\Models\DetectedElement;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use App\Services\DocumentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('docformat');
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
});

it('creates analysis record and detected elements from simple.docx', function () {
    $file = UploadedFile::fake()->createWithContent(
        'test.docx',
        file_get_contents(__DIR__.'/../fixtures/docx/simple.docx')
    );

    $document = Document::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'uploaded',
    ]);

    $version = DocumentVersion::factory()->create([
        'document_id' => $document->id,
        'file_path' => 'originals/'.now()->format('Y/m/d').'/'.$file->hashName(),
    ]);

    Storage::disk('docformat')->put($version->file_path, $file->get());

    $service = app(DocumentAnalysisService::class);
    $analysis = $service->analyze($document, $version);

    expect($analysis)->toBeInstanceOf(DocumentAnalysis::class);
    expect($analysis->status)->toBe(AnalysisStatus::COMPLETED);
    expect($analysis->metadata['element_count'])->toBeGreaterThan(0);

    $this->assertDatabaseHas('document_analyses', [
        'document_id' => $document->id,
        'status' => AnalysisStatus::COMPLETED->value,
    ]);

    $elements = DetectedElement::where('document_analysis_id', $analysis->id)->get();
    expect($elements)->not->toBeEmpty();

    expect($document->fresh()->status->value)->toBe('analysis_completed');
});

it('sets analysis status to failed on error', function () {
    $document = Document::factory()->create([
        'project_id' => $this->project->id,
    ]);

    $version = DocumentVersion::factory()->create([
        'document_id' => $document->id,
        'file_path' => 'nonexistent/file.docx',
    ]);

    $service = app(DocumentAnalysisService::class);

    try {
        $service->analyze($document, $version);
    } catch (Throwable $e) {
        // Expected
    }

    $this->assertDatabaseHas('document_analyses', [
        'document_id' => $document->id,
        'status' => AnalysisStatus::FAILED->value,
    ]);

    expect($document->fresh()->status->value)->toBe('failed');
});

it('assigns manual heading to a detected element', function () {
    $document = Document::factory()->create(['project_id' => $this->project->id]);
    $analysis = DocumentAnalysis::factory()->completed()->create([
        'document_id' => $document->id,
    ]);

    $element = DetectedElement::factory()->paragraph()->create([
        'document_analysis_id' => $analysis->id,
        'document_id' => $document->id,
        'content' => 'Test Heading Text',
    ]);

    $service = app(DocumentAnalysisService::class);
    $updated = $service->assignHeading($element, 3);

    expect($updated->type)->toBe('heading');
    expect($updated->heading_level)->toBe(3);
    expect($updated->metadata['confidence'])->toEqual(1.0);
    expect($updated->metadata['manual'])->toBeTrue();
    expect($updated->metadata['original_data']['type'])->toBe('paragraph');
});

it('rejects invalid heading level for manual assignment', function () {
    $document = Document::factory()->create(['project_id' => $this->project->id]);
    $analysis = DocumentAnalysis::factory()->completed()->create([
        'document_id' => $document->id,
    ]);

    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'document_id' => $document->id,
    ]);

    $service = app(DocumentAnalysisService::class);

    $service->assignHeading($element, 0);
})->throws(InvalidArgumentException::class, 'Heading level must be between 1 and 6.');

it('rejects heading level above 6 for manual assignment', function () {
    $document = Document::factory()->create(['project_id' => $this->project->id]);
    $analysis = DocumentAnalysis::factory()->completed()->create([
        'document_id' => $document->id,
    ]);

    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'document_id' => $document->id,
    ]);

    $service = app(DocumentAnalysisService::class);

    $service->assignHeading($element, 7);
})->throws(InvalidArgumentException::class, 'Heading level must be between 1 and 6.');
