<?php

namespace App\Services;

class BibliographyDetector
{
    // DOI pattern
    private const DOI_PATTERN = '/(?:doi:\s*|https?:\/\/doi\.org\/)(10\.\d{4,}\/[^\s,;.\)]+)/i';

    // URL pattern
    private const URL_PATTERN = '/https?:\/\/[^\s,;)]+/';

    /**
     * Detect bibliography entries from paragraphs.
     *
     * @param  array<int, array{index: int, text: string}>  $paragraphs
     * @return array<int, array{entry_type: string, authors: array, title: string, year: string|null, journal: string|null, publisher: string|null, volume: string|null, issue: string|null, pages: string|null, doi: string|null, url: string|null, access_date: string|null, extra_fields: array, raw_text: string, element_index: int}>
     */
    public function detect(array $paragraphs): array
    {
        $entries = [];
        $bibliographyStarted = false;

        foreach ($paragraphs as $paragraph) {
            $text = trim($paragraph['text']);
            $elementIndex = $paragraph['index'];

            if ($this->isBibliographySection($text)) {
                $bibliographyStarted = true;

                continue;
            }

            if ($bibliographyStarted && $this->isEntryStart($text)) {
                $entries[] = $this->parseEntry($text, $elementIndex);
            }
        }

        return $entries;
    }

    private function isBibliographySection(string $text): bool
    {
        $lower = mb_strtolower($text);

        return in_array($lower, [
            'bibliography',
            'references',
            'works cited',
            'literature cited',
            'références',
            'bibliographie',
        ]) || (bool) preg_match('/^(bibliography|references|works?\s+cited|r[ée]f[ée]rences|bibliographie)\s*$/i', $text);
    }

    private function isEntryStart(string $text): bool
    {
        // Numbered: "1." or "[1]"
        if (preg_match('/^\[?\d+\]?[\.\)]\s/', $text)) {
            return true;
        }

        // IEEE: "[3] A. A. Author"
        if (preg_match('/^\[\d+\]\s+[A-ZÀ-Ÿ]\.\s/', $text)) {
            return true;
        }

        // Starts with author name pattern: "Last, F. M." or "Last, F."
        if (preg_match('/^[A-ZÀ-Ÿ][a-zà-ÿ]+,\s+[A-ZÀ-Ÿ]\./', $text)) {
            return true;
        }

        return false;
    }

    /**
     * @return array{entry_type: string, authors: array, title: string, year: string|null, journal: string|null, publisher: string|null, volume: string|null, issue: string|null, pages: string|null, doi: string|null, url: string|null, access_date: string|null, extra_fields: array, raw_text: string, element_index: int}
     */
    private function parseEntry(string $text, int $elementIndex): array
    {
        $entry = [
            'entry_type' => 'other',
            'authors' => [],
            'title' => '',
            'year' => null,
            'journal' => null,
            'publisher' => null,
            'volume' => null,
            'issue' => null,
            'pages' => null,
            'doi' => null,
            'url' => null,
            'access_date' => null,
            'extra_fields' => [],
            'raw_text' => $text,
            'element_index' => $elementIndex,
        ];

        // Extract DOI
        if (preg_match(self::DOI_PATTERN, $text, $doiMatch)) {
            $entry['doi'] = $doiMatch[1];
        }

        // Extract URL
        if (preg_match(self::URL_PATTERN, $text, $urlMatch)) {
            $entry['url'] = rtrim($urlMatch[0], '.');
        }

        // Extract year - look for (YYYY) pattern first
        if (preg_match('/\((\d{4})\)/', $text, $yearMatch)) {
            $entry['year'] = $yearMatch[1];
        } elseif (preg_match('/\b(\d{4})\b/', $text, $yearMatch)) {
            $entry['year'] = $yearMatch[1];
        }

        // Extract authors - everything before the year in parentheses
        $entry['authors'] = $this->extractAuthors($text);

        // Classify entry type
        $entry['entry_type'] = $this->classifyType($text, $entry);

        // Extract title
        $entry['title'] = $this->extractTitle($text);

        // Extract journal/volume/issue/pages
        $this->extractJournalFields($text, $entry);

        // Extract publisher for books
        if (in_array($entry['entry_type'], ['book', 'chapter', 'thesis'])) {
            $entry['publisher'] = $this->extractPublisher($text);
        }

        return $entry;
    }

