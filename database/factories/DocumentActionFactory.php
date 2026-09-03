<?php

namespace Database\Factories;

use App\Enums\ActionOrigin;
use App\Enums\ActionType;
use App\Enums\Reversibility;
use App\Models\Document;
use App\Models\DocumentAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAction>
 */
class DocumentActionFactory extends Factory
{
    protected $model = DocumentAction::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'action_type' => fake()->randomElement(ActionType::cases())->value,
            'element_type' => fake()->randomElement(['heading', 'figure', 'table', 'caption', 'citation', 'bibliography']),
            'element_id' => fake()->optional()->numberBetween(1, 1000),
            'origin' => fake()->randomElement(ActionOrigin::cases())->value,
            'old_value' => null,
            'new_value' => null,
            'payload' => null,
            'reversibility' => fake()->randomElement(Reversibility::cases())->value,
            'bulk_id' => null,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn () => [
            'origin' => ActionOrigin::Manual->value,
        ]);
    }

    public function reversible(): static
    {
        return $this->state(fn () => [
            'reversibility' => Reversibility::Full->value,
        ]);
    }

    public function nonReversible(): static
    {
        return $this->state(fn () => [
            'reversibility' => Reversibility::None->value,
        ]);
    }
}
