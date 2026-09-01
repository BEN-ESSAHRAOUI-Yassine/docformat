<?php

use App\Services\DocxEngine\DocxWriter;
use Tests\TestCase;

uses(TestCase::class);

it('can create a new empty document', function () {
    $writer = new DocxWriter;
    $writer->createNew();

    expect($writer->getPhpWord())->not->toBeNull();
});

it('can add headings to a new document', function () {
    $writer = new DocxWriter;
    $writer->createNew();
    $writer->addHeading('Test Heading', 1);
    $writer->addHeading('Sub Heading', 2);

    $phpWord = $writer->getPhpWord();
    expect($phpWord->getSections())->toHaveCount(1);
});

it('can add paragraphs to a new document', function () {
    $writer = new DocxWriter;
    $writer->createNew();
    $writer->addParagraph('Hello world');

    $phpWord = $writer->getPhpWord();
    expect($phpWord->getSections())->toHaveCount(1);
});

it('can save a new document', function () {
    $writer = new DocxWriter;
    $writer->createNew();
    $writer->addHeading('Saved Document', 1);
    $writer->addParagraph('This document was saved.');

    $tempPath = tempPath('writer-test-new.docx');
    $result = $writer->save($tempPath);

    expect($result)->toBeTrue();
    expect(file_exists($tempPath))->toBeTrue();

    cleanTemp('writer-test-new.docx');
});

it('can load and modify an existing document', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile(fixturePath('simple.docx'));

    expect($writer->getPhpWord())->not->toBeNull();
});

it('can modify a heading in existing document', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile(fixturePath('simple.docx'));

    $result = $writer->modifyHeading(0, 'Modified Title');
    expect($result)->toBeTrue();

    $tempPath = tempPath('writer-test-modified.docx');
    $writer->save($tempPath);
    expect(file_exists($tempPath))->toBeTrue();

    cleanTemp('writer-test-modified.docx');
});

it('returns false when modifying heading in non-loaded document', function () {
    $writer = new DocxWriter;
    $result = $writer->modifyHeading(0, 'Test');

    expect($result)->toBeFalse();
});

it('returns false when saving non-loaded document', function () {
    $writer = new DocxWriter;
    $result = $writer->save(tempPath('should-not-exist.docx'));

    expect($result)->toBeFalse();
});

it('throws exception for non-existent file', function () {
    $writer = new DocxWriter;
    $writer->loadFromFile('nonexistent.docx');
})->throws(RuntimeException::class, 'DOCX file not found');
