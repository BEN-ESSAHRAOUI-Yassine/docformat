<?php

namespace App\Services;

class ParaphraseEngine
{
    /**
     * @return array{original: string, alternative: string, confidence: float, suggest_citation: bool}
     */
    public function suggest(string $text, bool $sourceDetected = false): array
    {
        $alternative = app(AiContentService::class)->paraphrase($text)[0]['text'] ?? $text;

        return [
            'original' => $text,
            'alternative' => $alternative,
            'confidence' => 0.8,
            'suggest_citation' => $sourceDetected,
        ];
    }
}
