<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\QualityReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityReport>
 */
class QualityReportFactory extends Factory
{
    protected $model = QualityReport::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'document_analysis_id' => DocumentAnalysis::factory(),
            'quality_score' => [
                'overall_score' => 90.0,
                'category_scores' => [],
                'counts' => ['errors' => 0, 'warnings' => 1, 'info' => 2],
            ],
            'sections' => [],
            'summary' => ['total_issues' => 3],
            'generated_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'quality_score' => [
                'overall_score' => 100.0,
                'category_scores' => [],
                'counts' => ['errors' => 0, 'warnings' => 0, 'info' => 0],
            ],
            'summary' => ['total_issues' => 0],
        ]);
    }
}
