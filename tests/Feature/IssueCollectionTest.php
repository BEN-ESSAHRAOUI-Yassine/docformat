<?php

use App\Enums\IssueDecision;
use App\Enums\IssueSource;
use App\Models\BibliographyEntry;
use App\Models\Citation;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentIssue;
use App\Models\Project;
use App\Models\StyleViolation;
use App\Models\User;
use App\Services\IssueCollector;

it('collects style issues into document issues', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

    StyleViolation::factory()->create([
        'document_analysis_id' => $analysis->id,
        'category' => 'font',
        'severity' => 'warning',
    ]);

    $issues = app(IssueCollector::class)->collect($document, $analysis);

    expect($issues)->not->toBeEmpty();
    expect($issues->first()->source)->toBe(IssueSource::Style);
    expect($issues->first()->decision)->toBe(IssueDecision::Pending);
});

it('collects citation and bibliography issues', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

    Citation::factory()->create([
        'document_id' => $document->id,
        'document_analysis_id' => $analysis->id,
        'bibliography_entry_id' => null,
        'author' => 'Unknown',
    ]);

    BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'document_analysis_id' => $analysis->id,
        'title' => 'Uncited Paper',
    ]);

    $issues = app(IssueCollector::class)->collect($document, $analysis);

    $citationIssues = $issues->filter(fn ($i) => $i->source === IssueSource::Citation);
    expect($citationIssues)->not->toBeEmpty();
});

it('collects duplicate issues with probabilistic flag', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, J.'],
        'title' => 'Deep Learning',
        'year' => '2020',
    ]);
    BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, J.'],
        'title' => 'Deep Learning',
        'year' => '2020',
    ]);

    $issues = app(IssueCollector::class)->collect($document);

    $dup = $issues->filter(fn ($i) => $i->source === IssueSource::Duplicate);
    expect($dup)->not->toBeEmpty();
    expect($dup->first()->probabilistic)->toBeTrue();
});

it('is idempotent across repeated collection', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $collector = app(IssueCollector::class);
    $collector->collect($document);
    $count1 = DocumentIssue::forDocument($document->id)->count();
    $collector->collect($document);
    $count2 = DocumentIssue::forDocument($document->id)->count();

    expect($count2)->toBe($count1);
});

it('lists issues filtered and paginated for owner', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);
    $token = $user->createToken('test-token')->plainTextToken;

    DocumentIssue::factory()->count(3)->create(['document_id' => $document->id, 'decision' => IssueDecision::Pending->value]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/documents/'.$document->id.'/issues');

    $response->assertOk()
        ->assertJsonStructure(['document_id', 'issues']);
    expect($response->json('issues'))->toBeArray();
});
