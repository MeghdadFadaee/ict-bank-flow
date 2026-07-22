<?php

namespace Database\Factories;

use App\Models\StageDefinition;
use App\Models\WorkflowConfiguration;
use App\Models\WorkflowConfigurationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowConfigurationStep>
 */
class WorkflowConfigurationStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_configuration_id' => WorkflowConfiguration::factory(),
            'stage_definition_id' => StageDefinition::factory(),
            'position' => fake()->unique()->numberBetween(1, 1000),
            'rules' => [],
            'is_enabled' => true,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
