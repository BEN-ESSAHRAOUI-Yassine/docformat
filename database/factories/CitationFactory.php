<?php

namespace Database\Factories;

use App\Models\Citation;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citation>
 */
class CitationFactory extends Factory
{
    protected $model = Citation::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'document_analysis_id' => DocumentAnalysis::factory(),
            'detected_element_id' => null,
            'type' => fake()->randomElement(['author_year', 'numeric', 'bracketed']),
            'raw_text' => '(Smith, 2020)',
            'author' => fake()->lastName(),
            'year' => (string) fake()->year(),
            'numbers' => null,
            'element_index' => fake()->numberBetween(0, 100),
            'confidence' => fake()->randomFloat(2, 0.7, 1.0),
            'metadata' => null,
            'bibliography_entry_id' => null,
        ];
    }

    public function authorYear(): static
    {
        return $this->state(fn () => [
            'type' => 'author_year',
            'raw_text' => '(Smith, 2020)',
            'author' => 'Smith',
            'year' => '2020',
        ]);
    }

    public function numeric(): static
    {
        return $this->state(fn () => [
            'type' => 'numeric',
            'raw_text' => '[1]',
            'author' => null,
            'year' => null,
            'numbers' => [1],
        ]);
    }

    public function bracketed(): static
    {
        return $this->state(fn () => [
            'type' => 'bracketed',
            'raw_text' => '[Smith 2020]',
            'author' => 'Smith',
            'year' => '2020',
        ]);
    }
}
