<?php

namespace Database\Factories;

use App\Enums\AnalysisStatus;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAnalysis>
 */
class DocumentAnalysisFactory extends Factory
{
    protected $model = DocumentAnalysis::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'document_version_id' => DocumentVersion::factory(),
            'status' => AnalysisStatus::PENDING,
            'error_message' => null,
            'metadata' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisStatus::COMPLETED,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisStatus::FAILED,
            'error_message' => fake()->sentence(),
        ]);
    }
}
