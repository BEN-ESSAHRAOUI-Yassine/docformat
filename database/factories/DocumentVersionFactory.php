<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version_number' => 1,
            'file_path' => 'originals/'.now()->format('Y/m/d').'/'.fake()->sha256().'.docx',
            'file_size' => fake()->numberBetween(1000, 1000000),
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_by' => User::factory(),
        ];
    }
}
