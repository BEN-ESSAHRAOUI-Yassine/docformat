<?php

use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;
use App\Services\PageIntegrityService;

it('detects no oversized elements for normal content', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'metadata' => ['width' => 400, 'height' => 300, 'section' => 0],
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
    ];

    $service = new PageIntegrityService;
    $result = $service->analyzeIntegrity($elements);

    expect($result['oversized'])->toHaveCount(0);
});

it('detects oversized figure by width', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'metadata' => ['width' => 800, 'height' => 200, 'section' => 0],
        ]),
    ];

    $service = new PageIntegrityService;
    $oversized = $service->detectOversized($elements);

    expect($oversized)->toHaveCount(1);
    expect($oversized[0]['exceeds'])->toContain('width');
});

it('detects oversized figure by height', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'metadata' => ['width' => 300, 'height' => 1200, 'section' => 0],
        ]),
    ];

    $service = new PageIntegrityService;
    $oversized = $service->detectOversized($elements);

    expect($oversized)->toHaveCount(1);
    expect($oversized[0]['exceeds'])->toContain('height');
});

it('detects oversized table', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'metadata' => ['width' => 700, 'height' => 150, 'rows' => 20, 'columns' => 8, 'section' => 0],
        ]),
    ];

    $service = new PageIntegrityService;
    $oversized = $service->detectOversized($elements);

    expect($oversized)->toHaveCount(1);
    expect($oversized[0]['type'])->toBe('table');
});

it('detects uncaptioned figures', function () {
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
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'section' => 0],
        ]),
    ];

    $service = new PageIntegrityService;
    $issues = $service->detectIntegrityIssues($elements);

    $uncaptioned = array_filter($issues, fn ($i) => $i['issue'] === 'uncaptioned_figure');
    expect($uncaptioned)->toHaveCount(1);
    expect($uncaptioned[0]['element_index'])->toBe(1);
});

it('detects uncaptioned tables', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
    ];

    $service = new PageIntegrityService;
    $issues = $service->detectIntegrityIssues($elements);

    expect($issues)->toHaveCount(1);
    expect($issues[0]['issue'])->toBe('uncaptioned_table');
});

it('detects orphaned captions', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 99, 'section' => 0],
        ]),
    ];

    $service = new PageIntegrityService;
    $issues = $service->detectIntegrityIssues($elements);

    expect($issues)->toHaveCount(1);
    expect($issues[0]['issue'])->toBe('orphaned_caption');
});

it('generates warnings for oversized and integrity issues', function () {
    $oversized = [
        ['element_index' => 0, 'type' => 'figure', 'width_mm' => 220.0, 'height_mm' => 100.0, 'exceeds' => 'width'],
    ];
    $integrityIssues = [
        ['issue' => 'uncaptioned_figure', 'element_index' => 1, 'details' => 'Figure at index 1 has no caption'],
    ];

    $service = new PageIntegrityService;
    $warnings = $service->generateWarnings($oversized, $integrityIssues);

    expect($warnings)->toHaveCount(2);
    expect($warnings[0]['type'])->toBe('oversized');
    expect($warnings[1]['type'])->toBe('integrity');
});

it('generates correct appendix reference for figure', function () {
    $service = new PageIntegrityService;
    $ref = $service->generateAppendixReference('figure', 5, 'A');

    expect($ref)->toBe('Suite du Figure 5 : Voir Annexe A');
});

it('generates correct appendix reference for table', function () {
    $service = new PageIntegrityService;
    $ref = $service->generateAppendixReference('table', 8, 'B');

    expect($ref)->toBe('Suite du Tableau 8 : Voir Annexe B');
});

it('estimates page count for normal element', function () {
    $element = DetectedElement::factory()->figure()->create([
        'metadata' => ['width' => 400, 'height' => 300],
    ]);

    $service = new PageIntegrityService;
    $pages = $service->estimatePageCount($element);

    expect($pages)->toBe(1);
});

it('estimates multiple pages for tall element', function () {
    $element = DetectedElement::factory()->figure()->create([
        'metadata' => ['width' => 400, 'height' => 2000],
    ]);

    $service = new PageIntegrityService;
    $pages = $service->estimatePageCount($element);

    expect($pages)->toBeGreaterThan(1);
});
