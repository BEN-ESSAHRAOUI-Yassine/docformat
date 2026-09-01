<?php

use App\Enums\EnforcementMode;
use App\Models\DetectedElement;
use App\Models\DocumentAnalysis;
use App\Models\StyleProfile;
use App\Services\StyleEngine\StyleEngine;

it('detects font family violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Arial'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('font_family');
    expect($violations[0]->severity)->toBe('error');
});

it('detects font size violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_size' => 11]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_size' => 12],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('font_size');
});

it('produces no violation for compliant formatting', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman', 'font_size' => 11]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Times New Roman', 'font_size' => 11],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(0);
});

it('respects enabled check types filter', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman', 'font_size' => 11]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Arial', 'font_size' => 12],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile, ['font_family']);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('font_family');
});

it('checks heading elements against heading rules', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => [
            'body' => ['font_family' => 'Times New Roman'],
            'heading_1' => ['font_family' => 'Times New Roman', 'font_size' => 18],
        ],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['font_family' => 'Arial', 'font_size' => 14],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(2);
});

it('detects bold violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_1' => ['bold' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['bold' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('bold');
});

it('detects alignment violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['alignment' => 'justify']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['alignment' => 'left'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('alignment');
});

it('detects italic violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['sources' => ['italic' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'source',
        'metadata' => ['italic' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('italic');
});

it('detects underline violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['sources' => ['underline' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'source',
        'metadata' => ['underline' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('underline');
});

it('detects all_caps violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_1' => ['all_caps' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['all_caps' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('all_caps');
});

it('detects small_caps violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_2' => ['small_caps' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 2,
        'metadata' => ['small_caps' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('small_caps');
});

it('detects indentation violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_2' => ['indentation' => 0.25]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 2,
        'metadata' => ['indentation' => 0],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('indentation');
});

it('detects line_spacing violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['line_spacing' => 1.5]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['line_spacing' => 1.0],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('line_spacing');
});

it('detects spacing_before violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_1' => ['spacing_before' => 24]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['spacing_before' => 12],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('spacing');
});

it('detects spacing_after violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_1' => ['spacing_after' => 12]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['spacing_after' => 6],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('spacing');
});

it('detects numbering violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_3' => ['numbering' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 3,
        'metadata' => ['numbering' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('numbering');
});

it('detects borders violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_1' => ['borders' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['borders' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('borders');
});

it('detects shading violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['heading_1' => ['shading' => true]],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'heading',
        'heading_level' => 1,
        'metadata' => ['shading' => false],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('shading');
});

it('detects paragraph_style violation', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['paragraph_style' => 'Normal']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['paragraph_style' => 'Body Text'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->check_type)->toBe('paragraph_style');
});

it('returns all 16 check types', function () {
    $engine = new StyleEngine;
    $checks = $engine->getEnabledChecks([]);

    expect($checks)->toHaveCount(16);
    expect(array_keys($checks))->toContain('font_family');
    expect(array_keys($checks))->toContain('font_size');
    expect(array_keys($checks))->toContain('font_color');
    expect(array_keys($checks))->toContain('bold');
    expect(array_keys($checks))->toContain('italic');
    expect(array_keys($checks))->toContain('underline');
    expect(array_keys($checks))->toContain('alignment');
    expect(array_keys($checks))->toContain('all_caps');
    expect(array_keys($checks))->toContain('small_caps');
    expect(array_keys($checks))->toContain('indentation');
    expect(array_keys($checks))->toContain('line_spacing');
    expect(array_keys($checks))->toContain('spacing');
    expect(array_keys($checks))->toContain('numbering');
    expect(array_keys($checks))->toContain('borders');
    expect(array_keys($checks))->toContain('shading');
    expect(array_keys($checks))->toContain('paragraph_style');
});

it('sets auto_fix to true for strict enforcement mode', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Arial'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile, [], EnforcementMode::Strict);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->auto_fix)->toBeTrue();
});

it('sets auto_fix to false for recommended enforcement mode', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Arial'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile, [], EnforcementMode::Recommended);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->auto_fix)->toBeFalse();
});

it('sets auto_fix to false for audit_only enforcement mode', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Arial'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile, [], EnforcementMode::AuditOnly);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->auto_fix)->toBeFalse();
});

it('does not set auto_fix when no enforcement mode provided', function () {
    $profile = StyleProfile::factory()->create([
        'rules' => ['body' => ['font_family' => 'Times New Roman']],
    ]);

    $analysis = DocumentAnalysis::factory()->create();
    $element = DetectedElement::factory()->create([
        'document_analysis_id' => $analysis->id,
        'type' => 'paragraph',
        'metadata' => ['font_family' => 'Arial'],
    ]);

    $engine = new StyleEngine;
    $violations = $engine->analyze([$element], $profile);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->auto_fix)->toBeNull();
});
