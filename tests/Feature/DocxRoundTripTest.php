<?php

use App\Services\DocxEngine\DocxReader;
use App\Services\DocxEngine\DocxWriter;

it('performs round-trip on complex fixture', function () {
    $reader = new DocxReader;
    $reader->load($this->fixturePath('complex.docx'));
    $originalCounts = $reader->countElements();

    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));

    $writer->modifyHeading(0, 'Modified Complex Document');

    $tempPath = $this->tempPath('roundtrip-test.docx');
    $writer->save($tempPath);

    $reader2 = new DocxReader;
    $reader2->load($tempPath);
    $newCounts = $reader2->countElements();

    expect($newCounts['headings'])->toBe($originalCounts['headings']);
    expect($newCounts['tables'])->toBe($originalCounts['tables']);
    expect($newCounts['sections'])->toBe($originalCounts['sections']);

    $headings = $reader2->extractHeadings();
    expect($headings[0]['text'])->toBe('Modified Complex Document');

    $this->cleanTemp('roundtrip-test.docx');
});

it('preserves tables through round-trip', function () {
    $reader = new DocxReader;
    $reader->load($this->fixturePath('complex.docx'));
    $originalTables = $reader->extractTables();

    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));
    $tempPath = $this->tempPath('roundtrip-tables.docx');
    $writer->save($tempPath);

    $reader2 = new DocxReader;
    $reader2->load($tempPath);
    $newTables = $reader2->extractTables();

    expect($newTables)->toHaveCount(count($originalTables));

    foreach ($originalTables as $index => $originalTable) {
        expect($newTables[$index]['rows'])->toBe($originalTable['rows']);
        expect($newTables[$index]['cells'])->toBe($originalTable['cells']);
    }

    $this->cleanTemp('roundtrip-tables.docx');
});

it('preserves page breaks through round-trip', function () {
    $reader = new DocxReader;
    $reader->load($this->fixturePath('complex.docx'));
    $originalBreaks = $reader->extractPageBreaks();

    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));
    $tempPath = $this->tempPath('roundtrip-breaks.docx');
    $writer->save($tempPath);

    $reader2 = new DocxReader;
    $reader2->load($tempPath);
    $newBreaks = $reader2->extractPageBreaks();

    expect($newBreaks)->toHaveCount(count($originalBreaks));

    $this->cleanTemp('roundtrip-breaks.docx');
});

it('preserves heading levels through round-trip', function () {
    $reader = new DocxReader;
    $reader->load($this->fixturePath('complex.docx'));
    $originalHeadings = $reader->extractHeadings();

    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));
    $tempPath = $this->tempPath('roundtrip-levels.docx');
    $writer->save($tempPath);

    $reader2 = new DocxReader;
    $reader2->load($tempPath);
    $newHeadings = $reader2->extractHeadings();

    expect($newHeadings)->toHaveCount(count($originalHeadings));

    foreach ($originalHeadings as $index => $originalHeading) {
        expect($newHeadings[$index]['level'])->toBe($originalHeading['level']);
    }

    $this->cleanTemp('roundtrip-levels.docx');
});

it('output DOCX is a valid ZIP file', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile($this->fixturePath('complex.docx'));
    $tempPath = $this->tempPath('roundtrip-zip.docx');
    $writer->save($tempPath);

    $zip = new ZipArchive;
    $result = $zip->open($tempPath);
    expect($result)->toBeTrue();

    expect($zip->locateName('[Content_Types].xml'))->not->toBeFalse();
    expect($zip->locateName('word/document.xml'))->not->toBeFalse();

    $zip->close();
    $this->cleanTemp('roundtrip-zip.docx');
});
