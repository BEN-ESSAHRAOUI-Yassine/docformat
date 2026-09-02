<?php

namespace App\Services;

use App\Models\DetectedElement;

class NumberingService
{
    /**
     * Detect numbering inconsistencies for figures and tables.
     *
     * @param  DetectedElement[]  $elements
     * @return array{figures: array, tables: array, summary: array}
     */
    public function detectInconsistencies(array $elements): array
    {
        $figureCaptions = $this->getCaptionsForType($elements, 'figure');
        $tableCaptions = $this->getCaptionsForType($elements, 'table');

        $figureIssues = $this->analyzeNumbering($figureCaptions, 'Figure');
        $tableIssues = $this->analyzeNumbering($tableCaptions, 'Tableau');

        return [
            'figures' => $figureIssues,
            'tables' => $tableIssues,
            'summary' => [
                'total_figures' => count($this->getElementsOfType($elements, 'figure')),
                'total_tables' => count($this->getElementsOfType($elements, 'table')),
                'captioned_figures' => count($figureCaptions),
                'captioned_tables' => count($tableCaptions),
                'figure_issues' => count($figureIssues),
                'table_issues' => count($tableIssues),
            ],
        ];
    }

    /**
     * Generate a renumbering preview without applying changes.
     *
     * @param  DetectedElement[]  $elements
     * @return array{figures: array, tables: array}
     */
    public function previewRenumbering(array $elements): array
    {
        $figures = $this->getElementsOfType($elements, 'figure');
        $tables = $this->getElementsOfType($elements, 'table');

        return [
            'figures' => $this->generateNewNumbers($figures, 'figure'),
            'tables' => $this->generateNewNumbers($tables, 'table'),
        ];
    }

    /**
     * Apply renumbering to caption elements.
     *
     * @param  DetectedElement[]  $elements
     * @return array{updated: int, captions: array}
     */
    public function applyRenumbering(array $elements): array
    {
        $preview = $this->previewRenumbering($elements);
        $updated = 0;
        $captions = [];

        foreach ($preview['figures'] as $item) {
            if ($item['changed']) {
                $caption = $this->findCaptionForElement($elements, $item['element_index'], 'figure');
                if ($caption) {
                    $newContent = $this->updateCaptionNumber($caption->content, $item['new_number']);
                    $caption->update([
                        'content' => $newContent,
                        'metadata' => array_merge($caption->metadata ?? [], [
                            'number' => $item['new_number'],
                        ]),
                    ]);
                    $updated++;
                    $captions[] = $caption;
                }
            }
        }

        foreach ($preview['tables'] as $item) {
            if ($item['changed']) {
                $caption = $this->findCaptionForElement($elements, $item['element_index'], 'table');
                if ($caption) {
                    $newContent = $this->updateCaptionNumber($caption->content, $item['new_number']);
                    $caption->update([
                        'content' => $newContent,
                        'metadata' => array_merge($caption->metadata ?? [], [
                            'number' => $item['new_number'],
                        ]),
                    ]);
                    $updated++;
                    $captions[] = $caption;
                }
            }
        }

        return [
            'updated' => $updated,
            'captions' => $captions,
        ];
    }

    /**
     * @param  DetectedElement[]  $elements
     * @return DetectedElement[]
     */
    private function getCaptionsForType(array $elements, string $type): array
    {
        return array_values(array_filter($elements, function (DetectedElement $el) use ($type) {
            return $el->type === 'caption'
                && isset($el->metadata['element_type'])
                && $el->metadata['element_type'] === $type;
        }));
    }

    /**
     * @param  DetectedElement[]  $elements
     * @return DetectedElement[]
     */
    private function getElementsOfType(array $elements, string $type): array
    {
        return array_values(array_filter($elements, fn (DetectedElement $el) => $el->type === $type));
    }

    /**
     * @param  DetectedElement[]  $captions
     * @return array<int, array{element_index: int, current_number: int, expected_number: int, issue: string}>
     */
    private function analyzeNumbering(array $captions, string $label): array
    {
        $issues = [];
        $numbers = [];

        foreach ($captions as $caption) {
            $number = $caption->metadata['number'] ?? null;
            $elementIndex = $caption->metadata['element_index'] ?? null;

            if ($number === null) {
                $issues[] = [
                    'element_index' => $elementIndex,
                    'current_number' => null,
                    'expected_number' => null,
                    'issue' => "Missing number in {$label} caption",
                ];

                continue;
            }

            $numbers[] = ['number' => $number, 'element_index' => $elementIndex];
        }

        usort($numbers, fn ($a, $b) => $a['number'] <=> $b['number']);

        $seen = [];
        $expected = 1;
        foreach ($numbers as $item) {
            $num = $item['number'];

            if (in_array($num, $seen)) {
                $issues[] = [
                    'element_index' => $item['element_index'],
                    'current_number' => $num,
                    'expected_number' => $expected,
                    'issue' => "{$label} {$num} is duplicated",
                ];
            } elseif ($num !== $expected) {
                $issues[] = [
                    'element_index' => $item['element_index'],
                    'current_number' => $num,
                    'expected_number' => $expected,
                    'issue' => "{$label} {$expected} is missing (found {$label} {$num})",
                ];
            }

            $seen[] = $num;
            $expected = max($expected, $num + 1);
        }

        return $issues;
    }

    /**
     * @param  DetectedElement[]  $elements
     * @return array<int, array{element_index: int, old_number: int|null, new_number: int, changed: bool}>
     */
    private function generateNewNumbers(array $elements, string $type): array
    {
        $result = [];
        $counter = 1;

        foreach ($elements as $element) {
            $caption = $this->findCaptionForType($element->element_index, $type);
            $oldNumber = $caption?->metadata['number'] ?? null;

            $result[] = [
                'element_index' => $element->element_index,
                'old_number' => $oldNumber,
                'new_number' => $counter,
                'changed' => $oldNumber !== $counter,
            ];

            $counter++;
        }

        return $result;
    }

    private function findCaptionForType(int $elementIndex, string $type): ?DetectedElement
    {
        return DetectedElement::where('type', 'caption')
            ->where('metadata->element_index', $elementIndex)
            ->where('metadata->element_type', $type)
            ->first();
    }

    private function findCaptionForElement(array $elements, int $elementIndex, string $type): ?DetectedElement
    {
        return $this->findCaptionForType($elementIndex, $type);
    }

    private function findCaptionForElementFromAll(array $elements, int $elementIndex, string $type): ?DetectedElement
    {
        foreach ($elements as $element) {
            if ($element->type === 'caption'
                && ($element->metadata['element_index'] ?? null) === $elementIndex
                && ($element->metadata['element_type'] ?? null) === $type
            ) {
                return $element;
            }
        }

        return null;
    }

    private function updateCaptionNumber(string $content, int $newNumber): string
    {
        return preg_replace('/(\d+)/', (string) $newNumber, $content, 1);
    }
}
