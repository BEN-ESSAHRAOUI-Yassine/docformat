<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GroqProvider implements AiProvider
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private string $model,
    ) {}

    public function name(): string
    {
        return 'groq';
    }

    public function analyze(string $text, array $options = []): array
    {
        $prompt = $options['prompt'] ?? $this->defaultAnalyzePrompt();
        $response = $this->client()->post('/chat/completions', [
            'model' => $this->model,
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $text],
            ],
        ]);

        $response->throw();

        $content = data_get($response->json(), 'choices.0.message.content', '');

        return $this->extractJson($content);
    }

    public function paraphrase(string $text, array $options = []): string
    {
        $response = $this->client()->post('/chat/completions', [
            'model' => $this->model,
            'temperature' => 0.5,
            'messages' => [
                ['role' => 'system', 'content' => 'Rephrase the following text while preserving its technical meaning and tone. Return only the rephrased text.'],
                ['role' => 'user', 'content' => $text],
            ],
        ]);

        $response->throw();

        return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
    }

    public function suggestSynonyms(string $word, array $options = []): array
    {
        $response = $this->client()->post('/chat/completions', [
            'model' => $this->model,
            'temperature' => 0.3,
            'messages' => [
                ['role' => 'system', 'content' => 'Return a JSON array of 0-5 synonyms for the given word that do not change its technical meaning. Output only valid JSON.'],
                ['role' => 'user', 'content' => $word],
            ],
        ]);

        $response->throw();

        $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

        return $this->extractArray($content);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(10)
            ->timeout(30);
    }

    private function defaultAnalyzePrompt(): string
    {
        return 'Analyze the following academic/technical text. Identify weakly supported assertions, sections lacking references, and suggest additional references based on the citation context. Return a JSON array of objects with keys: type (assertion|reference|citation), text, message, confidence (0-1).';
    }

    private function extractJson(string $content): array
    {
        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Fall back to parsing a JSON array embedded in the response text.
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            $embedded = json_decode($matches[0], true);
            if (is_array($embedded)) {
                return $embedded;
            }
        }

        return [];
    }

    private function extractArray(string $content): array
    {
        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\[.*\]/s', $content, $matches)) {
            $embedded = json_decode($matches[0], true);
            if (is_array($embedded)) {
                return $embedded;
            }
        }

        return [];
    }
}
