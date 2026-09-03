<?php

use App\Models\BibliographyEntry;
use App\Models\Document;
use App\Services\DuplicateDetector;

it('detects exact duplicates by author+title+year', function () {
    $document = Document::factory()->create();

    $entry1 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, J.'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
    ]);

    $entry2 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, J.'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
    ]);

    $detector = new DuplicateDetector;
    $groups = $detector->detect(collect([$entry1, $entry2]));

    expect($groups)->toHaveCount(1);
    expect($groups[0]['type'])->toBe('exact');
    expect($groups[0]['entries'])->toContain($entry1->id, $entry2->id);
});

it('detects DOI duplicates', function () {
    $document = Document::factory()->create();

    $entry1 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'doi' => '10.1000/xyz123',
        'authors' => ['Jones'],
        'title' => 'Different Title',
        'year' => '2019',
    ]);

    $entry2 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'doi' => '10.1000/xyz123',
        'authors' => ['Brown'],
        'title' => 'Another Title',
        'year' => '2021',
    ]);

    $detector = new DuplicateDetector;
    $groups = $detector->detect(collect([$entry1, $entry2]));

    expect($groups)->toHaveCount(1);
    expect($groups[0]['type'])->toBe('doi');
    expect($groups[0]['confidence'])->toBe(0.99);
});

it('detects fuzzy title duplicates', function () {
    $document = Document::factory()->create();

    $entry1 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, J.'],
        'title' => 'Deep Learning for Natural Language Processing',
        'year' => '2020',
    ]);

    $entry2 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, J.'],
        'title' => 'Deep Learning for Natural Language Processing Applications',
        'year' => '2020',
    ]);

    $detector = new DuplicateDetector;
    $groups = $detector->detect(collect([$entry1, $entry2]));

    expect($groups)->toHaveCount(1);
    expect($groups[0]['confidence'])->toBeGreaterThanOrEqual(0.7);
});

it('does not detect different entries as duplicates', function () {
    $document = Document::factory()->create();

    $entry1 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'title' => 'First Paper',
        'year' => '2020',
    ]);

    $entry2 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Jones'],
        'title' => 'Completely Different Paper',
        'year' => '2021',
    ]);

    $detector = new DuplicateDetector;
    $groups = $detector->detect(collect([$entry1, $entry2]));

    expect($groups)->toHaveCount(0);
});

it('generates merge preview', function () {
    $document = Document::factory()->create();

    $entry1 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'title' => 'Paper A',
        'year' => '2020',
        'journal' => 'Journal of AI',
        'doi' => null,
    ]);

    $entry2 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'title' => 'Paper A',
        'year' => '2020',
        'journal' => 'Journal of ML',
        'doi' => '10.1000/abc',
    ]);

    $detector = new DuplicateDetector;
    $preview = $detector->mergePreview($entry1, $entry2);

    expect($preview)->toHaveKeys(['authors', 'title', 'year', 'journal', 'doi']);
    expect($preview['doi']['keep'])->toBeNull();
    expect($preview['doi']['merge'])->toBe('10.1000/abc');
    expect($preview['doi']['recommended'])->toBe('10.1000/abc');
});

it('merges entries keeping preferred values', function () {
    $document = Document::factory()->create();

    $entry1 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'title' => 'Paper A',
        'year' => '2020',
        'doi' => null,
    ]);

    $entry2 = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'title' => 'Paper A',
        'year' => '2020',
        'doi' => '10.1000/abc',
    ]);

    $detector = new DuplicateDetector;
    $merged = $detector->merge($entry1, $entry2, ['doi' => 'merge']);

    expect($merged->doi)->toBe('10.1000/abc');
    expect(BibliographyEntry::where('id', $entry2->id)->exists())->toBeFalse();
});

it('returns empty groups when no duplicates', function () {
    $document = Document::factory()->create();

    $entries = BibliographyEntry::factory()->count(3)->create([
        'document_id' => $document->id,
    ]);

    $detector = new DuplicateDetector;
    $groups = $detector->detect($entries);

    expect($groups)->toHaveCount(0);
});
