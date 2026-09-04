<?php

namespace App\Services;

class SynonymEngine
{
    /**
     * @return array{word: string, synonyms: array<int, string>}
     */
    public function suggest(string $word): array
    {
        $synonyms = app(AiContentService::class)->synonyms($word);

        return [
            'word' => $word,
            'synonyms' => $synonyms,
        ];
    }
}
