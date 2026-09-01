<?php

namespace Database\Factories;

use App\Models\StyleProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StyleProfileFactory extends Factory
{
    protected $model = StyleProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true).' Profile',
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['university', 'thesis', 'report', 'article', 'custom']),
            'language' => 'fr-FR',
            'version' => 1,
            'rules' => $this->defaultRules(),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'is_system' => true,
        ]);
    }

    public function university(): static
    {
        return $this->state(fn () => [
            'type' => 'university',
            'rules' => $this->universityRules(),
        ]);
    }

    public function thesis(): static
    {
        return $this->state(fn () => [
            'type' => 'thesis',
            'rules' => $this->thesisRules(),
        ]);
    }

    public function report(): static
    {
        return $this->state(fn () => [
            'type' => 'report',
            'rules' => $this->reportRules(),
        ]);
    }

    public function article(): static
    {
        return $this->state(fn () => [
            'type' => 'article',
            'rules' => $this->articleRules(),
        ]);
    }

    private function defaultRules(): array
    {
        return [
            'body' => [
                'font_family' => 'Times New Roman',
                'font_size' => 11,
                'color' => '#000000',
                'alignment' => 'justify',
            ],
            'heading_1' => [
                'font_family' => 'Times New Roman',
                'font_size' => 18,
                'color' => '#000000',
                'bold' => true,
                'all_caps' => true,
                'alignment' => 'center',
            ],
        ];
    }

    private function universityRules(): array
    {
        return $this->defaultRules();
    }

    private function thesisRules(): array
    {
        return array_merge($this->defaultRules(), [
            'heading_1' => [
                'font_family' => 'Times New Roman',
                'font_size' => 26,
                'color' => '#000000',
                'bold' => true,
                'all_caps' => true,
                'alignment' => 'center',
                'border' => true,
                'shading' => true,
            ],
        ]);
    }

    private function reportRules(): array
    {
        return $this->defaultRules();
    }

    private function articleRules(): array
    {
        return $this->defaultRules();
    }
}
