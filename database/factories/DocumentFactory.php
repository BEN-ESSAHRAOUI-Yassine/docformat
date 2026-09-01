<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'original_filename' => fake()->uuid().'.docx',
            'project_id' => Project::factory(),
            'status' => 'uploaded',
            'file_hash' => hash('sha256', fake()->unique()->uuid()),
        ];
    }
}
