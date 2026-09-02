<?php

use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;

it('creates table detected element with correct metadata', function () {
    $element = DetectedElement::factory()->table()->create();

    expect($element->type)->toBe('table');
    expect($element->metadata)->toHaveKeys([
        'rows', 'columns', 'cells', 'has_header', 'column_widths', 'content', 'section',
    ]);
    expect($element->metadata['rows'])->toBeGreaterThan(0);
    expect($element->metadata['columns'])->toBeGreaterThan(0);
    expect($element->metadata['cells'])->toBeGreaterThan(0);
});

it('table metadata includes column count', function () {
    $element = DetectedElement::factory()->table()->create([
        'metadata' => [
            'rows' => 3,
            'columns' => 4,
            'cells' => 12,
            'has_header' => true,
            'column_widths' => [100, 200, 150, 150],
            'content' => [],
            'section' => 0,
        ],
    ]);

    expect($element->metadata['columns'])->toBe(4);
    expect($element->metadata['column_widths'])->toHaveCount(4);
});

it('table can have header row', function () {
    $element = DetectedElement::factory()->table()->create([
        'metadata' => [
            'rows' => 3,
            'columns' => 2,
            'cells' => 6,
            'has_header' => true,
            'column_widths' => [],
            'content' => [
                ['Name', 'Value'],
                ['Item 1', '100'],
                ['Item 2', '200'],
            ],
            'section' => 0,
        ],
    ]);

    expect($element->metadata['has_header'])->toBeTrue();
    expect($element->metadata['content'])->toHaveCount(3);
    expect($element->metadata['content'][0])->toEqual(['Name', 'Value']);
});

it('table can have content data', function () {
    $content = [
        ['Column A', 'Column B', 'Column C'],
        ['Row 1A', 'Row 1B', 'Row 1C'],
        ['Row 2A', 'Row 2B', 'Row 2C'],
    ];

    $element = DetectedElement::factory()->table()->create([
        'metadata' => [
            'rows' => 3,
            'columns' => 3,
            'cells' => 9,
            'has_header' => true,
            'column_widths' => [],
            'content' => $content,
            'section' => 1,
        ],
    ]);

    expect($element->metadata['content'])->toEqual($content);
});

it('lists all tables from a document analysis', function () {
    $analysis = DocumentAnalysis::factory()->create();

    DetectedElement::factory()->table()->create([
        'document_analysis_id' => $analysis->id,
        'element_index' => 0,
    ]);

    DetectedElement::factory()->table()->create([
        'document_analysis_id' => $analysis->id,
        'element_index' => 1,
    ]);

    DetectedElement::factory()->paragraph()->create([
        'document_analysis_id' => $analysis->id,
        'element_index' => 2,
    ]);

    $tables = DetectedElement::where('document_analysis_id', $analysis->id)
        ->where('type', 'table')
        ->get();

    expect($tables)->toHaveCount(2);
});

it('table factory generates valid metadata', function () {
    $table = DetectedElement::factory()->table()->create();

    expect($table->metadata['rows'])->toBeInt();
    expect($table->metadata['columns'])->toBeInt();
    expect($table->metadata['cells'])->toBeInt();
    expect($table->metadata['has_header'])->toBeBool();
    expect($table->metadata['section'])->toBeInt();
});

it('caption detected element has correct metadata', function () {
    $element = DetectedElement::factory()->caption()->create();

    expect($element->type)->toBe('caption');
    expect($element->metadata)->toHaveKeys([
        'label', 'number', 'element_type', 'section',
    ]);
    expect($element->metadata['label'])->toBeIn(['Figure', 'Tableau']);
    expect($element->metadata['number'])->toBeGreaterThan(0);
    expect($element->metadata['element_type'])->toBeIn(['figure', 'table']);
});

it('source detected element has correct metadata', function () {
    $element = DetectedElement::factory()->source()->create();

    expect($element->type)->toBe('source');
    expect($element->content)->not->toBeEmpty();
});
