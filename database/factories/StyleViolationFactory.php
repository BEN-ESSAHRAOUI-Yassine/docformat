<?php

namespace Database\Factories;

use App\Models\DocumentAnalysis;
use App\Models\StyleViolation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StyleViolationFactory extends Factory
{
    protected $model = StyleViolation::class;

    public function definition(): array
    {
        return [
            'document_analysis_id' => DocumentAnalysis::factory(),
            'detected_element_id' => null,
            'check_type' => fake()->randomElement(['font_family', 'font_size', 'color', 'bold', 'italic', 'alignment', 'indentation', 'spacing']),
            'expected_value' => ['value' => 'Times New Roman'],
            'actual_value' => ['value' => 'Arial'],
            'severity' => fake()->randomElement(['error', 'warning', 'info']),
            'category' => fake()->randomElement(['font', 'spacing', 'alignment', 'formatting']),
            'description' => fake()->sentence(),
            'recommendation' => fake()->sentence(),
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
}
