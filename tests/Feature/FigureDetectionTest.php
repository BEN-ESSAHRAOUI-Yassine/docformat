<?php

use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;

it('creates figure detected element with correct metadata', function () {
    $element = DetectedElement::factory()->figure()->create();

    expect($element->type)->toBe('figure');
    expect($element->metadata)->toHaveKeys([
        'name', 'image_type', 'width', 'height', 'is_watermark', 'section',
    ]);
    expect($element->metadata['name'])->toContain('image_');
    expect($element->metadata['width'])->toBeGreaterThan(0);
    expect($element->metadata['height'])->toBeGreaterThan(0);
});

it('figure metadata includes image type', function () {
    $element = DetectedElement::factory()->figure()->create([
        'metadata' => [
            'name' => 'photo.png',
            'image_type' => 'image/png',
            'width' => 640,
            'height' => 480,
            'is_watermark' => false,
            'section' => 0,
        ],
    ]);

    expect($element->metadata['image_type'])->toBe('image/png');
    expect($element->metadata['name'])->toBe('photo.png');
});

it('figure can be detected as watermark', function () {
    $element = DetectedElement::factory()->figure()->create([
        'metadata' => [
            'name' => 'watermark.png',
            'image_type' => 'image/png',
            'width' => 200,
            'height' => 200,
            'is_watermark' => true,
            'section' => 0,
        ],
    ]);

    expect($element->metadata['is_watermark'])->toBeTrue();
});

it('lists all figures from a document analysis', function () {
    $analysis = DocumentAnalysis::factory()->create();

    DetectedElement::factory()->figure()->create([
        'document_analysis_id' => $analysis->id,
        'element_index' => 0,
    ]);

    DetectedElement::factory()->figure()->create([
        'document_analysis_id' => $analysis->id,
        'element_index' => 1,
    ]);

    DetectedElement::factory()->paragraph()->create([
        'document_analysis_id' => $analysis->id,
        'element_index' => 2,
    ]);

    $figures = DetectedElement::where('document_analysis_id', $analysis->id)
        ->where('type', 'figure')
        ->get();

    expect($figures)->toHaveCount(2);
});

it('figure factory generates valid metadata', function () {
    $figure = DetectedElement::factory()->figure()->create();

    expect($figure->metadata['width'])->toBeInt();
    expect($figure->metadata['height'])->toBeInt();
    expect($figure->metadata['is_watermark'])->toBeBool();
    expect($figure->metadata['section'])->toBeInt();
});
