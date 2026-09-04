<?php

namespace Database\Factories;

use App\Enums\BatchItemStatus;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BatchItem>
 */
class BatchItemFactory extends Factory
{
    protected $model = BatchItem::class;

    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'document_id' => Document::factory(),
            'status' => BatchItemStatus::Queued->value,
            'quality_score' => null,
            'error' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => BatchItemStatus::Completed->value,
            'quality_score' => fake()->randomFloat(1, 0, 100),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => BatchItemStatus::Failed->value,
            'error' => 'Processing failed',
        ]);
    }
}
