<?php

use App\Services\DocxEngine\DocxReader;
use Tests\TestCase;

uses(TestCase::class);

it('can load a simple DOCX file', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('simple.docx'));

    expect($reader->getPhpWord())->not->toBeNull();
});

it('extracts headings from simple fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('simple.docx'));
    $headings = $reader->extractHeadings();

    expect($headings)->toHaveCount(3);
    expect($headings[0]['text'])->toBe('Simple Document');
    expect($headings[0]['level'])->toBe(1);
    expect($headings[1]['text'])->toBe('Introduction');
    expect($headings[1]['level'])->toBe(2);
    expect($headings[2]['text'])->toBe('Background');
    expect($headings[2]['level'])->toBe(3);
});

it('extracts paragraphs from simple fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('simple.docx'));
    $paragraphs = $reader->extractParagraphs();

    expect($paragraphs)->toHaveCount(3);
    expect($paragraphs[0])->toContain('simple paragraph');
});

it('extracts no tables from simple fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('simple.docx'));
    $tables = $reader->extractTables();

    expect($tables)->toHaveCount(0);
});

it('counts elements correctly for simple fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('simple.docx'));
    $counts = $reader->countElements();

    expect($counts['headings'])->toBe(3);
    expect($counts['tables'])->toBe(0);
    expect($counts['images'])->toBe(0);
    expect($counts['pageBreaks'])->toBe(0);
    expect($counts['sections'])->toBe(1);
});

it('extracts headings from complex fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('complex.docx'));
    $headings = $reader->extractHeadings();

    expect($headings)->toHaveCount(12);
    expect($headings[0]['text'])->toBe('Complex Document');
    expect($headings[0]['level'])->toBe(1);
});

it('extracts tables from complex fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('complex.docx'));
    $tables = $reader->extractTables();

    expect($tables)->toHaveCount(2);
    expect($tables[0]['rows'])->toBe(3);
    expect($tables[0]['cells'])->toBe(9);
    expect($tables[1]['rows'])->toBe(4);
    expect($tables[1]['cells'])->toBe(8);
});

it('extracts page breaks from complex fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('complex.docx'));
    $pageBreaks = $reader->extractPageBreaks();

    expect($pageBreaks)->toHaveCount(1);
});

it('extracts all elements from complex fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('complex.docx'));
    $all = $reader->extractAll();

    expect($all['headings'])->toHaveCount(12);
    expect($all['tables'])->toHaveCount(2);
    expect($all['pageBreaks'])->toHaveCount(1);
    expect($all['sections'])->toBe(1);
});

it('extracts headings from multilingual fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('multilingual.docx'));
    $headings = $reader->extractHeadings();

    expect($headings)->toHaveCount(5);
});

it('extracts tables from multilingual fixture', function () {
    $reader = new DocxReader;
    $reader->load(fixturePath('multilingual.docx'));
    $tables = $reader->extractTables();

    expect($tables)->toHaveCount(1);
    expect($tables[0]['rows'])->toBe(3);
});

it('throws exception for non-existent file', function () {
    $reader = new DocxReader;
    $reader->load('nonexistent.docx');
})->throws(RuntimeException::class, 'DOCX file not found');

it('returns empty results when no file loaded', function () {
    $reader = new DocxReader;

    expect($reader->extractHeadings())->toBeEmpty();
    expect($reader->extractTables())->toBeEmpty();
    expect($reader->extractImages())->toBeEmpty();
    expect($reader->extractPageBreaks())->toBeEmpty();
    expect($reader->extractParagraphs())->toBeEmpty();
    expect($reader->countElements())->toBe([
        'headings' => 0,
        'tables' => 0,
        'images' => 0,
        'pageBreaks' => 0,
        'paragraphs' => 0,
        'sections' => 0,
    ]);
});
