<?php

namespace App\Services;

class CitationDetector
{
    // Author-year: (Smith, 2020), (Dupont et al., 2021)
    private const AUTHOR_YEAR_PATTERN = '/\(([A-ZÀ-Ÿ][a-zà-ÿ]+(?:\s+(?:et\s+al\.?|and\s+al\.?))?(?:,\s*[A-ZÀ-Ÿ][a-zà-ÿ]+)*),?\s+(\d{4})\)/';

    // Numeric: [1], [2, 3, 5]
    private const NUMERIC_PATTERN = '/\[(\d+(?:\s*,\s*\d+)*)\]/';

    // Bracketed author-year: [Smith 2020], [Dupont et al. 2021]
    private const BRACKETED_PATTERN = '/\[([A-ZÀ-Ÿ][a-zà-ÿ]+(?:\s+(?:et\s+al\.?|and\s+al\.?))?)\s+(\d{4})\]/';

    /**
     * Detect citations in an array of paragraphs.
     *
     * @param  array<int, array{index: int, text: string}>  $paragraphs
     * @return array<int, array{type: string, raw_text: string, author: string|null, year: string|null, numbers: array|null, element_index: int, confidence: float}>
     */
    public function detect(array $paragraphs): array
    {
        $citations = [];

        foreach ($paragraphs as $paragraph) {
            $text = $paragraph['text'];
            $elementIndex = $paragraph['index'];

            $citations = array_merge(
                $citations,
                $this->detectAuthorYear($text, $elementIndex),
                $this->detectNumeric($text, $elementIndex),
                $this->detectBracketed($text, $elementIndex),
            );
        }

        return $citations;
    }

    /**
     * @return array<int, array{type: string, raw_text: string, author: string|null, year: string|null, numbers: array|null, element_index: int, confidence: float}>
     */
    private function detectAuthorYear(string $text, int $elementIndex): array
    {
        $citations = [];

        if (preg_match_all(self::AUTHOR_YEAR_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $rawText = $match[0][0];
                $author = trim($match[1][0]);
                $year = $match[2][0];

                $citations[] = [
                    'type' => 'author_year',
                    'raw_text' => $rawText,
                    'author' => $author,
                    'year' => $year,
                    'numbers' => null,
                    'element_index' => $elementIndex,
                    'confidence' => 0.95,
                ];
            }
        }

        return $citations;
    }

    /**
     * @return array<int, array{type: string, raw_text: string, author: string|null, year: string|null, numbers: array|null, element_index: int, confidence: float}>
     */
    private function detectNumeric(string $text, int $elementIndex): array
    {
        $citations = [];

        if (preg_match_all(self::NUMERIC_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $rawText = $match[0][0];
                $numbersStr = $match[1][0];
                $numbers = array_map('intval', array_map('trim', explode(',', $numbersStr)));

                $citations[] = [
                    'type' => 'numeric',
                    'raw_text' => $rawText,
                    'author' => null,
                    'year' => null,
                    'numbers' => $numbers,
                    'element_index' => $elementIndex,
                    'confidence' => 0.9,
                ];
            }
        }

        return $citations;
    }

    /**
     * @return array<int, array{type: string, raw_text: string, author: string|null, year: string|null, numbers: array|null, element_index: int, confidence: float}>
     */
    private function detectBracketed(string $text, int $elementIndex): array
    {
        $citations = [];

        if (preg_match_all(self::BRACKETED_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $rawText = $match[0][0];
                $author = trim($match[1][0]);
                $year = $match[2][0];

                $citations[] = [
                    'type' => 'bracketed',
                    'raw_text' => $rawText,
                    'author' => $author,
                    'year' => $year,
                    'numbers' => null,
                    'element_index' => $elementIndex,
                    'confidence' => 0.92,
                ];
            }
        }

        return $citations;
    }
}
