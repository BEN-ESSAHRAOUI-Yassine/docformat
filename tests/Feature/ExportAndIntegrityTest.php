<?php

use App\Enums\DocumentStatus;
use App\Jobs\ExportJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use App\Notifications\DocumentExportCompleted;
use App\Notifications\DocumentExportFailed;
use App\Services\DocxEngine\DocxIntegrityValidator;
use App\Services\DocxExportService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('docformat');
});

function addFixtureVersion(Document $document, string $fixture, int $userId): DocumentVersion
{
    $fixturePath = fixturePath($fixture);
    $target = 'versions/'.now()->format('Y/m/d').'/'.$fixture;
    Storage::disk('docformat')->put($target, file_get_contents($fixturePath));

    return $document->versions()->create([
        'version_number' => 1,
        'file_path' => $target,
        'file_size' => filesize($fixturePath),
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'uploaded_by' => $userId,
    ]);
}

it('validates a well-formed DOCX', function () {
    $file = fixturePath('simple.docx');
    $result = app(DocxIntegrityValidator::class)->validate($file);

    expect($result['valid'])->toBeTrue();
    expect($result['errors'])->toBe([]);
});

it('detects a corrupt file as invalid', function () {
    $tmp = tempPath('corrupt.docx');
    file_put_contents($tmp, 'not a zip');

    $result = app(DocxIntegrityValidator::class)->validate($tmp);
    expect($result['valid'])->toBeFalse();
    expect($result['errors'])->not->toBeEmpty();

    cleanTemp('corrupt.docx');
});

it('exports a document to DOCX and validates it', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $version = addFixtureVersion($document, 'simple.docx', $user->id);
    $document->update(['current_version_id' => $version->id]);

    $result = app(DocxExportService::class)->export($document, $user->id);

    expect($result['integrity']['valid'])->toBeTrue();
    expect(file_exists($result['path']))->toBeTrue();
    expect($document->fresh()->current_version_id)->not->toBe($version->id);
});

it('leaves the original file untouched after export', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $version = addFixtureVersion($document, 'simple.docx', $user->id);
    $document->update(['current_version_id' => $version->id]);

    $originalHash = Storage::disk('docformat')->get($version->file_path);

    app(DocxExportService::class)->export($document, $user->id);

    expect(Storage::disk('docformat')->get($version->file_path))->toBe($originalHash);
});

it('dispatches export asynchronously and returns 202', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    Queue::fake();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/export")
        ->assertStatus(202);

    Queue::assertPushed(ExportJob::class);
});

it('rejects export for non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/export")
        ->assertForbidden();
});

it('export job marks the document completed on success and notifies the owner', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $version = addFixtureVersion($document, 'simple.docx', $user->id);
    $document->update(['current_version_id' => $version->id]);

    Notification::fake();

    $job = new ExportJob($document, $user->id);
    $job->handle(app(DocxExportService::class));

    expect($document->fresh()->status)->toBe(DocumentStatus::COMPLETED);

    Notification::assertSentTo(
        $user,
        DocumentExportCompleted::class
    );
});

it('export job marks the document failed on exception and preserves status', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    Notification::fake();

    $job = new ExportJob($document, $user->id);

    try {
        $job->handle(app(DocxExportService::class));
    } catch (Throwable) {
        // expected: no current version
    }

    expect($document->fresh()->status)->toBe(DocumentStatus::FAILED);

    Notification::assertSentTo(
        $user,
        DocumentExportFailed::class
    );
});
