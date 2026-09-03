<?php

use App\Models\Abbreviation;
use App\Models\BibliographyEntry;
use App\Models\Citation;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use App\Services\AbbreviationDetector;
use App\Services\BibliographyDetector;
use App\Services\BibliographyFormatter;
use App\Services\CitationDetector;
use App\Services\CitationValidator;
use App\Services\DuplicateDetector;

it('CitationDetector handles empty paragraphs', function () {
    $detector = new CitationDetector;
    $result = $detector->detect([]);
    expect($result)->toHaveCount(0);
});

it('BibliographyDetector handles empty paragraphs', function () {
    $detector = new BibliographyDetector;
    $result = $detector->detect([]);
    expect($result)->toHaveCount(0);
});

it('AbbreviationDetector handles empty paragraphs', function () {
    $detector = new AbbreviationDetector;
    $result = $detector->detect([]);
    expect($result)->toHaveCount(0);
});

it('DuplicateDetector handles single entry', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create(['document_id' => $document->id]);

    $detector = new DuplicateDetector;
    $groups = $detector->detect(collect([$entry]));

    expect($groups)->toHaveCount(0);
});

it('BibliographyFormatter handles all styles', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John'],
        'title' => 'Test Paper',
        'year' => '2020',
        'journal' => 'Test Journal',
        'volume' => '10',
        'issue' => '1',
        'pages' => '1-10',
    ]);

    $formatter = new BibliographyFormatter;

    $styles = ['apa', 'ieee', 'vancouver', 'mla', 'chicago'];
    foreach ($styles as $style) {
        $result = $formatter->format($entry, $style);
        expect($result)->not->toBeEmpty();
    }
});

it('CitationValidator handles document with no data', function () {
    $document = Document::factory()->create();

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    expect($result['errors'])->toBeEmpty();
    expect($result['warnings'])->toBeEmpty();
    expect($result['info'])->toBeEmpty();
});

it('getIssues returns correct summary', function () {
    $document = Document::factory()->create();

    $validator = new CitationValidator;
    $result = $validator->getIssues($document);

    expect($result['summary']['total_citations'])->toBe(0);
    expect($result['summary']['total_entries'])->toBe(0);
});

it('API citations endpoint returns paginated data', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    Citation::factory()->count(5)->create([
        'document_id' => $document->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/citations");

    $response->assertOk()
        ->assertJsonCount(5, 'citations');
});

it('API bibliography endpoint returns all entries', function () {
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
        ->assertJsonCount(3, 'bibliography');
});

it('API abbreviations endpoint returns all abbreviations', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    Abbreviation::factory()->count(4)->create([
        'document_id' => $document->id,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/abbreviations");

    $response->assertOk()
        ->assertJsonCount(4, 'abbreviations');
});

it('API validate-references endpoint works', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/documents/{$document->id}/validate-references");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'issues' => ['errors', 'warnings', 'info'],
            'summary',
        ]);
});

it('API reference-issues endpoint works', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson("/api/v1/documents/{$document->id}/reference-issues");

    $response->assertOk()
        ->assertJsonStructure([
            'document_id',
            'summary',
            'issues',
        ]);
});

it('API abbreviation-issues endpoint works', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $document = Document::factory()->create(['project_id' => $project->id]);

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

it('unauthenticated requests are rejected', function () {
    $document = Document::factory()->create();

    $this->getJson("/api/v1/documents/{$document->id}/citations")
        ->assertUnauthorized();

    $this->getJson("/api/v1/documents/{$document->id}/bibliography")
        ->assertUnauthorized();

    $this->getJson("/api/v1/documents/{$document->id}/abbreviations")
        ->assertUnauthorized();
});

it('Citation and BibliographyEntry models have correct relationships', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create(['document_id' => $document->id]);
    $citation = Citation::factory()->create([
        'document_id' => $document->id,
        'bibliography_entry_id' => $entry->id,
    ]);

    expect($citation->bibliographyEntry)->toBeInstanceOf(BibliographyEntry::class);
    expect($entry->citations)->toHaveCount(1);
    expect($document->citations)->toHaveCount(1);
    expect($document->bibliographyEntries)->toHaveCount(1);
});

it('Abbreviation model has correct relationships', function () {
    $document = Document::factory()->create();
    $abbreviation = Abbreviation::factory()->create(['document_id' => $document->id]);

    expect($abbreviation->document)->toBeInstanceOf(Document::class);
    expect($document->abbreviations)->toHaveCount(1);
});
