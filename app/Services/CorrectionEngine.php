<?php

namespace App\Services;

use App\Models\DetectedElement;
use App\Models\Document;
use App\Services\Ai\ProviderManager;

class CorrectionEngine
{
    /**
     * @param  array<int, string>  $corrections
     */
    private const TYPO_MAP = [
        'teh' => 'the',
        'recieve' => 'receive',
        'occured' => 'occurred',
        'seperate' => 'separate',
        'definately' => 'definitely',
    ];

    public function __construct(
        private ?ProviderManager $providerManager = null,
    ) {}

    /**
     * @return array<int, array{original: string, suggested: string, reason: string, confidence: float, reversible: bool, category: string}>
     */
    public function run(Document $document): array
    {
        $text = DetectedElement::where('document_id', $document->id)
            ->where('type', 'paragraph')
            ->pluck('content')
            ->filter()
            ->implode(' ');

        if ($text === '') {
            return [];
        }

        return $this->checkText($text);
    }

    /**
     * @return array<int, array{original: string, suggested: string, reason: string, confidence: float, reversible: bool, category: string}>
     */
    public function checkText(string $text): array
    {
        $corrections = [];

        foreach ($this->sentences($text) as $sentence) {
            $corrections = array_merge($corrections, $this->checkCapitalization($sentence));
            $corrections = array_merge($corrections, $this->checkPunctuation($sentence));
            $corrections = array_merge($corrections, $this->checkDoubleSpaces($sentence));
        }

        foreach (self::TYPO_MAP as $wrong => $right) {
            if (preg_match('/\b'.$wrong.'\b/i', $text)) {
                $corrections[] = [
                    'original' => $wrong,
                    'suggested' => $right,
                    'reason' => 'Common spelling error',
                    'confidence' => 0.95,
                    'reversible' => true,
                    'category' => 'spelling',
                ];
            }
        }

        return $corrections;
    }

    /**
     * @return array<int, array{original: string, suggested: string, reason: string, confidence: float, reversible: bool, category: string}>
     */
    private function checkCapitalization(string $sentence): array
    {
        if ($sentence === '') {
            return [];
        }

        $first = $sentence[0];

        if (preg_match('/[a-z]/', $first)) {
            return [[
                'original' => $sentence,
                'suggested' => ucfirst($sentence),
                'reason' => 'Sentence should start with a capital letter',
                'confidence' => 0.9,
                'reversible' => true,
                'category' => 'grammar',
            ]];
        }

        return [];
    }

    /**
     * @return array<int, array{original: string, suggested: string, reason: string, confidence: float, reversible: bool, category: string}>
     */
    private function checkPunctuation(string $sentence): array
    {
        if ($sentence === '') {
            return [];
        }

        if (! preg_match('/[.!?]$/', $sentence)) {
            return [[
                'original' => $sentence,
                'suggested' => $sentence.'.',
                'reason' => 'Sentence is missing ending punctuation',
                'confidence' => 0.8,
                'reversible' => true,
                'category' => 'grammar',
            ]];
        }

        return [];
    }

    /**
     * @return array<int, array{original: string, suggested: string, reason: string, confidence: float, reversible: bool, category: string}>
     */
    private function checkDoubleSpaces(string $text): array
    {
        $trimmed = preg_replace('/ {2,}/', ' ', $text);

        if ($trimmed !== $text) {
            return [[
                'original' => $text,
                'suggested' => $trimmed,
                'reason' => 'Double spaces found',
                'confidence' => 1.0,
                'reversible' => true,
                'category' => 'spacing',
            ]];
        }

        return [];
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
