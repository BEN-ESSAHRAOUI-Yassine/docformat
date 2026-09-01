<?php

namespace Database\Factories;

use App\Models\DetectedElement;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetectedElement>
 */
class DetectedElementFactory extends Factory
{
    protected $model = DetectedElement::class;

    public function definition(): array
    {
        return [
            'document_analysis_id' => DocumentAnalysis::factory(),
            'document_id' => Document::factory(),
            'type' => fake()->randomElement(['heading', 'paragraph', 'table', 'figure', 'list', 'page_break']),
            'element_index' => fake()->numberBetween(0, 100),
            'content' => fake()->optional()->sentence(),
            'heading_level' => null,
            'metadata' => null,
        ];
    }

    public function heading(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'heading',
            'heading_level' => fake()->numberBetween(1, 6),
            'metadata' => ['confidence' => fake()->randomFloat(2, 0.5, 1.0)],
        ]);
    }

    public function paragraph(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'paragraph',
        ]);
    }
}
