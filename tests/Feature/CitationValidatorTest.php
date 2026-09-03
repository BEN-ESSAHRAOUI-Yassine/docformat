<?php

use App\Models\BibliographyEntry;
use App\Models\Citation;
use App\Models\Document;
use App\Services\CitationValidator;

it('detects orphaned citations', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'year' => '2020',
    ]);

    Citation::factory()->create([
        'document_id' => $document->id,
        'author' => 'Unknown',
        'year' => '2020',
        'bibliography_entry_id' => null,
    ]);

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    expect($result['warnings'])->not->toBeEmpty();
    expect($result['warnings'][0]['type'])->toBe('orphaned');
});

it('detects uncited entries', function () {
    $document = Document::factory()->create();

    Citation::factory()->create([
        'document_id' => $document->id,
        'bibliography_entry_id' => null,
    ]);

    BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'title' => 'Uncited Paper',
    ]);

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    $uncited = collect($result['warnings'])->where('type', 'uncited');
    expect($uncited)->not->toBeEmpty();
    expect($uncited->first()['message'])->toContain('Uncited Paper');
});

it('auto-links citation to matching entry', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'year' => '2020',
    ]);

    $citation = Citation::factory()->create([
        'document_id' => $document->id,
        'author' => 'Smith',
        'year' => '2020',
        'bibliography_entry_id' => null,
    ]);

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    $autoLinked = collect($result['info'])->where('type', 'auto_linked');
    expect($autoLinked)->not->toBeEmpty();

    $citation->refresh();
    expect($citation->bibliography_entry_id)->toBe($entry->id);
});

it('detects author mismatch', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Jones'],
        'year' => '2020',
    ]);

    Citation::factory()->create([
        'document_id' => $document->id,
        'author' => 'Smith',
        'year' => '2020',
        'bibliography_entry_id' => $entry->id,
    ]);

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    $mismatches = collect($result['warnings'])->where('type', 'mismatch')->where('field', 'author');
    expect($mismatches)->not->toBeEmpty();
});

it('detects year mismatch', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'year' => '2020',
    ]);

    Citation::factory()->create([
        'document_id' => $document->id,
        'author' => 'Smith',
        'year' => '2021',
        'bibliography_entry_id' => $entry->id,
    ]);

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    $mismatches = collect($result['warnings'])->where('type', 'mismatch')->where('field', 'year');
    expect($mismatches)->not->toBeEmpty();
});

it('returns empty issues for valid document', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'year' => '2020',
    ]);

    Citation::factory()->create([
        'document_id' => $document->id,
        'author' => 'Smith',
        'year' => '2020',
        'bibliography_entry_id' => $entry->id,
    ]);

    $validator = new CitationValidator;
    $result = $validator->validate($document);

    expect($result['warnings'])->toBeEmpty();
});

it('getIssues returns summary', function () {
    $document = Document::factory()->create();

    $validator = new CitationValidator;
    $result = $validator->getIssues($document);

    expect($result)->toHaveKeys(['document_id', 'summary', 'issues']);
    expect($result['summary'])->toHaveKeys([
        'total_citations',
        'total_entries',
        'orphaned_citations',
        'uncited_entries',
        'mismatches',
        'ambiguous',
    ]);
});
