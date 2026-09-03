<?php

use App\Models\Document;
use App\Models\DocumentIssue;
use App\Models\Project;
use App\Models\QualityReport;
use App\Models\User;
use App\Services\Quality\QualityEngine;
use App\Services\QualityReportService;

it('returns a perfect score with no issues', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $score = app(QualityEngine::class)->score($document);

    expect($score['overall_score'])->toBe(100.0);
    expect($score['counts'])->toMatchArray(['errors' => 0, 'warnings' => 0, 'info' => 0]);
});

it('penalizes error issues but stays deterministic', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    DocumentIssue::factory()->error()->create([
        'document_id' => $document->id,
        'source' => 'citation',
        'probabilistic' => false,
    ]);

    $engine = app(QualityEngine::class);
    $first = $engine->score($document);
    $second = $engine->score($document);

    expect($first['overall_score'])->toBeLessThan(100);
    expect($first['overall_score'])->toBe($second['overall_score']);
    expect($first['counts']['errors'])->toBe(1);
});

it('excludes probabilistic issues from the deterministic category score', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    DocumentIssue::factory()->probabilistic()->error()->create([
        'document_id' => $document->id,
        'source' => 'duplicate',
    ]);

    $score = app(QualityEngine::class)->score($document);

    expect($score['counts']['probabilistic'])->toBe(1);
    expect($score['category_scores']['citations']['score'])->toBe(100.0);
});

it('exposes quality endpoint for owner', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/quality")
        ->assertOk()
        ->assertJsonStructure(['overall_score', 'category_scores', 'counts']);
});

it('rejects quality endpoint for non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/quality")
        ->assertForbidden();
});

it('generates and retrieves a quality report', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $report = app(QualityReportService::class)->generate($document);

    expect($report)->toBeInstanceOf(QualityReport::class);
    expect($report->generated_at)->not->toBeNull();
    expect($report->summary['document_id'])->toBe($document->id);
    expect($report->quality_score['overall_score'])->toBe(100);
});

it('returns the latest report via API', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    QualityReport::factory()->create(['document_id' => $document->id, 'summary' => ['version' => 1]]);
    QualityReport::factory()->create(['document_id' => $document->id, 'summary' => ['version' => 2]]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/report")
        ->assertOk()
        ->assertJsonPath('summary.version', 2);
});

it('rejects report access for non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $otherUser->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/report")
        ->assertForbidden();
});
