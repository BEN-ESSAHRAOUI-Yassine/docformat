<?php

namespace Database\Factories;

use App\Models\Abbreviation;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Abbreviation>
 */
class AbbreviationFactory extends Factory
{
    protected $model = Abbreviation::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'document_analysis_id' => DocumentAnalysis::factory(),
            'detected_element_id' => null,
            'abbreviation' => strtoupper(fake()->bothify('??')),
            'full_form' => fake()->words(3, true),
            'definition_element_index' => fake()->numberBetween(0, 100),
            'usage_count' => fake()->numberBetween(0, 20),
            'occurrences' => fake()->optional()->passthrough([0, 1, 2]),
            'is_consistent' => true,
            'inconsistent_forms' => null,
        ];
    }

    public function consistent(): static
    {
        return $this->state(fn () => [
            'is_consistent' => true,
            'inconsistent_forms' => null,
        ]);
    }

    public function inconsistent(): static
    {
        return $this->state(fn () => [
            'is_consistent' => false,
            'inconsistent_forms' => ['Different Form'],
        ]);
    }
}
