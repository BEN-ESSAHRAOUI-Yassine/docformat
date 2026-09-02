<?php

namespace App\Services;

use App\Models\DetectedElement;

class CaptionService
{
    public function __construct(
        private CaptionDetector $detector,
    ) {}

    /**
     * Analyze captions for a document's detected elements.
     *
     * @param  DetectedElement[]  $elements
     * @return array{existing: array, missing: array, proposed: array}
     */
    public function analyzeCaptions(array $elements, string $language = 'fr'): array
    {
        $existing = $this->detector->detectCaptions($elements, $language);
        $missing = $this->detector->findMissingCaptions($elements, $language);

        $proposed = [];
        foreach ($missing as $item) {
            $proposed[] = [
                'element_type' => $item['element_type'],
                'element_index' => $item['element_index'],
                'caption' => $item['suggestion'],
            ];
        }

        return [
            'existing' => $existing,
            'missing' => $missing,
            'proposed' => $proposed,
        ];
    }

    /**
     * Store all detected and proposed captions.
     *
     * @param  DetectedElement[]  $elements
     * @return array{stored: int, captions: array}
     */
    public function storeAllCaptions(
        array $elements,
        int $documentId,
        int $analysisId,
        string $language = 'fr',
    ): array {
        $stored = $this->detector->storeCaptions($elements, $documentId, $analysisId, $language);

        return [
            'stored' => count($stored),
            'captions' => $stored,
        ];
    }

    /**
     * Get caption format for a given language.
     */
    public function getCaptionFormat(string $language = 'fr'): array
    {
        return match ($language) {
            'fr' => [
                'figure' => 'Figure %d : %s',
                'table' => 'Tableau %d : %s',
            ],
            default => [
                'figure' => 'Figure %d: %s',
                'table' => 'Table %d: %s',
            ],
        };
    }
}
