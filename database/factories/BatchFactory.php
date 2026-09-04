<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Project;
use App\Models\StyleProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'style_profile_id' => StyleProfile::factory(),
            'name' => fake()->words(3, true),
            'status' => BatchStatus::Queued->value,
            'summary' => [
                'total' => 0,
                'completed' => 0,
                'failed' => 0,
                'pending' => 0,
                'average_score' => null,
            ],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => BatchStatus::Completed->value,
        ]);
    }
}
