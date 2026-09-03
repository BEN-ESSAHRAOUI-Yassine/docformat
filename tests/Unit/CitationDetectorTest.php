<?php

use App\Services\CitationDetector;

it('detects author-year citations', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'This is supported by previous research (Smith, 2020).'],
        ['index' => 1, 'text' => 'Multiple studies confirm this (Dupont et al., 2021) and (Jones and al., 2019).'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(3);

    expect($citations[0]['type'])->toBe('author_year');
    expect($citations[0]['raw_text'])->toBe('(Smith, 2020)');
    expect($citations[0]['author'])->toBe('Smith');
    expect($citations[0]['year'])->toBe('2020');
    expect($citations[0]['numbers'])->toBeNull();
    expect($citations[0]['element_index'])->toBe(0);
    expect($citations[0]['confidence'])->toBeGreaterThan(0.9);

    expect($citations[1]['type'])->toBe('author_year');
    expect($citations[1]['author'])->toBe('Dupont et al.');
    expect($citations[1]['year'])->toBe('2021');
});

it('detects numeric citations', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'As shown in prior work [1].'],
        ['index' => 1, 'text' => 'Several studies agree [2, 3, 5].'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(2);

    expect($citations[0]['type'])->toBe('numeric');
    expect($citations[0]['raw_text'])->toBe('[1]');
    expect($citations[0]['author'])->toBeNull();
    expect($citations[0]['year'])->toBeNull();
    expect($citations[0]['numbers'])->toBe([1]);

    expect($citations[1]['type'])->toBe('numeric');
    expect($citations[1]['numbers'])->toBe([2, 3, 5]);
});

it('detects bracketed author-year citations', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'According to [Smith 2020], this is correct.'],
        ['index' => 1, 'text' => 'Also confirmed by [Dupont et al. 2021].'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(2);

    expect($citations[0]['type'])->toBe('bracketed');
    expect($citations[0]['raw_text'])->toBe('[Smith 2020]');
    expect($citations[0]['author'])->toBe('Smith');
    expect($citations[0]['year'])->toBe('2020');

    expect($citations[1]['type'])->toBe('bracketed');
    expect($citations[1]['author'])->toBe('Dupont et al.');
});

it('returns empty array when no citations found', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'This paragraph has no citations at all.'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(0);
});

it('detects mixed citation types in single paragraph', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Research shows (Smith, 2020) and also [1] and [Jones 2019].'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(3);
    expect($citations[0]['type'])->toBe('author_year');
    expect($citations[1]['type'])->toBe('numeric');
    expect($citations[2]['type'])->toBe('bracketed');
});

it('handles multiple author-year citations with comma-separated authors', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Studies by (Smith, Jones, 2020) confirm this.'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(1);
    expect($citations[0]['author'])->toBe('Smith, Jones');
    expect($citations[0]['year'])->toBe('2020');
});

it('detects citations across multiple paragraphs with correct element indices', function () {
    $detector = new CitationDetector;

    $paragraphs = [
        ['index' => 5, 'text' => 'First citation (Smith, 2020).'],
        ['index' => 10, 'text' => 'Second citation [1].'],
    ];

    $citations = $detector->detect($paragraphs);

    expect($citations)->toHaveCount(2);
    expect($citations[0]['element_index'])->toBe(5);
    expect($citations[1]['element_index'])->toBe(10);
});
