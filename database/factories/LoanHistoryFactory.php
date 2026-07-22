<?php

namespace Database\Factories;

use App\Domain\Loan\Enums\StageResultType;
use App\Models\Loan;
use App\Models\LoanHistory;
use App\Models\WorkflowConfigurationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanHistory>
 */
class LoanHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'workflow_configuration_step_id' => static function (array $attributes): int {
                $loan = Loan::query()->findOrFail($attributes['loan_id']);

                return WorkflowConfigurationStep::factory()
                    ->for($loan->workflowConfiguration)
                    ->create()
                    ->getKey();
            },
            'stage_code' => static fn (array $attributes): string => WorkflowConfigurationStep::query()
                ->findOrFail($attributes['workflow_configuration_step_id'])
                ->stageDefinition
                ->code,
            'rules_snapshot' => static fn (array $attributes): array => WorkflowConfigurationStep::query()
                ->findOrFail($attributes['workflow_configuration_step_id'])
                ->rules,
            'result' => StageResultType::Pass,
            'reason' => 'SUCCESS',
            'executed_at' => now(),
        ];
    }
}
