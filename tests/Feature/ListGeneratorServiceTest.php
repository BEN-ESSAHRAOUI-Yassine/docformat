<?php

use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;
use App\Services\ListGeneratorService;

it('generates list of figures in french', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 1 : Diagramme de flux',
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'page' => 3],
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'content' => 'Figure 2 : Résultats expérimentaux',
            'metadata' => ['label' => 'Figure', 'number' => 2, 'element_type' => 'figure', 'element_index' => 2, 'page' => 5],
        ]),
    ];

    $service = new ListGeneratorService;
    $list = $service->generateListOfFigures($elements, 'fr');

    expect($list['title'])->toBe('LISTE DES FIGURES');
    expect($list['entries'])->toHaveCount(2);
    expect($list['total'])->toBe(2);
    expect($list['entries'][0]['number'])->toBe(1);
    expect($list['entries'][0]['caption'])->toBe('Diagramme de flux');
    expect($list['entries'][0]['page'])->toBe(3);
    expect($list['entries'][1]['number'])->toBe(2);
    expect($list['entries'][1]['caption'])->toBe('Résultats expérimentaux');
    expect($list['entries'][1]['page'])->toBe(5);
});

it('generates list of figures in english', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 1: Data Flow Diagram',
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'page' => 3],
        ]),
    ];

    $service = new ListGeneratorService;
    $list = $service->generateListOfFigures($elements, 'en');

    expect($list['title'])->toBe('LIST OF FIGURES');
    expect($list['entries'])->toHaveCount(1);
    expect($list['entries'][0]['caption'])->toBe('Data Flow Diagram');
});

it('generates list of tables in french', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Tableau 1 : Données de test',
            'metadata' => ['label' => 'Tableau', 'number' => 1, 'element_type' => 'table', 'element_index' => 0, 'page' => 7],
        ]),
    ];

    $service = new ListGeneratorService;
    $list = $service->generateListOfTables($elements, 'fr');

    expect($list['title'])->toBe('LISTE DES TABLEAUX');
    expect($list['entries'])->toHaveCount(1);
    expect($list['entries'][0]['caption'])->toBe('Données de test');
});

it('generates list of tables in english', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Table 1: Test Data',
            'metadata' => ['label' => 'Table', 'number' => 1, 'element_type' => 'table', 'element_index' => 0, 'page' => 7],
        ]),
    ];

    $service = new ListGeneratorService;
    $list = $service->generateListOfTables($elements, 'en');

    expect($list['title'])->toBe('LIST OF TABLES');
    expect($list['entries'][0]['caption'])->toBe('Test Data');
});

it('generates both lists at once', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 1 : Test',
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'page' => 1],
        ]),
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 3,
            'content' => 'Tableau 1 : Données',
            'metadata' => ['label' => 'Tableau', 'number' => 1, 'element_type' => 'table', 'element_index' => 2, 'page' => 2],
        ]),
    ];

    $service = new ListGeneratorService;
    $lists = $service->generateAllLists($elements, 'fr');

    expect($lists['figures']['title'])->toBe('LISTE DES FIGURES');
    expect($lists['tables']['title'])->toBe('LISTE DES TABLEAUX');
    expect($lists['figures']['total'])->toBe(1);
    expect($lists['tables']['total'])->toBe(1);
});

it('formats list as text', function () {
    $list = [
        'title' => 'LISTE DES FIGURES',
        'entries' => [
            ['number' => 1, 'caption' => 'Diagramme', 'page' => 3],
            ['number' => 2, 'caption' => 'Résultats', 'page' => 5],
        ],
        'total' => 2,
    ];

    $service = new ListGeneratorService;
    $lines = $service->formatAsText($list);

    expect($lines[0])->toBe('LISTE DES FIGURES');
    expect($lines[2])->toContain('1.');
    expect($lines[2])->toContain('Diagramme');
    expect($lines[2])->toContain('3');
    expect($lines[3])->toContain('2.');
    expect($lines[3])->toContain('Résultats');
    expect($lines[3])->toContain('5');
});

it('formats list with null page', function () {
    $list = [
        'title' => 'LISTE DES FIGURES',
        'entries' => [
            ['number' => 1, 'caption' => 'Test', 'page' => null],
        ],
        'total' => 1,
    ];

    $service = new ListGeneratorService;
    $lines = $service->formatAsText($list);

    expect($lines[2])->not->toContain('.......');
});

it('sorts entries by number', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Figure 3 : Troisième',
            'metadata' => ['label' => 'Figure', 'number' => 3, 'element_type' => 'figure', 'element_index' => 0, 'page' => 10],
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 1 : Première',
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'page' => 3],
        ]),
    ];

    $service = new ListGeneratorService;
    $list = $service->generateListOfFigures($elements, 'fr');

    expect($list['entries'][0]['number'])->toBe(1);
    expect($list['entries'][1]['number'])->toBe(3);
});

it('detects changes needed when captions change', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Figure 1 : Test',
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'page' => 1],
        ]),
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 2 : Nouveau',
            'metadata' => ['label' => 'Figure', 'number' => 2, 'element_type' => 'figure', 'element_index' => 1, 'page' => 2],
        ]),
    ];

    $service = new ListGeneratorService;
    $changes = $service->detectChangesNeeded($elements, 'fr');

    expect($changes['figures']['changes_needed'])->toBeFalse();
    expect($changes['figures']['missing_entries'])->toBeEmpty();
});

it('detects outdated entries after renumbering', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->caption()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Figure 1 : Ancien',
            'metadata' => ['label' => 'Figure', 'number' => 1, 'element_type' => 'figure', 'element_index' => 0, 'page' => 1],
        ]),
    ];

    $service = new ListGeneratorService;
    $changes = $service->detectChangesNeeded($elements, 'fr');

    expect($changes['figures']['changes_needed'])->toBeFalse();
});

it('returns empty list when no captions exist', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
    ];

    $service = new ListGeneratorService;
    $list = $service->generateListOfFigures($elements, 'fr');

    expect($list['entries'])->toHaveCount(0);
    expect($list['total'])->toBe(0);
});
