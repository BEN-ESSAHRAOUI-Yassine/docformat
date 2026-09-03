<?php

namespace App\Services;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Models\Citation;
use App\Models\Document;
use Illuminate\Support\Collection;

class CitationValidator
{
    public function __construct(
        private ?ActionLogger $actionLogger = null,
    ) {}

    /**
     * Validate citations against bibliography entries for a document.
     */
    public function validate(Document $document): array
    {
        $citations = Citation::where('document_id', $document->id)->get();
        $entries = $document->bibliographyEntries()->get();

        $issues = [
            'errors' => [],
            'warnings' => [],
            'info' => [],
        ];

        // Check for citations without bibliography entries (orphaned)
        $this->checkOrphanedCitations($citations, $entries, $issues);

        // Check for bibliography entries never cited (uncited)
        $this->checkUncitedEntries($citations, $entries, $issues);

        // Check for author/year mismatches
        $this->checkMismatches($citations, $entries, $issues);

        // Check for ambiguous citations
        $this->checkAmbiguous($citations, $entries, $issues);

        return $issues;
    }

    /**
     * Get reference issues for a document.
     */
    public function getIssues(Document $document): array
    {
        $validation = $this->validate($document);

        return [
            'document_id' => $document->id,
            'summary' => [
                'total_citations' => Citation::where('document_id', $document->id)->count(),
                'total_entries' => $document->bibliographyEntries()->count(),
                'orphaned_citations' => count($validation['warnings']['orphaned'] ?? []),
                'uncited_entries' => count($validation['warnings']['uncited'] ?? []),
                'mismatches' => count($validation['warnings']['mismatch'] ?? []),
                'ambiguous' => count($validation['warnings']['ambiguous'] ?? []),
            ],
            'issues' => $validation,
        ];
    }

    private function checkOrphanedCitations(Collection $citations, Collection $entries, array &$issues): void
    {
        foreach ($citations as $citation) {
            if ($citation->bibliography_entry_id === null) {
                // Try to match by author/year
                $match = $this->findMatchingEntry($citation, $entries);

                if ($match) {
                    $oldEntryId = $citation->bibliography_entry_id;
                    $citation->update(['bibliography_entry_id' => $match->id]);

                    $this->actionLogger?->record($document, [
                        'action_type' => ActionType::CitationLinked,
                        'element_type' => 'Citation',
                        'element_id' => $citation->id,
                        'origin' => ActionOrigin::Automatic,
                        'old_value' => [
                            'model' => 'Citation',
                            'id' => $citation->id,
                            'attributes' => ['bibliography_entry_id' => $oldEntryId],
                        ],
                        'new_value' => [
                            'model' => 'Citation',
                            'id' => $citation->id,
                            'attributes' => ['bibliography_entry_id' => $match->id],
                        ],
                        'reversibility' => Reversibility::Full,
                    ]);

                    $issues['info'][] = [
                        'type' => 'auto_linked',
                        'citation_id' => $citation->id,
                        'entry_id' => $match->id,
                        'message' => "Citation \"{$citation->raw_text}\" automatically linked to entry.",
                    ];
                } else {
                    $issues['warnings'][] = [
                        'type' => 'orphaned',
                        'citation_id' => $citation->id,
                        'message' => "Citation \"{$citation->raw_text}\" has no matching bibliography entry.",
                    ];
                }
            }
        }
    }

    private function checkUncitedEntries(Collection $citations, Collection $entries, array &$issues): void
    {
        $citedEntryIds = $citations->pluck('bibliography_entry_id')->filter()->unique();

        foreach ($entries as $entry) {
            if (! $citedEntryIds->contains($entry->id)) {
                $issues['warnings'][] = [
                    'type' => 'uncited',
                    'entry_id' => $entry->id,
                    'message' => "Bibliography entry \"{$entry->title}\" is never cited.",
                ];
            }
        }
    }

    private function checkMismatches(Collection $citations, Collection $entries, array &$issues): void
    {
        foreach ($citations->whereNotNull('bibliography_entry_id') as $citation) {
            $entry = $entries->firstWhere('id', $citation->bibliography_entry_id);

            if (! $entry) {
                continue;
            }

            // Check author match
            if ($citation->author && $entry->authors) {
                $entryAuthors = array_map('strtolower', $entry->authors);
                $citationAuthor = strtolower($citation->author);

                $authorMatch = false;
                foreach ($entryAuthors as $entryAuthor) {
                    if (str_contains($entryAuthor, $citationAuthor) || str_contains($citationAuthor, $entryAuthor)) {
                        $authorMatch = true;
                        break;
                    }
                }

                if (! $authorMatch) {
                    $issues['warnings'][] = [
                        'type' => 'mismatch',
                        'citation_id' => $citation->id,
                        'entry_id' => $entry->id,
                        'field' => 'author',
                        'message' => "Citation author \"{$citation->author}\" does not match entry authors.",
                    ];
                }
            }

            // Check year match
            if ($citation->year && $entry->year && $citation->year !== $entry->year) {
                $issues['warnings'][] = [
                    'type' => 'mismatch',
                    'citation_id' => $citation->id,
                    'entry_id' => $entry->id,
                    'field' => 'year',
                    'message' => "Citation year \"{$citation->year}\" does not match entry year \"{$entry->year}\".",
                ];
            }
        }
    }

    private function checkAmbiguous(Collection $citations, Collection $entries, array &$issues): void
    {
        foreach ($citations->whereNull('bibliography_entry_id') as $citation) {
            $matches = $this->findMultipleMatchingEntries($citation, $entries);

            if (count($matches) > 1) {
                $issues['warnings'][] = [
                    'type' => 'ambiguous',
                    'citation_id' => $citation->id,
                    'matching_entry_ids' => $matches->pluck('id')->toArray(),
                    'message' => "Citation \"{$citation->raw_text}\" matches multiple bibliography entries.",
                ];
            }
        }
    }

    private function findMatchingEntry(Citation $citation, Collection $entries): ?object
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($entries as $entry) {
            $score = $this->calculateMatchScore($citation, $entry);

            if ($score > $bestScore && $score >= 0.5) {
                $bestScore = $score;
                $bestMatch = $entry;
            }
        }

        return $bestMatch;
    }

    private function findMultipleMatchingEntries(Citation $citation, Collection $entries): Collection
    {
        return $entries->filter(function ($entry) use ($citation) {
            return $this->calculateMatchScore($citation, $entry) >= 0.5;
        });
    }

    private function calculateMatchScore(Citation $citation, object $entry): float
    {
        $score = 0;

        // Author match
        if ($citation->author && $entry->authors) {
            $entryAuthors = array_map('strtolower', $entry->authors);
            $citationAuthor = strtolower($citation->author);

            foreach ($entryAuthors as $entryAuthor) {
                if (str_contains($entryAuthor, $citationAuthor) || str_contains($citationAuthor, $entryAuthor)) {
                    $score += 0.5;
                    break;
                }
            }
        }

        // Year match
        if ($citation->year && $entry->year && $citation->year === $entry->year) {
            $score += 0.4;
        }

        // Numeric citation match
        if ($citation->type === 'numeric' && $citation->numbers) {
            $score += 0.1; // Base score for numeric
        }

        return min($score, 1.0);
    }
}
