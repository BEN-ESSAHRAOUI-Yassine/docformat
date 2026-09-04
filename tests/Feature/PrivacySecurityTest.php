<?php

use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\DocumentIssue;
use App\Models\Project;
use App\Models\User;
use App\Services\DataRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('encrypts sensitive issue content at rest', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $issue = DocumentIssue::factory()->create([
        'document_id' => $document->id,
        'description' => 'Sensitive issue text',
        'recommendation' => 'Sensitive recommendation',
    ]);

    $raw = DB::table('document_issues')->where('id', $issue->id)->value('description');

    expect($raw)->not->toBe('Sensitive issue text');
    expect($issue->fresh()->description)->toBe('Sensitive issue text');
    expect($issue->fresh()->recommendation)->toBe('Sensitive recommendation');
});

it('purges expired documents and their files', function () {
    Storage::fake('docformat');
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $expired = Document::factory()->create(['project_id' => $project->id, 'created_at' => now()->subYears(2)]);
    $active = Document::factory()->create(['project_id' => $project->id, 'created_at' => now()]);

    $version = $expired->versions()->create([
        'version_number' => 1,
        'file_path' => 'originals/old.docx',
        'file_size' => 10,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'uploaded_by' => $user->id,
    ]);
    Storage::disk('docformat')->put('originals/old.docx', 'x');

    $removed = app(DataRetentionService::class)->purge();

    expect($removed)->toBe(1);
    expect($expired->fresh())->toBeNull();
    expect($active->fresh())->not->toBeNull();
    expect(Storage::disk('docformat')->exists('originals/old.docx'))->toBeFalse();
});

it('logs a security event on document deletion', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson("/api/v1/projects/{$project->id}/documents/{$document->id}")
        ->assertOk();

    expect(DocumentAction::forDocument($document->id)->ofType('security_event')->count())->toBe(1);
});

it('exports the authenticated user data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/user/data-export')
        ->assertOk()
        ->assertJsonStructure(['user', 'projects']);
});

it('deletes the authenticated user data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/v1/user/data')
        ->assertOk();

    expect($document->fresh())->toBeNull();
    expect($project->fresh())->toBeNull();
});
