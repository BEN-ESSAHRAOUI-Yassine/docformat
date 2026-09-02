<?php

use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;
use App\Services\NumberingService;

it('detects no issues with sequential numbering', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'metadata' => ['label' => 'Figure', 'number' => 2, 'element_type' => 'figure', 'element_index' => 2, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->detectInconsistencies($elements);

    expect($result['figures'])->toHaveCount(0);
    expect($result['summary']['figure_issues'])->toBe(0);
});

it('detects missing figure number', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => null, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->detectInconsistencies($elements);

    expect($result['figures'])->toHaveCount(1);
    expect($result['figures'][0]['issue'])->toContain('Missing number');
});

it('detects duplicate figure numbers', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 2, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->detectInconsistencies($elements);

    expect($result['figures'])->toHaveCount(1);
    expect($result['figures'][0]['issue'])->toContain('duplicated');
});

it('detects missing figure in sequence', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'metadata' => ['label' => 'Figure', 'number' => 3, 'element_type' => 'figure', 'element_index' => 2, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->detectInconsistencies($elements);

    expect($result['figures'])->toHaveCount(1);
    expect($result['figures'][0]['issue'])->toContain('missing');
    expect($result['figures'][0]['expected_number'])->toBe(2);
});

it('detects table numbering issues', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Tableau', 'number' => 1, 'element_type' => 'table', 'element_index' => 0, 'section' => 0],
        ]),
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'metadata' => ['label' => 'Tableau', 'number' => 1, 'element_type' => 'table', 'element_index' => 2, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->detectInconsistencies($elements);

    expect($result['tables'])->toHaveCount(1);
    expect($result['tables'][0]['issue'])->toContain('duplicated');
});

it('generates renumbering preview', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => 5, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'metadata' => ['label' => 'Figure', 'number' => 3, 'element_type' => 'figure', 'element_index' => 2, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $preview = $service->previewRenumbering($elements);

    expect($preview['figures'])->toHaveCount(2);
    expect($preview['figures'][0]['old_number'])->toBe(5);
    expect($preview['figures'][0]['new_number'])->toBe(1);
    expect($preview['figures'][0]['changed'])->toBeTrue();
    expect($preview['figures'][1]['old_number'])->toBe(3);
    expect($preview['figures'][1]['new_number'])->toBe(2);
    expect($preview['figures'][1]['changed'])->toBeTrue();
});

it('shows no change when numbering is correct', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $preview = $service->previewRenumbering($elements);

    expect($preview['figures'][0]['changed'])->toBeFalse();
});

it('applies renumbering to captions', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 5 : Test',
            'metadata' => ['label' => 'Figure', 'number' => 5, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->applyRenumbering($elements);

    expect($result['updated'])->toBe(1);
    expect($result['captions'][0]->content)->toContain('1');
    expect($result['captions'][0]->metadata['number'])->toBe(1);
});

it('returns correct summary', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
        ]),
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
    ];

    $service = new NumberingService;
    $result = $service->detectInconsistencies($elements);

    expect($result['summary']['total_figures'])->toBe(2);
    expect($result['summary']['total_tables'])->toBe(1);
    expect($result['summary']['captioned_figures'])->toBe(1);
    expect($result['summary']['captioned_tables'])->toBe(0);
});
