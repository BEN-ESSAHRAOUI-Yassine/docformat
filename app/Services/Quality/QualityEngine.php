<?php

namespace App\Services\Quality;

use App\Enums\IssueSource;
use App\Models\Document;
use App\Models\DocumentIssue;
use Illuminate\Support\Collection;

class QualityEngine
{
    /**
     * Category weights summing to 1.0 (used for the overall score).
     */
    private const WEIGHTS = [
        'formatting' => 0.40,
        'citations' => 0.25,
        'figures_tables' => 0.20,
        'style' => 0.15,
    ];

    /**
     * Severity penalty applied as a count-normalized deduction (max deduction per category).
     */
    private const SEVERITY_PENALTY = [
        'error' => 1.0,
        'warning' => 0.5,
        'info' => 0.1,
    ];

    public function score(Document $document): array
    {
        $issues = $document->issues()->get();

        $categories = $this->computeCategoryScores($issues);
        $overall = $this->computeOverall($categories);

        return [
            'document_id' => $document->id,
            'overall_score' => $overall,
            'category_scores' => $categories,
            'counts' => [
                'errors' => $issues->where('severity', 'error')->count(),
                'warnings' => $issues->where('severity', 'warning')->count(),
                'info' => $issues->where('severity', 'info')->count(),
                'probabilistic' => $issues->where('probabilistic', true)->count(),
            ],
            'probabilistic' => $issues->where('probabilistic', true)->values(),
            'deterministic' => true,
        ];
    }

    /**
     * @param  Collection<int, DocumentIssue>  $issues
     * @return array<string, array{score: float, errors: int, warnings: int, info: int, weight: float}>
     */
    private function computeCategoryScores(Collection $issues): array
    {
        $categories = [
            'formatting' => [
                'sources' => [
                    IssueSource::Style,
                    IssueSource::Numbering,
                    IssueSource::PageIntegrity,
                ],
            ],
            'citations' => [
                'sources' => [
                    IssueSource::Citation,
                    IssueSource::Bibliography,
                    IssueSource::Duplicate,
                    IssueSource::Abbreviation,
                ],
            ],
            'figures_tables' => [
                'sources' => [
                    IssueSource::Figure,
                    IssueSource::Table,
                ],
            ],
            'style' => [
                'sources' => [
                    IssueSource::Style,
                ],
            ],
        ];

        $result = [];

        foreach ($categories as $key => $config) {
            $result[$key] = $this->scoreCategory($issues, $config['sources'], self::WEIGHTS[$key]);
        }

        return $result;
    }

    /**
     * @param  Collection<int, DocumentIssue>  $issues
     * @param  IssueSource[]  $sources
     * @return array{score: float, errors: int, warnings: int, info: int, weight: float}
     */
    private function scoreCategory(Collection $issues, array $sources, float $weight): array
    {
        $categoryIssues = $issues
            ->where('probabilistic', false)
            ->filter(fn (DocumentIssue $issue) => in_array($issue->source, $sources, true));

        $counts = [
            'errors' => $categoryIssues->where('severity', 'error')->count(),
            'warnings' => $categoryIssues->where('severity', 'warning')->count(),
            'info' => $categoryIssues->where('severity', 'info')->count(),
        ];

        $deduction = $counts['errors'] * self::SEVERITY_PENALTY['error']
            + $counts['warnings'] * self::SEVERITY_PENALTY['warning']
            + $counts['info'] * self::SEVERITY_PENALTY['info'];

        // Score is clamped to 0, so heavy issue loads never go negative.
        $score = round(max(0, 100 - $deduction), 1);

        return [
            'score' => $score,
            'errors' => $counts['errors'],
            'warnings' => $counts['warnings'],
            'info' => $counts['info'],
            'weight' => $weight,
        ];
    }

    /**
     * @param  array<string, array{score: float, weight: float}>  $categories
     */
    private function computeOverall(array $categories): float
    {
        $weighted = 0.0;
        $weightSum = 0.0;

        foreach ($categories as $category) {
            $weighted += $category['score'] * $category['weight'];
            $weightSum += $category['weight'];
        }

        if ($weightSum === 0.0) {
            return 100.0;
        }

        return round($weighted / $weightSum, 1);
    }
}
