<?php

namespace Database\Factories;

use App\Enums\IssueCategory;
use App\Enums\IssueDecision;
use App\Enums\IssueSource;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use App\Models\DocumentIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentIssue>
 */
class DocumentIssueFactory extends Factory
{
    protected $model = DocumentIssue::class;

    public function definition(): array
    {
        $source = fake()->randomElement(IssueSource::cases());

        return [
            'document_id' => Document::factory(),
            'document_analysis_id' => DocumentAnalysis::factory(),
            'detected_element_id' => null,
            'source' => $source->value,
            'category' => fake()->randomElement(IssueCategory::cases())->value,
            'severity' => fake()->randomElement(['error', 'warning', 'info']),
            'description' => fake()->sentence(),
            'recommendation' => fake()->optional()->sentence(),
            'location' => ['element_index' => fake()->numberBetween(0, 100)],
            'decision' => IssueDecision::Pending->value,
            'ignored_reason' => null,
            'review_mode' => $source->reviewMode()->value,
            'probabilistic' => false,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function error(): static
    {
        return $this->state(fn () => [
            'severity' => 'error',
        ]);
    }

    public function warning(): static
    {
        return $this->state(fn () => [
            'severity' => 'warning',
        ]);
    }

    public function info(): static
    {
        return $this->state(fn () => [
            'severity' => 'info',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'decision' => IssueDecision::Pending->value,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'decision' => IssueDecision::Accepted->value,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'decision' => IssueDecision::Rejected->value,
        ]);
    }

    public function ignored(): static
    {
        return $this->state(fn () => [
            'decision' => IssueDecision::Ignored->value,
            'ignored_reason' => 'Not applicable',
        ]);
    }

    public function probabilistic(): static
    {
        return $this->state(fn () => [
            'probabilistic' => true,
        ]);
    }
}
