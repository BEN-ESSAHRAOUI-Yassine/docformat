<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentElement;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentElementFactory extends Factory
{
    protected $model = DocumentElement::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'type' => fake()->randomElement(['heading', 'paragraph', 'table', 'image']),
            'element_index' => fake()->numberBetween(0, 100),
            'content' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }
}
