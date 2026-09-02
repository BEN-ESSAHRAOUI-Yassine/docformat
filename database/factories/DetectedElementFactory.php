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

    public function figure(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'figure',
            'content' => null,
            'metadata' => [
                'name' => 'image_'.fake()->numberBetween(1, 100),
                'image_type' => fake()->randomElement(['image/png', 'image/jpeg', 'image/gif']),
                'width' => fake()->numberBetween(100, 800),
                'height' => fake()->numberBetween(100, 600),
                'is_watermark' => false,
                'section' => fake()->numberBetween(0, 5),
            ],
        ]);
    }

    public function table(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'table',
            'content' => null,
            'metadata' => [
                'rows' => fake()->numberBetween(2, 10),
                'columns' => fake()->numberBetween(2, 6),
                'cells' => fake()->numberBetween(4, 60),
                'has_header' => fake()->boolean(),
                'column_widths' => [],
                'content' => [],
                'section' => fake()->numberBetween(0, 5),
            ],
        ]);
    }

    public function caption(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'caption',
            'content' => fake()->sentence(),
            'metadata' => [
                'label' => fake()->randomElement(['Figure', 'Tableau']),
                'number' => fake()->numberBetween(1, 20),
                'element_type' => fake()->randomElement(['figure', 'table']),
                'section' => fake()->numberBetween(0, 5),
            ],
        ]);
    }

    public function source(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'source',
            'content' => fake()->sentence(),
            'metadata' => [
                'section' => fake()->numberBetween(0, 5),
            ],
        ]);
    }
}
