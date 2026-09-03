<?php

use App\Services\BibliographyDetector;

it('detects bibliography section and parses entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Conclusion'],
        ['index' => 1, 'text' => 'References'],
        ['index' => 2, 'text' => '1. Smith, J. A. (2020). Deep learning for NLP. Journal of AI Research, vol. 15, no. 3, pp. 123-145.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['entry_type'])->toBe('article');
    expect($entries[0]['authors'])->toContain('Smith');
    expect($entries[0]['authors'])->toContain('J. A');
    expect($entries[0]['year'])->toBe('2020');
    expect($entries[0]['title'])->toContain('Deep learning for NLP');
    expect($entries[0]['volume'])->toBe('15');
    expect($entries[0]['issue'])->toBe('3');
    expect($entries[0]['pages'])->toBe('123-145');
});

it('parses APA format entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'References'],
        ['index' => 1, 'text' => 'Dupont, M. & Leroy, P. (2019). Artificial intelligence in healthcare. The Lancet, 394(10201), 112-120.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['authors'])->toContain('Dupont');
    expect($entries[0]['authors'])->toContain('M.');
    expect($entries[0]['authors'])->toContain('Leroy');
    expect($entries[0]['year'])->toBe('2019');
    expect($entries[0]['title'])->toContain('Artificial intelligence in healthcare');
});

it('parses IEEE format entries with numeric prefix', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'References'],
        ['index' => 1, 'text' => '[3] A. A. Author, "Title of article," Title of Periodical, vol. 10, no. 2, pp. 100-110, 2021.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['authors'])->not->toBeEmpty();
    expect($entries[0]['volume'])->toBe('10');
    expect($entries[0]['issue'])->toBe('2');
    expect($entries[0]['pages'])->toBe('100-110');
    expect($entries[0]['year'])->toBe('2021');
});

it('extracts DOI from entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'References'],
        ['index' => 1, 'text' => '1. Smith, J. (2020). Test paper. doi:10.1000/xyz123'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['doi'])->toBe('10.1000/xyz123');
});

it('extracts URL from entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'References'],
        ['index' => 1, 'text' => '1. Author, A. (2020). Online resource. https://example.com/resource.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['url'])->toBe('https://example.com/resource');
    expect($entries[0]['entry_type'])->toBe('online');
});

it('classifies book entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Bibliography'],
        ['index' => 1, 'text' => '1. Jones, B. (2018). Machine Learning. Publisher Press.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['entry_type'])->toBe('book');
});

it('classifies conference entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'References'],
        ['index' => 1, 'text' => '1. Chen, L. (2022). Neural networks. Proceedings of the AAAI Conference, pp. 50-60.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
    expect($entries[0]['entry_type'])->toBe('conference');
});

it('returns empty array when no bibliography section found', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Introduction'],
        ['index' => 1, 'text' => 'Some body text.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(0);
});

it('parses multiple entries', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'References'],
        ['index' => 1, 'text' => '1. Smith, J. (2020). First paper. Journal, vol. 1, pp. 1-10.'],
        ['index' => 2, 'text' => '2. Jones, A. (2021). Second paper. Conference Proceedings, pp. 20-30.'],
        ['index' => 3, 'text' => '3. Brown, K. (2019). Third paper. Book Publisher.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(3);
    expect($entries[0]['year'])->toBe('2020');
    expect($entries[1]['year'])->toBe('2021');
    expect($entries[2]['year'])->toBe('2019');
});

it('handles French bibliography section names', function () {
    $detector = new BibliographyDetector;

    $paragraphs = [
        ['index' => 0, 'text' => 'Références'],
        ['index' => 1, 'text' => '1. Martin, P. (2020). Intelligence artificielle. Press, vol. 5, pp. 100-120.'],
    ];

    $entries = $detector->detect($paragraphs);

    expect($entries)->toHaveCount(1);
});