    /**
     * @return array<int, string>
     */
    private function extractAuthors(string $text): array
    {
        // Remove leading number like "1." or "[1]" or "[3]"
        $cleaned = preg_replace('/^\[?\d+\]?[\.\)]\s/', '', $text);

        // Find everything before the year pattern "(YYYY)" or before quoted title
        if (preg_match('/^(.+?)\s*(?:\(\d{4}\)|")/', $cleaned, $match)) {
            $authorStr = trim(rtrim($match[1], ',.& '));

            if ($authorStr === '') {
                return [];
            }

            // Split by comma, & or "and"
            $parts = preg_split('/\s*(?:,\s*(?:&\s*|and\s+)?|\s+&\s+|\s+and\s+)\s*/', $authorStr);

            return array_filter(array_map('trim', $parts));
        }

        return [];
    }

    private function extractTitle(string $text): string
    {
        // Remove leading number
        $cleaned = preg_replace('/^\[?\d+\]?[\.\)]\s/', '', $text);

        // Pattern: after year "(YYYY)." title "."
        if (preg_match('/\(\d{4}\)\.?\s+"?(.+?)"?[.\n]/', $cleaned, $match)) {
            return trim($match[1], '"');
        }

        // Pattern: after year "(YYYY)." title until next period
        if (preg_match('/\(\d{4}\)\.?\s+(.+?)\./', $cleaned, $match)) {
            return trim($match[1]);
        }

        // Fallback: text between first and second period
        if (preg_match('/^[^.]+\.\s+(.+?)\./', $cleaned, $match)) {
            return trim($match[1]);
        }

        return mb_substr($cleaned, 0, 200);
    }

    private function classifyType(string $text, array $entry): string
    {
        if ($entry['doi'] !== null || preg_match('/\bvol(?:\.|ume)?\b/i', $text)) {
            return 'article';
        }

        if (preg_match('/\b(in|dans|chapter|chapitre|éd\.|ed\.)\b/i', $text)) {
            return 'chapter';
        }

        if (preg_match('/\bproceedings|conference|workshop|symposium\b/i', $text)) {
            return 'conference';
        }

        if (preg_match('/\bthesis|th[èe]se|dissertation\b/i', $text)) {
            return 'thesis';
        }

        if ($entry['url'] !== null || preg_match('/\baccessed|retrieved|consult[ée]\b/i', $text)) {
            return 'online';
        }

        if (preg_match('/\bpublisher|press|publishing|éditions?\b/i', $text)) {
            return 'book';
        }

        return 'other';
    }

    private function extractJournalFields(string $text, array &$entry): void
    {
        // Volume
        if (preg_match('/\bvol(?:\.|ume)\s*(\d+)/i', $text, $match)) {
            $entry['volume'] = $match[1];
        }

        // Issue
        if (preg_match('/\bn[°o]\.?\s*(\d+)/i', $text, $match)) {
            $entry['issue'] = $match[1];
        } elseif (preg_match('/\b\((\d+)\)/', $text, $match)) {
            $entry['issue'] = $match[1];
        }

        // Pages
        if (preg_match('/\bpp?\.\s*(\d+[\s–-]+\d+|\d+)/i', $text, $match)) {
            $entry['pages'] = $match[1];
        } elseif (preg_match('/\b(\d+[\s–-]\d+)\b/', $text, $match)) {
            $entry['pages'] = $match[1];
        }

        // Journal: text between year and volume or after title period
        if (preg_match('/\d{4}\)\.?\s+(.+?)(?:,\s*vol|\s*$)/', $text, $match)) {
            $entry['journal'] = trim($match[1], '. ');
        }
    }

    private function extractPublisher(string $text): ?string
    {
        if (preg_match('/(?:publisher|press|publishing|éditions?)\s*:?\s*(.+?)[,\n]/i', $text, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}
