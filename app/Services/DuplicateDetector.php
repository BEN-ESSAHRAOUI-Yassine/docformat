<?php

namespace App\Services;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Models\BibliographyEntry;
use Illuminate\Support\Collection;

class DuplicateDetector
{
    private const THRESHOLDS = [
        'exact' => 0.9,
        'fuzzy' => 0.7,
        'doi' => 0.99,
    ];

    public function __construct(
        private ?ActionLogger $actionLogger = null,
    ) {}

    /**
     * Detect duplicate bibliography entries.
     *
     * @param  Collection<int, BibliographyEntry>  $entries
     * @return array<int, array{group_id: string, entries: array<int, int>, confidence: float, type: string}>
     */
    public function detect(Collection $entries): array
    {
        $groups = [];
        $processed = [];

        $entriesArray = $entries->all();

        for ($i = 0; $i < count($entriesArray); $i++) {
            if (in_array($entriesArray[$i]->id, $processed)) {
                continue;
            }

            $group = [
                'group_id' => 'dup_'.($i + 1),
                'entries' => [$entriesArray[$i]->id],
                'confidence' => 0,
                'type' => '',
            ];

            for ($j = $i + 1; $j < count($entriesArray); $j++) {
                if (in_array($entriesArray[$j]->id, $processed)) {
                    continue;
                }

                $result = $this->compare($entriesArray[$i], $entriesArray[$j]);

                if ($result['is_duplicate']) {
                    $group['entries'][] = $entriesArray[$j]->id;
                    $group['confidence'] = max($group['confidence'], $result['confidence']);
                    $group['type'] = $result['type'];
                    $processed[] = $entriesArray[$j]->id;
                }
            }

            if (count($group['entries']) > 1) {
                $groups[] = $group;
                $processed[] = $entriesArray[$i]->id;
            }
        }

        return $groups;
    }

    /**
     * Compare two bibliography entries for similarity.
     */
    public function compare(BibliographyEntry $a, BibliographyEntry $b): array
    {
        // DOI match
        if ($a->doi && $b->doi && $a->doi === $b->doi) {
            return [
                'is_duplicate' => true,
                'confidence' => self::THRESHOLDS['doi'],
                'type' => 'doi',
            ];
        }

        $scoreA = $this->calculateSimilarity($a, $b);
        $scoreB = $this->calculateSimilarity($b, $a);
        $score = max($scoreA, $scoreB);

        if ($score >= self::THRESHOLDS['exact']) {
            return [
                'is_duplicate' => true,
                'confidence' => $score,
                'type' => 'exact',
            ];
        }

        if ($score >= self::THRESHOLDS['fuzzy']) {
            return [
                'is_duplicate' => true,
                'confidence' => $score,
                'type' => 'fuzzy',
            ];
        }

        return [
            'is_duplicate' => false,
            'confidence' => $score,
            'type' => '',
        ];
    }

    /**
     * Generate merge preview for two entries.
     */
    public function mergePreview(BibliographyEntry $keep, BibliographyEntry $merge): array
    {
        $fields = [
            'authors' => [$keep->authors, $merge->authors],
            'title' => [$keep->title, $merge->title],
            'year' => [$keep->year, $merge->year],
            'journal' => [$keep->journal, $merge->journal],
            'publisher' => [$keep->publisher, $merge->publisher],
            'volume' => [$keep->volume, $merge->volume],
            'issue' => [$keep->issue, $merge->issue],
            'pages' => [$keep->pages, $merge->pages],
            'doi' => [$keep->doi, $merge->doi],
            'url' => [$keep->url, $merge->url],
        ];

        $preview = [];
        foreach ($fields as $field => [$keepVal, $mergeVal]) {
            $preview[$field] = [
                'keep' => $keepVal,
                'merge' => $mergeVal,
                'recommended' => $keepVal ?? $mergeVal,
            ];
        }

        return $preview;
    }

    /**
     * Merge two entries, keeping the preferred values.
     */
    public function merge(BibliographyEntry $keep, BibliographyEntry $merge, array $fieldChoices = []): BibliographyEntry
    {
        $data = [];

        $fields = ['authors', 'title', 'year', 'journal', 'publisher', 'volume', 'issue', 'pages', 'doi', 'url'];

        foreach ($fields as $field) {
            $keepVal = $keep->{$field};
            $mergeVal = $merge->{$field};

            if (isset($fieldChoices[$field])) {
                $data[$field] = $fieldChoices[$field] === 'keep' ? $keepVal : $mergeVal;
            } else {
                $data[$field] = $keepVal ?? $mergeVal;
            }
        }

        // Merge extra_fields
        $keepExtra = $keep->extra_fields ?? [];
        $mergeExtra = $merge->extra_fields ?? [];
        $data['extra_fields'] = array_merge($mergeExtra, $keepExtra);

        $deletedEntrySnapshot = $merge->toArray();

        $keep->update($data);

        // Delete the merged entry
        $merge->delete();

        if ($this->actionLogger) {
            $this->actionLogger->record($keep->fresh()->document, [
                'action_type' => ActionType::Merged,
                'element_type' => 'BibliographyEntry',
                'element_id' => $keep->id,
                'origin' => ActionOrigin::Manual,
                'old_value' => $keep->getOriginal('title'),
                'new_value' => $data['title'] ?? null,
                'payload' => [
                    'deleted_entry' => $deletedEntrySnapshot,
                    'field_choices' => $fieldChoices,
                ],
                'reversibility' => Reversibility::Full,
            ]);
        }

        return $keep->fresh();
    }

    private function calculateSimilarity(BibliographyEntry $a, BibliographyEntry $b): float
    {
        $score = 0;
        $weights = [
            'author' => 0.3,
            'title' => 0.4,
            'year' => 0.2,
            'journal' => 0.1,
        ];

        // Author similarity
        if ($a->authors && $b->authors) {
            $authorA = $this->normalize(implode(' ', $a->authors));
            $authorB = $this->normalize(implode(' ', $b->authors));
            $score += $this->stringSimilarity($authorA, $authorB) * $weights['author'];
        }

        // Title similarity
        if ($a->title && $b->title) {
            $titleA = $this->normalize($a->title);
            $titleB = $this->normalize($b->title);
            $score += $this->stringSimilarity($titleA, $titleB) * $weights['title'];
        }

        // Year match
        if ($a->year && $b->year && $a->year === $b->year) {
            $score += $weights['year'];
        }

        // Journal similarity
        if ($a->journal && $b->journal) {
            $journalA = $this->normalize($a->journal);
            $journalB = $this->normalize($b->journal);
            $score += $this->stringSimilarity($journalA, $journalB) * $weights['journal'];
        }

        return round(min($score, 1.0), 2);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function stringSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if (strlen($a) === 0 || strlen($b) === 0) {
            return 0.0;
        }

        // Use similar_text for PHP native similarity
        $similarity = 0;
        similar_text($a, $b, $percent);

        return round($percent / 100, 2);
    }
}
