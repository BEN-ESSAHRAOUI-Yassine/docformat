<?php

use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;
use App\Services\CaptionDetector;
use App\Services\CaptionService;

it('detects figure caption from paragraph content', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Figure 1 : Résultats de l\'expérience',
        ]),
    ];

    $detector = new CaptionDetector;
    $captions = $detector->detectCaptions($elements, 'fr');

    expect($captions)->toHaveCount(1);
    expect($captions[0]['label'])->toBe('Figure');
    expect($captions[0]['number'])->toBe(1);
    expect($captions[0]['text'])->toBe('Résultats de l\'expérience');
    expect($captions[0]['element_type'])->toBe('figure');
});

it('detects table caption from paragraph content', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Tableau 2 : Données expérimentales',
        ]),
    ];

    $detector = new CaptionDetector;
    $captions = $detector->detectCaptions($elements, 'fr');

    expect($captions)->toHaveCount(1);
    expect($captions[0]['label'])->toBe('Tableau');
    expect($captions[0]['number'])->toBe(2);
    expect($captions[0]['element_type'])->toBe('table');
});

it('detects English figure caption', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Figure 3: Experimental results',
        ]),
    ];

    $detector = new CaptionDetector;
    $captions = $detector->detectCaptions($elements, 'en');

    expect($captions)->toHaveCount(1);
    expect($captions[0]['label'])->toBe('Figure');
    expect($captions[0]['number'])->toBe(3);
    expect($captions[0]['text'])->toBe('Experimental results');
});

it('detects English table caption', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
            'content' => 'Table 4: Data summary',
        ]),
    ];

    $detector = new CaptionDetector;
    $captions = $detector->detectCaptions($elements, 'en');

    expect($captions)->toHaveCount(1);
    expect($captions[0]['label'])->toBe('Tableau');
    expect($captions[0]['number'])->toBe(4);
    expect($captions[0]['element_type'])->toBe('table');
});

it('finds figures missing captions', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Figure 1 : Mon schéma',
        ]),
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
    ];

    $detector = new CaptionDetector;
    $missing = $detector->findMissingCaptions($elements, 'fr');

    expect($missing)->toHaveCount(1);
    expect($missing[0]['element_type'])->toBe('figure');
    expect($missing[0]['element_index'])->toBe(0);
});

it('finds tables missing captions', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $elements = [
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 1,
            'content' => 'Tableau 1 : Résultats',
        ]),
        DetectedElement::factory()->table()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
        ]),
    ];

    $detector = new CaptionDetector;
    $missing = $detector->findMissingCaptions($elements, 'fr');

    expect($missing)->toHaveCount(1);
    expect($missing[0]['element_type'])->toBe('table');
    expect($missing[0]['element_index'])->toBe(0);
});

it('generates correct caption format for french', function () {
    $detector = new CaptionDetector;

    expect($detector->generateCaption('figure', 1, 'fr'))->toBe('Figure 1 :');
    expect($detector->generateCaption('table', 2, 'fr'))->toBe('Tableau 2 :');
});

it('generates correct caption format for english', function () {
    $detector = new CaptionDetector;

    expect($detector->generateCaption('figure', 1, 'en'))->toBe('Figure 1 :');
    expect($detector->generateCaption('table', 2, 'en'))->toBe('Table 2 :');
});

it('stores captions as detected elements', function () {
    $analysis = DocumentAnalysis::factory()->create();
    $document = $analysis->document;
    $elements = [
        DetectedElement::factory()->figure()->create([
            'document_analysis_id' => $analysis->id,
            'document_id' => $document->id,
            'element_index' => 0,
        ]),
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'document_id' => $document->id,
            'element_index' => 1,
            'content' => 'Figure 1 : Test image',
        ]),
    ];

    $detector = new CaptionDetector;
    $stored = $detector->storeCaptions($elements, $document->id, $analysis->id, 'fr');

    expect($stored)->toHaveCount(1);
    expect($stored[0]->type)->toBe('caption');
    expect($stored[0]->metadata['label'])->toBe('Figure');
    expect($stored[0]->metadata['number'])->toBe(1);
});

it('caption service analyzes all captions', function () {
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
        DetectedElement::factory()->paragraph()->create([
            'document_analysis_id' => $analysis->id,
            'element_index' => 2,
            'content' => 'Figure 1 : Première image',
        ]),
    ];

    $service = new CaptionService(new CaptionDetector);
    $result = $service->analyzeCaptions($elements, 'fr');

    expect($result['existing'])->toHaveCount(1);
    expect($result['missing'])->toHaveCount(1);
    expect($result['proposed'])->toHaveCount(1);
    expect($result['proposed'][0]['caption'])->toContain('Figure');
});

it('caption service returns correct format for language', function () {
    $service = new CaptionService(new CaptionDetector);

    $frFormat = $service->getCaptionFormat('fr');
    expect($frFormat['figure'])->toBe('Figure %d : %s');
    expect($frFormat['table'])->toBe('Tableau %d : %s');

    $enFormat = $service->getCaptionFormat('en');
    expect($enFormat['figure'])->toBe('Figure %d: %s');
    expect($enFormat['table'])->toBe('Table %d: %s');
});
