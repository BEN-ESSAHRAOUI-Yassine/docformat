<?php

namespace App\Services;

use App\Models\DetectedElement;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

class SimilarityEngine
{
    /**
     * @return array{overall: float, matches: array<int, array{text: string, source: string, source_document: int|null, confidence: float, match_type: string}>}
     */
    public function compare(Document $document): array
    {
        $source = $this->documentText($document);

        if ($source === '') {
            return ['overall' => 0.0, 'matches' => []];
        }

        $chunks = $this->chunks($source);
        $corpus = $this->corpus($document);
        $matches = [];
        $weighted = 0.0;

        foreach ($chunks as $chunk) {
            [$best, $score] = $this->findBestMatch($document, $chunk, $corpus);

            if ($score >= 0.5) {
                $matches[] = [
                    'text' => $chunk,
                    'source' => $best ? $best['name'] : '',
                    'source_document' => $best ? $best['id'] : null,
                    'confidence' => round($score, 2),
                    'match_type' => $score >= 0.85 ? 'direct' : 'weak',
                ];
                $weighted += $score;
            }
        }

        $overall = $matches === [] ? 0.0 : round(min(100, ($weighted / count($matches)) * 100), 1);

        return [
            'overall' => $overall,
            'matches' => $matches,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, text: string}>
     */
    private function corpus(Document $document): array
    {
        $exclude = $document->id;

        return Document::query()
            ->where('id', '!=', $exclude)
            ->limit(20)
            ->get()
            ->map(function (Document $d) {
                $text = $this->documentText($d);

                return ['id' => $d->id, 'name' => $d->name, 'text' => $text];
            })
            ->filter(fn ($item) => $item['text'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function chunks(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/', $text);

        return array_values(array_filter(array_map('trim', $parts ?: []), fn ($c) => mb_strlen($c) > 40));
    }

    /**
     * @param  array<int, array{id: int, name: string, text: string}>  $corpus
     * @return array{0: array|null, 1: float}
     */
    private function findBestMatch(Document $document, string $chunk, array $corpus): array
    {
        $best = null;
        $bestScore = 0.0;

        foreach ($corpus as $candidate) {
            // Avoid comparing the document to itself.
            if ($candidate['id'] === $document->id) {
                continue;
            }

            foreach ($this->chunks($candidate['text']) as $candidateChunk) {
                $score = $this->similarity($chunk, $candidateChunk);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $candidate;
                }
            }
        }

        return [$best, $bestScore];
    }

    private function documentText(Document $document): string
    {
        $elements = DetectedElement::where('document_id', $document->id)
            ->whereIn('type', ['paragraph', 'heading'])
            ->pluck('content')
            ->filter()
            ->values();

        if ($elements->isNotEmpty()) {
            return $elements->implode(' ');
        }

        $version = $document->currentVersion;
        if (! $version) {
            return '';
        }

        $path = Storage::disk('docformat')->path($version->file_path);
        if (! file_exists($path)) {
            return '';
        }

        return $this->extractText($path);
    }

    private function extractText(string $path): string
    {
        try {
            $phpWord = IOFactory::load($path);
            $text = [];
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof Text) {
                        $text[] = $element->getText();
                    } elseif ($element instanceof TextRun) {
                        $text[] = $element->getText();
                    }
                }
            }

            return implode(' ', $text);
        } catch (\Throwable) {
            return '';
        }
    }

    private function similarity(string $a, string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);

        if ($a === $b) {
            return 1.0;
        }

        if (strlen($a) === 0 || strlen($b) === 0) {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        return round($percent / 100, 2);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
