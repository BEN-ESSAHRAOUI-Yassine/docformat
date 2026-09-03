<?php

namespace Database\Factories;

use App\Models\BibliographyEntry;
use App\Models\Document;
use App\Models\DocumentAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibliographyEntry>
 */
class BibliographyEntryFactory extends Factory
{
    protected $model = BibliographyEntry::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'document_analysis_id' => DocumentAnalysis::factory(),
            'detected_element_id' => null,
            'entry_type' => fake()->randomElement(['article', 'book', 'chapter', 'conference', 'online', 'thesis', 'other']),
            'authors' => [fake()->lastName().', '.fake()->randomLetter().'.'],
            'title' => fake()->sentence(),
            'year' => (string) fake()->year(),
            'journal' => fake()->optional()->words(3, true),
            'publisher' => fake()->optional()->company(),
            'volume' => fake()->optional()->numberBetween(1, 50),
            'issue' => fake()->optional()->numberBetween(1, 12),
            'pages' => fake()->optional()->numerify('##-##'),
            'doi' => fake()->optional()->slug(),
            'url' => fake()->optional()->url(),
            'access_date' => null,
            'extra_fields' => null,
            'raw_text' => fake()->sentence(),
            'element_index' => fake()->numberBetween(0, 100),
            'is_duplicate' => false,
            'duplicate_group_id' => null,
            'duplicate_confidence' => null,
        ];
    }

    public function article(): static
    {
        return $this->state(fn () => [
            'entry_type' => 'article',
            'journal' => 'Journal of Example Studies',
            'volume' => '15',
            'issue' => '3',
            'pages' => '123-145',
        ]);
    }

    public function book(): static
    {
        return $this->state(fn () => [
            'entry_type' => 'book',
            'publisher' => 'Academic Press',
        ]);
    }
}
