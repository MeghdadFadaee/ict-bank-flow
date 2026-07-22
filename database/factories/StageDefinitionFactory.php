<?php

namespace Database\Factories;

use App\Domain\Loan\Enums\LoanStage;
use App\Models\StageDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StageDefinition>
 */
class StageDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stage = fake()->randomElement(LoanStage::cases());

        return [
            'code' => $stage->value,
            'name' => str($stage->name)->headline()->toString(),
            'is_active' => true,
        ];
    }

    public function forStage(LoanStage $stage): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $stage->value,
            'name' => str($stage->name)->headline()->toString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
