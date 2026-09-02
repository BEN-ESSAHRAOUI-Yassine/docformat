<?php

namespace App\Services;

use App\Models\DetectedElement;

class CaptionDetector
{
    private const CAPTION_PATTERNS = [
        'fr' => [
            'figure' => '/^(?:Figure|Fig\.?)\s+(\d+)\s*[:\.]\s*(.+)$/i',
            'table' => '/^(?:Tableau|Tab\.?)\s+(\d+)\s*[:\.]\s*(.+)$/i',
        ],
        'en' => [
            'figure' => '/^(?:Figure|Fig\.?)\s+(\d+)\s*[:\.]\s*(.+)$/i',
            'table' => '/^(?:Table|Tab\.?)\s+(\d+)\s*[:\.]\s*(.+)$/i',
        ],
    ];

    /**
     * Detect captions from paragraph elements near figures/tables.
     *
     * @param  DetectedElement[]  $elements
     * @return array<int, array{label: string, number: int, text: string, element_type: string, element_index: int|null}>
     */
    public function detectCaptions(array $elements, string $language = 'fr'): array
    {
        $captions = [];
        $patterns = self::CAPTION_PATTERNS[$language] ?? self::CAPTION_PATTERNS['fr'];

        foreach ($elements as $element) {
            if ($element->type !== 'paragraph' || empty($element->content)) {
                continue;
            }

            foreach ($patterns as $elementType => $pattern) {
                if (preg_match($pattern, trim($element->content), $matches)) {
                    $captions[] = [
                        'label' => $this->normalizeLabel($matches[0], $elementType),
                        'number' => (int) $matches[1],
                        'text' => trim($matches[2]),
                        'element_type' => $elementType,
                        'element_index' => $this->findNearbyElement($elements, $element->element_index, $elementType),
                    ];
                    break;
                }
            }
        }

        return $captions;
    }

    /**
     * Find figures/tables missing captions.
     *
     * @param  DetectedElement[]  $elements
     * @return array<int, array{element_type: string, element_index: int, suggestion: string}>
     */
    public function findMissingCaptions(array $elements, string $language = 'fr'): array
    {
        $captions = $this->detectCaptions($elements, $language);
        $captionedIndices = array_column($captions, 'element_index');
        $missing = [];

        $figureCount = 0;
        $tableCount = 0;

        foreach ($elements as $element) {
            if ($element->type === 'figure') {
                $figureCount++;
                if (! in_array($element->element_index, $captionedIndices)) {
                    $missing[] = [
                        'element_type' => 'figure',
                        'element_index' => $element->element_index,
                        'suggestion' => $this->generateCaption('figure', $figureCount, $language),
                    ];
                }
            }

            if ($element->type === 'table') {
                $tableCount++;
                if (! in_array($element->element_index, $captionedIndices)) {
                    $missing[] = [
                        'element_type' => 'table',
                        'element_index' => $element->element_index,
                        'suggestion' => $this->generateCaption('table', $tableCount, $language),
                    ];
                }
            }
        }

        return $missing;
    }

    /**
     * Generate a caption for a figure or table.
     */
    public function generateCaption(string $elementType, int $number, string $language = 'fr'): string
    {
        $labels = match ($language) {
            'fr' => ['figure' => 'Figure', 'table' => 'Tableau'],
            default => ['figure' => 'Figure', 'table' => 'Table'],
        };

        $label = $labels[$elementType] ?? 'Element';

        return "{$label} {$number} :";
    }

    /**
     * Store detected captions as DetectedElement records.
     *
     * @param  DetectedElement[]  $elements
     * @return DetectedElement[] Created caption elements
     */
    public function storeCaptions(
        array $elements,
        int $documentId,
        int $analysisId,
        string $language = 'fr',
    ): array {
        $captions = $this->detectCaptions($elements, $language);
        $stored = [];

        foreach ($captions as $captionData) {
            $element = DetectedElement::create([
                'document_analysis_id' => $analysisId,
                'document_id' => $documentId,
                'type' => 'caption',
                'element_index' => $this->findNextIndex($elements) + count($stored),
                'content' => "{$captionData['label']} {$captionData['number']} : {$captionData['text']}",
                'metadata' => [
                    'label' => $captionData['label'],
                    'number' => $captionData['number'],
                    'element_type' => $captionData['element_type'],
                    'element_index' => $captionData['element_index'],
                    'section' => 0,
                ],
            ]);

            $stored[] = $element;
        }

        return $stored;
    }

    private function normalizeLabel(string $match, string $elementType): string
    {
        if (str_starts_with(strtolower($match), 'fig')) {
            return 'Figure';
        }

        return 'Tableau';
    }

    private function findNearbyElement(array $elements, int $currentIndex, string $type): ?int
    {
        $typeMap = [
            'figure' => 'figure',
            'table' => 'table',
        ];

        $targetType = $typeMap[$type] ?? $type;

        foreach ($elements as $element) {
            if ($element->type === $targetType && $element->element_index > $currentIndex) {
                return $element->element_index;
            }
        }

        return null;
    }

    private function findNextIndex(array $elements): int
    {
        if (empty($elements)) {
            return 0;
        }

        return max(array_column($elements, 'element_index')) + 1;
    }
}
