<?php

namespace App\Services;

use App\Models\Document;
use App\Models\QualityReport;
use App\Services\Quality\QualityEngine;

class QualityReportService
{
    public function __construct(
        private QualityEngine $engine,
    ) {}

    public function generate(Document $document): QualityReport
    {
        $analysis = $document->latestAnalysis;
        $score = $this->engine->score($document);

        return QualityReport::create([
            'document_id' => $document->id,
            'document_analysis_id' => $analysis?->id,
            'quality_score' => $score,
            'sections' => $this->buildSections($document),
            'summary' => $this->buildSummary($document, $score),
            'generated_at' => now(),
        ]);
    }

    public function latest(Document $document): ?QualityReport
    {
        return $document->qualityReports()->latest('id')->first();
    }

    private function buildSections(Document $document): array
    {
        return [
            'structure' => [
                'headings' => $document->elements()->where('type', 'heading')->count(),
            ],
            'figures' => [
                'total' => $document->elements()->where('type', 'figure')->count(),
                'captioned' => $document->elements()->where('type', 'caption')->count(),
            ],
            'tables' => [
                'total' => $document->elements()->where('type', 'table')->count(),
            ],
            'citations' => [
                'total' => $document->citations()->count(),
            ],
            'bibliography' => [
                'total' => $document->bibliographyEntries()->count(),
            ],
            'style' => [
                'violations' => $document->issues()->where('source', 'style')->count(),
            ],
        ];
    }

    private function buildSummary(Document $document, array $score): array
    {
        $issues = $document->issues();

        return [
            'document_id' => $document->id,
            'total_issues' => $issues->count(),
            'pending_issues' => $issues->pending()->count(),
            'ignored_issues' => $issues->where('decision', 'ignored')->count(),
            'overall_score' => $score['overall_score'],
            'counts' => $score['counts'],
        ];
    }
}
