<?php

namespace App\Services;

use App\Models\DetectedElement;

class ListGeneratorService
{
    private const TITLES = [
        'fr' => [
            'figures' => 'LISTE DES FIGURES',
            'tables' => 'LISTE DES TABLEAUX',
        ],
        'en' => [
            'figures' => 'LIST OF FIGURES',
            'tables' => 'LIST OF TABLES',
        ],
    ];

    /**
     * Generate List of Figures from detected elements.
     *
     * @param  DetectedElement[]  $elements
     * @param  string  $language  'fr' or 'en'
     * @return array{title: string, entries: array<int, array{number: int, caption: string, page: int|null}>, total: int}
     */
    public function generateListOfFigures(array $elements, string $language = 'fr'): array
    {
        return $this->generateList($elements, 'figure', $language);
    }

    /**
     * Generate List of Tables from detected elements.
     *
     * @param  DetectedElement[]  $elements
     * @param  string  $language  'fr' or 'en'
     * @return array{title: string, entries: array<int, array{number: int, caption: string, page: int|null}>, total: int}
     */
    public function generateListOfTables(array $elements, string $language = 'fr'): array
    {
        return $this->generateList($elements, 'table', $language);
    }

    /**
     * Generate both LoF and LoT.
     *
     * @param  DetectedElement[]  $elements
     * @param  string  $language  'fr' or 'en'
     * @return array{figures: array, tables: array}
     */
    public function generateAllLists(array $elements, string $language = 'fr'): array
    {
        return [
            'figures' => $this->generateListOfFigures($elements, $language),
            'tables' => $this->generateListOfTables($elements, $language),
        ];
    }

    /**
     * Format a list as plain text lines.
     *
     * @param  array{title: string, entries: array, total: int}  $list
     * @return array<int, string>
     */
    public function formatAsText(array $list): array
    {
        $lines = [$list['title'], str_repeat('-', strlen($list['title']))];

        foreach ($list['entries'] as $entry) {
            $label = $this->getLabelForEntry($entry);
            $page = $entry['page'] !== null ? " ....... {$entry['page']}" : '';
            $lines[] = "{$label} {$entry['caption']}{$page}";
        }

        $lines[] = '';
        $lines[] = "Total : {$list['total']}";

        return $lines;
    }

    /**
     * Detect changes needed when elements are renumbered.
     *
     * @param  DetectedElement[]  $elements
     * @param  string  $language  'fr' or 'en'
     * @return array{figures: array, tables: array}
     */
    public function detectChangesNeeded(array $elements, string $language = 'fr'): array
    {
        $currentLists = $this->generateAllLists($elements, $language);

        $figureCaptions = $this->getCaptionsForType($elements, 'figure');
        $tableCaptions = $this->getCaptionsForType($elements, 'table');

        $figureChanges = $this->compareWithCaptions($currentLists['figures'], $figureCaptions);
        $tableChanges = $this->compareWithCaptions($currentLists['tables'], $tableCaptions);

        return [
            'figures' => $figureChanges,
            'tables' => $tableChanges,
        ];
    }

    /**
     * @param  DetectedElement[]  $elements
     * @param  string  $type  'figure' or 'table'
     * @param  string  $language  'fr' or 'en'
     * @return array{title: string, entries: array<int, array{number: int, caption: string, page: int|null}>, total: int}
     */
    private function generateList(array $elements, string $type, string $language): array
    {
        $title = self::TITLES[$language][$type === 'figure' ? 'figures' : 'tables'];
        $captions = $this->getCaptionsForType($elements, $type);

        usort($captions, fn (DetectedElement $a, DetectedElement $b) => ($a->metadata['number'] ?? 0) <=> ($b->metadata['number'] ?? 0)
        );

        $entries = [];
        foreach ($captions as $caption) {
            $entries[] = [
                'number' => $caption->metadata['number'] ?? 0,
                'caption' => $this->extractCaptionText($caption->content),
                'page' => $caption->metadata['page'] ?? null,
            ];
        }

        return [
            'title' => $title,
            'entries' => $entries,
            'total' => count($entries),
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
                && ($el->metadata['element_type'] ?? null) === $type;
        }));
    }

    private function extractCaptionText(string $content): string
    {
        $text = preg_replace('/^(?:Figure|Fig\.?|Tableau|Tab\.?|Table)\s+\d+\s*[:\.]\s*/i', '', $content);

        return trim($text);
    }

    private function getLabelForEntry(array $entry): string
    {
        return "{$entry['number']}.";
    }

    /**
     * @param  array{title: string, entries: array, total: int}  $list
     * @param  DetectedElement[]  $captions
     * @return array{changes_needed: bool, missing_entries: array, outdated_entries: array}
     */
    private function compareWithCaptions(array $list, array $captions): array
    {
        $listNumbers = array_column($list['entries'], 'number');
        $captionNumbers = array_map(fn ($c) => $c->metadata['number'] ?? null, $captions);
        $captionNumbers = array_filter($captionNumbers);

        $missing = array_diff($captionNumbers, $listNumbers);
        $outdated = array_diff($listNumbers, $captionNumbers);

        return [
            'changes_needed' => $missing !== [] || $outdated !== [],
            'missing_entries' => array_values($missing),
            'outdated_entries' => array_values($outdated),
        ];
    }
}
