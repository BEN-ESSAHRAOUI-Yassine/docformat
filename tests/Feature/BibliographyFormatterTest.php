<?php

use App\Models\BibliographyEntry;
use App\Models\Document;
use App\Services\BibliographyFormatter;

it('formats in APA style', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John A.'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
        'journal' => 'Journal of AI Research',
        'volume' => '15',
        'issue' => '3',
        'pages' => '123-145',
        'doi' => '10.1000/xyz123',
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'apa');

    expect($result)->toContain('Smith, J');
    expect($result)->toContain('(2020)');
    expect($result)->toContain('Deep Learning for NLP');
    expect($result)->toContain('Journal of AI Research');
    expect($result)->toContain('15');
    expect($result)->toContain('3');
    expect($result)->toContain('123-145');
    expect($result)->toContain('https://doi.org/10.1000/xyz123');
});

it('formats in IEEE style', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John A.', 'Jones, Bob'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
        'journal' => 'Journal of AI Research',
        'volume' => '15',
        'issue' => '3',
        'pages' => '123-145',
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'ieee');

    expect($result)->toContain('J.');
    expect($result)->toContain('B. Jones');
    expect($result)->toContain('"Deep Learning for NLP,"');
    expect($result)->toContain('vol. 15');
    expect($result)->toContain('no. 3');
    expect($result)->toContain('pp. 123-145');
    expect($result)->toContain('2020');
});

it('formats in Vancouver style', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John A.'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
        'journal' => 'Journal of AI Research',
        'volume' => '15',
        'issue' => '3',
        'pages' => '123-145',
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'vancouver');

    expect($result)->toContain('Smith JA');
    expect($result)->toContain('Deep Learning for NLP');
    expect($result)->toContain('Journal of AI Research');
    expect($result)->toContain('2020');
    expect($result)->toContain('15');
    expect($result)->toContain('123-145');
});

it('formats in MLA style', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John A.'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
        'journal' => 'Journal of AI Research',
        'volume' => '15',
        'issue' => '3',
        'pages' => '123-145',
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'mla');

    expect($result)->toContain('Smith, John A.');
    expect($result)->toContain('"Deep Learning for NLP."');
    expect($result)->toContain('Journal of AI Research');
    expect($result)->toContain('vol. 15');
    expect($result)->toContain('no. 3');
    expect($result)->toContain('123-145');
});

it('formats in Chicago style', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John A.'],
        'title' => 'Deep Learning for NLP',
        'year' => '2020',
        'journal' => 'Journal of AI Research',
        'volume' => '15',
        'issue' => '3',
        'pages' => '123-145',
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'chicago');

    expect($result)->toContain('Smith, John A.');
    expect($result)->toContain('"Deep Learning for NLP."');
    expect($result)->toContain('Journal of AI Research');
    expect($result)->toContain('15');
    expect($result)->toContain('no. 3');
    expect($result)->toContain('(2020)');
});

it('handles multiple authors', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith, John', 'Jones, Bob', 'Brown, Alice'],
        'title' => 'Multi-Author Paper',
        'year' => '2021',
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'apa');

    expect($result)->toContain('Smith');
    expect($result)->toContain('Jones');
    expect($result)->toContain('Brown');
});

it('returns available styles', function () {
    $formatter = new BibliographyFormatter;
    $styles = $formatter->getAvailableStyles();

    expect($styles)->toContain('APA');
    expect($styles)->toContain('IEEE');
    expect($styles)->toContain('Vancouver');
    expect($styles)->toContain('MLA');
    expect($styles)->toContain('Chicago');
});

it('handles missing fields gracefully', function () {
    $document = Document::factory()->create();
    $entry = BibliographyEntry::factory()->create([
        'document_id' => $document->id,
        'authors' => ['Smith'],
        'title' => 'Minimal Entry',
        'year' => null,
        'journal' => null,
        'volume' => null,
        'issue' => null,
        'pages' => null,
        'doi' => null,
    ]);

    $formatter = new BibliographyFormatter;
    $result = $formatter->format($entry, 'apa');

    expect($result)->toContain('Smith');
    expect($result)->toContain('Minimal Entry');
    expect($result)->toContain('(n.d.)');
});
