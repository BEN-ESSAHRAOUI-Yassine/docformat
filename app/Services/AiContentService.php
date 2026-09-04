<?php

namespace App\Services;

use App\Models\DetectedElement;
use App\Models\Document;
use App\Services\Ai\ProviderManager;

class AiContentService
{
    public function __construct(
        private ProviderManager $providerManager,
    ) {}

    /**
     * @return array<int, array{type: string, text: string, message: string, confidence: float}>
     */
    public function analyze(Document $document): array
    {
        $text = $this->documentText($document);

        if ($text === '') {
            return [];
        }

        return $this->providerManager->withFallback(
            (string) config('services.ai.default', 'local'),
            fn ($provider) => $provider->analyze($text)
        );
    }

    /**
     * @return array<int, array{text: string, confidence: float}>
     */
    public function paraphrase(string $text): array
    {
        $alternative = $this->providerManager->withFallback(
            (string) config('services.ai.default', 'local'),
            fn ($provider) => $provider->paraphrase($text)
        );

        return [[
            'text' => $alternative,
            'confidence' => 0.8,
        ]];
    }

    /**
     * @return array<int, string>
     */
    public function synonyms(string $word): array
    {
        return $this->providerManager->withFallback(
            (string) config('services.ai.default', 'local'),
            fn ($provider) => $provider->suggestSynonyms($word)
        );
    }

    private function documentText(Document $document): string
    {
        return DetectedElement::where('document_id', $document->id)
            ->whereIn('type', ['paragraph', 'heading'])
            ->pluck('content')
            ->filter()
            ->implode(' ');
    }
}
