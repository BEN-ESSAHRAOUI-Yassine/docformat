<?php

namespace App\Services;

use App\Models\BibliographyEntry;

class BibliographyFormatter
{
    private const STYLES = [
        'apa' => 'APA',
        'ieee' => 'IEEE',
        'vancouver' => 'Vancouver',
        'mla' => 'MLA',
        'chicago' => 'Chicago',
        'custom' => 'Custom',
    ];

    /**
     * Format a bibliography entry in the specified style.
     */
    public function format(BibliographyEntry $entry, string $style = 'apa'): string
    {
        $style = strtolower($style);

        return match ($this->normalizeStyle($style)) {
            'apa' => $this->formatAPA($entry),
            'ieee' => $this->formatIEEE($entry),
            'vancouver' => $this->formatVancouver($entry),
            'mla' => $this->formatMLA($entry),
            'chicago' => $this->formatChicago($entry),
            default => $this->formatAPA($entry),
        };
    }

    /**
     * Get available formatting styles.
     *
     * @return array<int, string>
     */
    public function getAvailableStyles(): array
    {
        return array_values(self::STYLES);
    }

    private function normalizeStyle(string $style): string
    {
        return self::STYLES[$style] ? strtolower(self::STYLES[$style]) : $style;
    }

    private function formatAPA(BibliographyEntry $entry): string
    {
        $authors = $this->formatAuthorsAPA($entry->authors ?? []);
        $year = $entry->year ? "({$entry->year})" : '(n.d.)';
        $title = $entry->title ?? '';
        $journal = $entry->journal ?? '';
        $volume = $entry->volume ?? '';
        $issue = $entry->issue ?? '';
        $pages = $entry->pages ?? '';
        $doi = $entry->doi ?? '';

        $parts = [];
        $parts[] = "{$authors} {$year}.";
        $parts[] = "{$title}.";

        if ($journal) {
            $journalPart = "*{$journal}*";
            if ($volume) {
                $journalPart .= ", *{$volume}*";
            }
            if ($issue) {
                $journalPart .= "({$issue})";
            }
            if ($pages) {
                $journalPart .= ", {$pages}";
            }
            $parts[] = $journalPart.'.';
        }

        if ($doi) {
            $parts[] = "https://doi.org/{$doi}";
        }

        return implode(' ', array_filter($parts));
    }

    private function formatIEEE(BibliographyEntry $entry): string
    {
        $authors = $this->formatAuthorsIEEE($entry->authors ?? []);
        $title = $entry->title ?? '';
        $journal = $entry->journal ?? '';
        $volume = $entry->volume ?? '';
        $issue = $entry->issue ?? '';
        $pages = $entry->pages ?? '';
        $year = $entry->year ?? '';

        $parts = [];
        $parts[] = "{$authors},";
        $parts[] = "\"{$title},\"";

        if ($journal) {
            $journalPart = "*{$journal}*";
            if ($volume) {
                $journalPart .= ", vol. {$volume}";
            }
            if ($issue) {
                $journalPart .= ", no. {$issue}";
            }
            if ($pages) {
                $journalPart .= ", pp. {$pages}";
            }
            $parts[] = $journalPart.',';
        }

        if ($year) {
            $parts[] = "{$year}.";
        }

        return implode(' ', array_filter($parts));
    }

    private function formatVancouver(BibliographyEntry $entry): string
    {
        $authors = $this->formatAuthorsVancouver($entry->authors ?? []);
        $title = $entry->title ?? '';
        $journal = $entry->journal ?? '';
        $year = $entry->year ?? '';
        $volume = $entry->volume ?? '';
        $issue = $entry->issue ?? '';
        $pages = $entry->pages ?? '';

        $parts = [];
        $parts[] = "{$authors}.";
        $parts[] = "{$title}.";

        if ($journal) {
            $parts[] = "{$journal}.";
        }

        if ($year) {
            $parts[] = "{$year}";
        }

        if ($volume) {
            $volumePart = "{$volume}";
            if ($issue) {
                $volumePart .= "({$issue})";
            }
            $parts[] = $volumePart;
        }

        if ($pages) {
            $parts[] = "{$pages}.";
        }

        return implode(' ', array_filter($parts));
    }

