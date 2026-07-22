<?php

namespace Database\Factories;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Models\LoanType;
use App\Models\StageDefinition;
use App\Models\WorkflowConfiguration;
use App\Models\WorkflowConfigurationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowConfiguration>
 */
class WorkflowConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_type_id' => LoanType::factory(),
            'name' => fake()->words(3, true),
            'version' => 1,
            'status' => WorkflowConfigurationStatus::Draft,
            'published_at' => null,
            'created_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowConfigurationStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowConfigurationStatus::Archived,
            'published_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public function withStep(StageDefinition $stageDefinition, int $position, array $rules = []): static
    {
        return $this->has(
            WorkflowConfigurationStep::factory()
                ->for($stageDefinition, 'stageDefinition')
                ->state([
                    'position' => $position,
                    'rules' => $rules,
                ]),
            'steps',
        );
    }
}
