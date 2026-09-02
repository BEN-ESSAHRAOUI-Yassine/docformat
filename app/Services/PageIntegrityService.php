<?php

namespace App\Services;

use App\Models\DetectedElement;

class PageIntegrityService
{
    private const PAGE_WIDTH_MM = 210;

    private const PAGE_HEIGHT_MM = 297;

    private const MARGIN_MM = 25;

    private const MAX_CONTENT_WIDTH_MM = self::PAGE_WIDTH_MM - (2 * self::MARGIN_MM);

    private const MAX_CONTENT_HEIGHT_MM = self::PAGE_HEIGHT_MM - (2 * self::MARGIN_MM);

    private const MM_PER_PX = 0.264583;

    /**
     * Analyze page integrity for figures and tables.
     *
     * @param  DetectedElement[]  $elements
     * @return array{oversized: array, integrity_issues: array, warnings: array}
     */
    public function analyzeIntegrity(array $elements): array
    {
        $oversized = $this->detectOversized($elements);
        $integrityIssues = $this->detectIntegrityIssues($elements);
        $warnings = $this->generateWarnings($oversized, $integrityIssues);

        return [
            'oversized' => $oversized,
            'integrity_issues' => $integrityIssues,
            'warnings' => $warnings,
        ];
    }

    /**
     * Detect oversized figures/tables that exceed page capacity.
     *
     * @param  DetectedElement[]  $elements
     * @return array<int, array{element_index: int, type: string, width_mm: float, height_mm: float, exceeds: string}>
     */
    public function detectOversized(array $elements): array
    {
        $oversized = [];

        foreach ($elements as $element) {
            if (! in_array($element->type, ['figure', 'table'])) {
                continue;
            }

            $widthMm = ($element->metadata['width'] ?? 0) * self::MM_PER_PX;
            $heightMm = ($element->metadata['height'] ?? 0) * self::MM_PER_PX;

            $exceeds = [];
            if ($widthMm > self::MAX_CONTENT_WIDTH_MM) {
                $exceeds[] = 'width';
            }
            if ($heightMm > self::MAX_CONTENT_HEIGHT_MM) {
                $exceeds[] = 'height';
            }

            if ($exceeds !== []) {
                $oversized[] = [
                    'element_index' => $element->element_index,
                    'type' => $element->type,
                    'width_mm' => round($widthMm, 1),
                    'height_mm' => round($heightMm, 1),
                    'exceeds' => implode(' and ', $exceeds),
                ];
            }
        }

        return $oversized;
    }

    /**
     * Detect integrity issues (figure without caption, caption without figure, etc.).
     *
     * @param  DetectedElement[]  $elements
     * @return array<int, array{issue: string, element_index: int|null, details: string}>
     */
    public function detectIntegrityIssues(array $elements): array
    {
        $issues = [];
        $figures = $this->getElementsByType($elements, 'figure');
        $tables = $this->getElementsByType($elements, 'table');
        $captions = $this->getElementsByType($elements, 'caption');
        $sources = $this->getElementsByType($elements, 'source');

        $captionedFigureIndices = $this->getCaptionedIndices($captions, 'figure');
        $captionedTableIndices = $this->getCaptionedIndices($captions, 'table');

        foreach ($figures as $figure) {
            if (! in_array($figure->element_index, $captionedFigureIndices)) {
                $issues[] = [
                    'issue' => 'uncaptioned_figure',
                    'element_index' => $figure->element_index,
                    'details' => "Figure at index {$figure->element_index} has no caption",
                ];
            }
        }

        foreach ($tables as $table) {
            if (! in_array($table->element_index, $captionedTableIndices)) {
                $issues[] = [
                    'issue' => 'uncaptioned_table',
                    'element_index' => $table->element_index,
                    'details' => "Table at index {$table->element_index} has no caption",
                ];
            }
        }

        foreach ($captions as $caption) {
            $elementType = $caption->metadata['element_type'] ?? null;
            $elementIndex = $caption->metadata['element_index'] ?? null;

            if ($elementType && $elementIndex !== null) {
                $targetElements = $elementType === 'figure' ? $figures : $tables;
                $found = false;
                foreach ($targetElements as $el) {
                    if ($el->element_index === $elementIndex) {
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $issues[] = [
                        'issue' => 'orphaned_caption',
                        'element_index' => $caption->element_index,
                        'details' => "Caption references {$elementType} at index {$elementIndex} which does not exist",
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Generate warnings for oversized elements.
     *
     * @return array<int, array{type: string, message: string, element_index: int|null}>
     */
    public function generateWarnings(array $oversized, array $integrityIssues): array
    {
        $warnings = [];

        foreach ($oversized as $item) {
            $warnings[] = [
                'type' => 'oversized',
                'message' => "{$item['type']} at index {$item['element_index']} exceeds page {$item['exceeds']} ({$item['width_mm']}x{$item['height_mm']}mm)",
                'element_index' => $item['element_index'],
            ];
        }

        foreach ($integrityIssues as $issue) {
            $warnings[] = [
                'type' => 'integrity',
                'message' => $issue['details'],
                'element_index' => $issue['element_index'],
            ];
        }

        return $warnings;
    }

    /**
     * Generate appendix reference text for oversized content.
     */
    public function generateAppendixReference(string $elementType, int $number, string $annexLetter): string
    {
        $label = $elementType === 'figure' ? 'Figure' : 'Tableau';

        return "Suite du {$label} {$number} : Voir Annexe {$annexLetter}";
    }

    /**
     * Calculate how many pages an element would occupy.
     */
    public function estimatePageCount(DetectedElement $element): int
    {
        $widthMm = ($element->metadata['width'] ?? 0) * self::MM_PER_PX;
        $heightMm = ($element->metadata['height'] ?? 0) * self::MM_PER_PX;

        if ($widthMm <= 0 || $heightMm <= 0) {
            return 1;
        }

        $widthPages = ceil($widthMm / self::MAX_CONTENT_WIDTH_MM);
        $heightPages = ceil($heightMm / self::MAX_CONTENT_HEIGHT_MM);

        return (int) max($widthPages, $heightPages, 1);
    }

    /**
     * @param  DetectedElement[]  $elements
     * @return DetectedElement[]
     */
    private function getElementsByType(array $elements, string $type): array
    {
        return array_values(array_filter($elements, fn (DetectedElement $el) => $el->type === $type));
    }

    /**
     * @param  DetectedElement[]  $captions
     * @return array<int>
     */
    private function getCaptionedIndices(array $captions, string $elementType): array
    {
        $indices = [];
        foreach ($captions as $caption) {
            if (($caption->metadata['element_type'] ?? null) === $elementType) {
                $indices[] = $caption->metadata['element_index'] ?? null;
            }
        }

        return array_values(array_filter($indices, fn ($i) => $i !== null));
    }
}
