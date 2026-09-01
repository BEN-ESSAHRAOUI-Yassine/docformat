<?php

use App\Services\HeadingDetectionService;
use PhpOffice\PhpWord\PhpWord;

uses();

beforeEach(function () {
    $this->service = new HeadingDetectionService;
});

it('detects Title elements as headings with confidence 1.0', function () {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addTitle('Introduction', 1);
    $section->addTitle('Background', 2);
    $section->addParagraph('Some text');

    $headings = $this->service->detectHeadings($phpWord);

    expect($headings)->toHaveCount(2);
    expect($headings[0]['text'])->toBe('Introduction');
    expect($headings[0]['level'])->toBe(1);
    expect($headings[0]['confidence'])->toBe(1.0);
    expect($headings[0]['signals'])->toContain('style');

    expect($headings[1]['text'])->toBe('Background');
    expect($headings[1]['level'])->toBe(2);
});

it('detects numbered headings and extracts correct level', function () {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('1 First Section');

    $headings = $this->service->detectHeadings($phpWord);

    expect($headings)->toHaveCount(1);
    expect($headings[0]['level'])->toBe(1);
    expect($headings[0]['signals'])->toContain('numbering');
});

it('detects capitalized text as heading signal', function () {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('INTRODUCTION');
    $section->addText('regular text');

    $headings = $this->service->detectHeadings($phpWord);

    expect($headings)->toHaveCount(1);
    expect($headings[0]['text'])->toBe('INTRODUCTION');
    expect($headings[0]['signals'])->toContain('capitalization');
});

it('detects chapter/section text patterns', function () {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('Chapter 1 Getting Started');
    $section->addText('Section 2 Configuration');

    $headings = $this->service->detectHeadings($phpWord);

    expect($headings)->toHaveCount(2);
    expect($headings[0]['text'])->toBe('Chapter 1 Getting Started');
    expect($headings[0]['signals'])->toContain('text_pattern');
    expect($headings[1]['text'])->toBe('Section 2 Configuration');
});

it('skips very short text as not a heading', function () {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('A');
    $section->addText('This is a regular paragraph with enough text to be a paragraph.');

    $headings = $this->service->detectHeadings($phpWord);

    expect($headings)->toHaveCount(0);
});

it('calculates confidence based on signal weights', function () {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('INTRODUCTION');

    $headings = $this->service->detectHeadings($phpWord);

    expect($headings)->toHaveCount(1);

    $confidence = $headings[0]['confidence'];
    expect($confidence)->toBeGreaterThan(0.0);
    expect($confidence)->toBeLessThanOrEqual(1.0);
    expect($headings[0]['signals'])->toContain('capitalization');
});

it('validates hierarchy and warns about skipped levels', function () {
    $headings = [
        ['index' => 0, 'text' => 'H1', 'level' => 1, 'confidence' => 1.0, 'signals' => []],
        ['index' => 1, 'text' => 'H3', 'level' => 3, 'confidence' => 1.0, 'signals' => []],
    ];

    $warnings = $this->service->validateHierarchy($headings);

    expect($warnings)->toHaveCount(1);
    expect($warnings[0])->toContain('Heading Level 3');
    expect($warnings[0])->toContain('Heading Level 2');
});

it('validates hierarchy with no skipped levels', function () {
    $headings = [
        ['index' => 0, 'text' => 'H1', 'level' => 1, 'confidence' => 1.0, 'signals' => []],
        ['index' => 1, 'text' => 'H2', 'level' => 2, 'confidence' => 1.0, 'signals' => []],
        ['index' => 2, 'text' => 'H3', 'level' => 3, 'confidence' => 1.0, 'signals' => []],
    ];

    $warnings = $this->service->validateHierarchy($headings);

    expect($warnings)->toHaveCount(0);
});

it('validates hierarchy with multiple skipped levels', function () {
    $headings = [
        ['index' => 0, 'text' => 'H1', 'level' => 1, 'confidence' => 1.0, 'signals' => []],
        ['index' => 1, 'text' => 'H4', 'level' => 4, 'confidence' => 1.0, 'signals' => []],
    ];

    $warnings = $this->service->validateHierarchy($headings);

    expect($warnings)->toHaveCount(2);
    expect($warnings[0])->toContain('Heading Level 4');
    expect($warnings[0])->toContain('Heading Level 2');
    expect($warnings[1])->toContain('Heading Level 4');
    expect($warnings[1])->toContain('Heading Level 3');
});
