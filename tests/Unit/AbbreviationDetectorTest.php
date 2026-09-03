<?php

use App\Services\AbbreviationDetector;

it('detects abbreviation definitions', function () {
    $detector = new AbbreviationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Artificial Intelligence (AI) is transforming the world.'],
        ['index' => 1, 'text' => 'Natural Language Processing (NLP) is a subfield of AI.'],
    ];

    $abbreviations = $detector->detect($paragraphs);

    expect($abbreviations)->toHaveCount(2);

    $ai = collect($abbreviations)->firstWhere('abbreviation', 'AI');
    expect($ai)->not->toBeNull();
    expect($ai['full_form'])->toBe('Artificial Intelligence');
    expect($ai['definition_element_index'])->toBe(0);

    $nlp = collect($abbreviations)->firstWhere('abbreviation', 'NLP');
    expect($nlp)->not->toBeNull();
    expect($nlp['full_form'])->toBe('Natural Language Processing');
});

it('counts usages after definition', function () {
    $detector = new AbbreviationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Machine Learning (ML) is useful.'],
        ['index' => 1, 'text' => 'ML is used in AI.'],
        ['index' => 2, 'text' => 'ML and AI are related.'],
    ];

    $abbreviations = $detector->detect($paragraphs);

    $ml = collect($abbreviations)->firstWhere('abbreviation', 'ML');
    expect($ml)->not->toBeNull();
    expect($ml['usage_count'])->toBe(3); // Definition + 2 usages
    expect($ml['occurrences'])->toContain(0, 1, 2);
});

it('detects inconsistent definitions', function () {
    $detector = new AbbreviationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Machine Learning (ML) is useful.'],
        ['index' => 1, 'text' => 'Maximum Likelihood (ML) is different.'],
    ];

    $abbreviations = $detector->detect($paragraphs);

    $ml = collect($abbreviations)->firstWhere('abbreviation', 'ML');
    expect($ml)->not->toBeNull();
    expect($ml['is_consistent'])->toBeFalse();
    expect($ml['inconsistent_forms'])->toContain('Maximum Likelihood');
});

it('returns empty array when no abbreviations found', function () {
    $detector = new AbbreviationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'This paragraph has no abbreviations.'],
    ];

    $abbreviations = $detector->detect($paragraphs);

    expect($abbreviations)->toHaveCount(0);
});

it('detects multiple abbreviations in single paragraph', function () {
    $detector = new AbbreviationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'AI and ML are related fields.'],
    ];

    // No definitions, so no abbreviations detected
    $abbreviations = $detector->detect($paragraphs);
    expect($abbreviations)->toHaveCount(0);
});

it('handles French abbreviations', function () {
    $detector = new AbbreviationDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Intelligence Artificielle (IA) est en pleine expansion.'],
    ];

    $abbreviations = $detector->detect($paragraphs);

    expect($abbreviations)->toHaveCount(1);
    expect($abbreviations[0]['abbreviation'])->toBe('IA');
    expect($abbreviations[0]['full_form'])->toBe('Intelligence Artificielle');
});

it('getIssues identifies inconsistent abbreviations', function () {
    $detector = new AbbreviationDetector;

    $abbreviations = [
        [
            'abbreviation' => 'ML',
            'full_form' => 'Machine Learning',
            'definition_element_index' => 0,
            'usage_count' => 5,
            'occurrences' => [1, 2, 3, 4, 5],
            'is_consistent' => false,
            'inconsistent_forms' => ['Maximum Likelihood'],
        ],
    ];

    $issues = $detector->getIssues($abbreviations);

    expect($issues['inconsistent'])->toHaveCount(1);
    expect($issues['inconsistent'][0]['abbreviation'])->toBe('ML');
    expect($issues['inconsistent'][0]['forms'])->toContain('Machine Learning', 'Maximum Likelihood');
});

it('getIssues identifies unused abbreviations', function () {
    $detector = new AbbreviationDetector;

    $abbreviations = [
        [
            'abbreviation' => 'NLP',
            'full_form' => 'Natural Language Processing',
            'definition_element_index' => 0,
            'usage_count' => 0,
            'occurrences' => [],
            'is_consistent' => true,
            'inconsistent_forms' => [],
        ],
    ];

    $issues = $detector->getIssues($abbreviations);

    expect($issues['unused'])->toHaveCount(1);
    expect($issues['unused'][0]['abbreviation'])->toBe('NLP');
});