    private function formatMLA(BibliographyEntry $entry): string
    {
        $authors = $this->formatAuthorsMLA($entry->authors ?? []);
        $title = $entry->title ?? '';
        $journal = $entry->journal ?? '';
        $volume = $entry->volume ?? '';
        $issue = $entry->issue ?? '';
        $year = $entry->year ?? '';
        $pages = $entry->pages ?? '';

        $parts = [];
        $parts[] = "{$authors}.";
        $parts[] = "\"{$title}.\"";

        if ($journal) {
            $parts[] = "*{$journal}*,";
            if ($volume) {
                $parts[] = "vol. {$volume},";
            }
            if ($issue) {
                $parts[] = "no. {$issue},";
            }
            if ($year) {
                $parts[] = "{$year},";
            }
            if ($pages) {
                $parts[] = "pp. {$pages}.";
            }
        } elseif ($year) {
            $parts[] = "{$year}.";
        }

        return implode(' ', array_filter($parts));
    }

    private function formatChicago(BibliographyEntry $entry): string
    {
        $authors = $this->formatAuthorsChicago($entry->authors ?? []);
        $title = $entry->title ?? '';
        $journal = $entry->journal ?? '';
        $volume = $entry->volume ?? '';
        $issue = $entry->issue ?? '';
        $year = $entry->year ?? '';
        $pages = $entry->pages ?? '';

        $parts = [];
        $parts[] = "{$authors}.";
        $parts[] = "\"{$title}.\"";

        if ($journal) {
            $parts[] = "*{$journal}*";
            if ($volume) {
                $parts[] = "{$volume}";
            }
            if ($issue) {
                $parts[] = "no. {$issue}";
            }
            if ($year) {
                $parts[] = "({$year})";
            }
            if ($pages) {
                $parts[] = ": {$pages}";
            }
            $parts[] = '.';
        } elseif ($year) {
            $parts[] = "({$year}).";
        }

        return implode(' ', array_filter($parts));
    }

    private function formatAuthorsAPA(array $authors): string
    {
        if (empty($authors)) {
            return '(Unknown Author)';
        }

        $formatted = [];
        foreach ($authors as $author) {
            $parts = array_map('trim', explode(',', $author));
            if (count($parts) >= 2) {
                $last = $parts[0];
                $initials = trim($parts[1]);
                $formatted[] = "{$last}, ".strtoupper(substr($initials, 0, 1)).'.';
            } else {
                $formatted[] = $author;
            }
        }

        if (count($formatted) === 1) {
            return $formatted[0];
        }

        if (count($formatted) === 2) {
            return "{$formatted[0]} & {$formatted[1]}";
        }

        return implode(', ', array_slice($formatted, 0, -1)).', & '.end($formatted);
    }

    private function formatAuthorsIEEE(array $authors): string
    {
        if (empty($authors)) {
            return 'Unknown';
        }

        $formatted = [];
        foreach ($authors as $author) {
            $parts = array_map('trim', explode(',', $author));
            if (count($parts) >= 2) {
                $initials = array_map('trim', explode(' ', $parts[1]));
                $initialsStr = implode('. ', array_map(fn ($i) => strtoupper(substr($i, 0, 1)).'.', $initials));
                $formatted[] = "{$initialsStr} {$parts[0]}";
            } else {
                $formatted[] = $author;
            }
        }

        return implode(', ', $formatted);
    }

    private function formatAuthorsVancouver(array $authors): string
    {
        if (empty($authors)) {
            return 'Unknown';
        }

        $formatted = [];
        foreach ($authors as $author) {
            $parts = array_map('trim', explode(',', $author));
            if (count($parts) >= 2) {
                $initials = array_map('trim', explode(' ', $parts[1]));
                $initialsStr = implode('', array_map(fn ($i) => strtoupper(substr($i, 0, 1)), $initials));
                $formatted[] = "{$parts[0]} {$initialsStr}";
            } else {
                $formatted[] = $author;
            }
        }

        if (count($formatted) > 6) {
            return implode(', ', array_slice($formatted, 0, 3)).', et al';
        }

        return implode(', ', $formatted);
    }

    private function formatAuthorsMLA(array $authors): string
    {
        if (empty($authors)) {
            return 'Unknown Author';
        }

        if (count($authors) === 1) {
            return $authors[0];
        }

        if (count($authors) === 2) {
            return "{$authors[0]}, and {$authors[1]}";
        }

        return "{$authors[0]}, et al.";
    }

    private function formatAuthorsChicago(array $authors): string
    {
        if (empty($authors)) {
            return 'Unknown Author';
        }

        $formatted = [];
        foreach ($authors as $author) {
            $parts = array_map('trim', explode(',', $author));
            if (count($parts) >= 2) {
                $formatted[] = "{$parts[0]}, {$parts[1]}";
            } else {
                $formatted[] = $author;
            }
        }

        if (count($formatted) === 1) {
            return $formatted[0];
        }

        if (count($formatted) === 2) {
            return "{$formatted[0]}, and {$formatted[1]}";
        }

        return implode(', ', array_slice($formatted, 0, -1)).', and '.end($formatted);
    }
}
