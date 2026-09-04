<?php

namespace App\Services\Ai;

interface AiProvider
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $options
     */
    public function analyze(string $text, array $options = []): array;

    /**
     * @param  array<string, mixed>  $options
     */
    public function paraphrase(string $text, array $options = []): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    public function suggestSynonyms(string $word, array $options = []): array;
}
