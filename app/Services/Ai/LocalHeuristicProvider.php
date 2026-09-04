<?php

namespace App\Services\Ai;

class LocalHeuristicProvider implements AiProvider
{
    public function name(): string
    {
        return 'local';
    }

    public function analyze(string $text, array $options = []): array
    {
        $findings = [];

        // Heuristic: long paragraph without any reference marker.
        foreach ($this->sentences($text) as $sentence) {
            if (mb_strlen($sentence) > 200 && ! preg_match('/\(?\w+\s*,?\s*\d{4}\)?/', $sentence)) {
                $findings[] = [
                    'type' => 'reference',
                    'text' => trim($sentence),
                    'message' => 'This sentence appears to lack a supporting reference.',
                    'confidence' => 0.6,
                ];
            }
        }

        // Heuristic: weak language indicating a weakly supported assertion.
        if (preg_match('/\b(probably|maybe|perhaps|could be|might be|it is believed)\b/i', $text)) {
            $findings[] = [
                'type' => 'assertion',
                'text' => 'Contains hedged language.',
                'message' => 'Hedged language may indicate a weakly supported assertion.',
                'confidence' => 0.5,
            ];
        }

        return $findings;
    }

    public function paraphrase(string $text, array $options = []): string
    {
        return 'Paraphrase: '.$text;
    }

    public function suggestSynonyms(string $word, array $options = []): array
    {
        $map = [
            'important' => ['significant', 'relevant', 'critical'],
            'use' => ['utilize', 'apply', 'employ'],
            'show' => ['demonstrate', 'illustrate', 'reveal'],
        ];

        return $map[mb_strtolower($word)] ?? [];
    }

    /**
     * @return array<int, string>
     */
    private function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/', $text);

        return $parts ? array_filter(array_map('trim', $parts)) : [];
    }
}
