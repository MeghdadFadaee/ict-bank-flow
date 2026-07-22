<?php

namespace App\Actions\WorkflowConfiguration;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Models\LoanType;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use Illuminate\Support\Facades\DB;

class CreateWorkflowConfigurationAction
{
    public function __construct(private ValidateWorkflowStepDataAction $validateWorkflowStepData) {}

    /**
     * @param  array<int, array{stage_definition_id: int, rules?: array<string, mixed>, is_enabled?: bool}>  $steps
     */
    public function handle(
        LoanType $loanType,
        string $name,
        User $creator,
        array $steps = [],
        ?WorkflowConfiguration $copyFrom = null,
    ): WorkflowConfiguration {
        return DB::transaction(function () use ($loanType, $name, $creator, $steps, $copyFrom): WorkflowConfiguration {
            $lockedLoanType = LoanType::query()->lockForUpdate()->findOrFail($loanType->getKey());

            $nextVersion = ((int) $lockedLoanType->workflowConfigurations()->max('version')) + 1;

            $workflowConfiguration = $lockedLoanType->workflowConfigurations()->create([
                'name' => $name,
                'version' => $nextVersion,
                'status' => WorkflowConfigurationStatus::Draft,
                'created_by' => $creator->getKey(),
            ]);

            $stepsToCreate = $copyFrom === null
                ? $steps
                : $copyFrom->steps()
                    ->get(['stage_definition_id', 'rules', 'is_enabled'])
                    ->map(fn ($step): array => [
                        'stage_definition_id' => $step->stage_definition_id,
                        'rules' => $step->rules,
                        'is_enabled' => $step->is_enabled,
                    ])
                    ->all();

            $validatedSteps = $this->validateWorkflowStepData->handle($stepsToCreate);

            foreach ($validatedSteps as $index => $step) {
                $workflowConfiguration->steps()->create([
                    'stage_definition_id' => $step['stage_definition_id'],
                    'position' => $index + 1,
                    'rules' => $step['rules'] ?? [],
                    'is_enabled' => $step['is_enabled'] ?? true,
                ]);
            }

            return $workflowConfiguration->load(['loanType', 'steps.stageDefinition', 'creator']);
        });
    }
}
