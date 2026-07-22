<?php

namespace Database\Factories;

use App\Domain\Loan\Enums\LoanStatus;
use App\Domain\Loan\Enums\StageResultType;
use App\Models\Loan;
use App\Models\LoanHistory;
use App\Models\WorkflowConfiguration;
use App\Models\WorkflowConfigurationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => 'L-'.fake()->unique()->numerify('#####'),
            'customer_id' => 'C-'.fake()->unique()->numerify('####'),
            'workflow_configuration_id' => WorkflowConfiguration::factory()->published(),
            'loan_type_id' => static fn (array $attributes): int => WorkflowConfiguration::query()
                ->findOrFail($attributes['workflow_configuration_id'])
                ->loan_type_id,
            'current_workflow_configuration_step_id' => null,
            'amount' => fake()->numberBetween(1_000_000, 1_000_000_000),
            'phone' => '09'.fake()->numerify('#########'),
            'monthly_income' => fake()->numberBetween(1_000_000, 100_000_000),
            'credit_score' => fake()->numberBetween(0, 1000),
            'has_guarantor' => fake()->boolean(),
            'status' => LoanStatus::Submitted,
        ];
    }

    public function forWorkflow(WorkflowConfiguration $workflowConfiguration): static
    {
        return $this
            ->for($workflowConfiguration->loanType, 'loanType')
            ->for($workflowConfiguration, 'workflowConfiguration');
    }

    public function atStep(WorkflowConfigurationStep $step): static
    {
        return $this->forWorkflow($step->workflowConfiguration)->state([
            'current_workflow_configuration_step_id' => $step->getKey(),
        ]);
    }

    public function withHistory(
        WorkflowConfigurationStep $step,
        StageResultType $result = StageResultType::Pass,
        string $reason = 'SUCCESS',
    ): static {
        return $this->has(
            LoanHistory::factory()
                ->for($step, 'workflowConfigurationStep')
                ->state([
                    'stage_code' => $step->stageDefinition->code,
                    'rules_snapshot' => $step->rules,
                    'result' => $result,
                    'reason' => $reason,
                ]),
            'histories',
        );
    }
}
