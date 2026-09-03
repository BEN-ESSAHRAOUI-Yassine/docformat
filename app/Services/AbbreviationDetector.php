<?php

namespace App\Services;

class AbbreviationDetector
{
    private const DEFINITION_PATTERN = '/(.+?)\s*\(([A-Z]{2,})\)/';

    private const ABBREVIATION_PATTERN = '/\b([A-Z]{2,})\b/';

    /**
     * Detect abbreviations from paragraphs.
     *
     * @param  array<int, array{index: int, text: string}>  $paragraphs
     * @return array<int, array{abbreviation: string, full_form: string, definition_element_index: int, usage_count: int, occurrences: array<int, int>, is_consistent: bool, inconsistent_forms: array}>
     */
    public function detect(array $paragraphs): array
    {
        $registry = [];

        // First pass: find definitions
        foreach ($paragraphs as $paragraph) {
            $text = $paragraph['text'];
            $elementIndex = $paragraph['index'];

            if (preg_match_all(self::DEFINITION_PATTERN, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $fullForm = trim($match[1]);
                    $abbreviation = $match[2];

                    if (! isset($registry[$abbreviation])) {
                        $registry[$abbreviation] = [
                            'abbreviation' => $abbreviation,
                            'full_form' => $fullForm,
                            'definition_element_index' => $elementIndex,
                            'usage_count' => 0,
                            'occurrences' => [],
                            'is_consistent' => true,
                            'inconsistent_forms' => [],
                        ];
                    } else {
                        // Check for inconsistent definitions
                        if ($registry[$abbreviation]['full_form'] !== $fullForm) {
                            $registry[$abbreviation]['is_consistent'] = false;
                            $registry[$abbreviation]['inconsistent_forms'][] = $fullForm;
                        }
                    }
                }
            }
        }

        // Second pass: count usages
        foreach ($paragraphs as $paragraph) {
            $text = $paragraph['text'];
            $elementIndex = $paragraph['index'];

            if (preg_match_all(self::ABBREVIATION_PATTERN, $text, $matches)) {
                foreach ($matches[1] as $abbreviation) {
                    if (isset($registry[$abbreviation])) {
                        $registry[$abbreviation]['usage_count']++;
                        $registry[$abbreviation]['occurrences'][] = $elementIndex;
                    }
                }
            }
        }

        return array_values($registry);
    }

    /**
     * Get abbreviation issues for a document.
     *
     * @param  array<int, array{abbreviation: string, full_form: string, definition_element_index: int, usage_count: int, occurrences: array<int, int>, is_consistent: bool, inconsistent_forms: array}>  $abbreviations
     * @return array{undefined: array<int, array{abbreviation: string, element_index: int}>, inconsistent: array<int, array{abbreviation: string, forms: array<string>}>, unused: array<int, array{abbreviation: string, definition_element_index: int}>}
     */
    public function getIssues(array $abbreviations): array
    {
        $issues = [
            'undefined' => [],
            'inconsistent' => [],
            'unused' => [],
        ];

        foreach ($abbreviations as $abbr) {
            if (! $abbr['is_consistent']) {
                $issues['inconsistent'][] = [
                    'abbreviation' => $abbr['abbreviation'],
                    'forms' => array_unique(array_merge([$abbr['full_form']], $abbr['inconsistent_forms'])),
                ];
            }

            if ($abbr['usage_count'] === 0) {
                $issues['unused'][] = [
                    'abbreviation' => $abbr['abbreviation'],
                    'definition_element_index' => $abbr['definition_element_index'],
                ];
            }
        }

        return $issues;
    }
}
