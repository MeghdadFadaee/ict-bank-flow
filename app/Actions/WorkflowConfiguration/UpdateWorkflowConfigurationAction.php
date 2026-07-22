<?php

namespace App\Actions\WorkflowConfiguration;

use App\Models\WorkflowConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateWorkflowConfigurationAction
{
    public function __construct(private ValidateWorkflowStepDataAction $validateWorkflowStepData) {}

    /**
     * @param  array<int, array{stage_definition_id: int, rules?: array<string, mixed>, is_enabled?: bool}>  $steps
     */
    public function handle(WorkflowConfiguration $workflowConfiguration, string $name, array $steps): WorkflowConfiguration
    {
        $validatedSteps = $this->validateWorkflowStepData->handle($steps);

        return DB::transaction(function () use ($workflowConfiguration, $name, $validatedSteps): WorkflowConfiguration {
            $lockedConfiguration = WorkflowConfiguration::query()
                ->lockForUpdate()
                ->findOrFail($workflowConfiguration->getKey());

            if (! $lockedConfiguration->isEditable()) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft workflow configurations can be changed.',
                ]);
            }

            $lockedConfiguration->update(['name' => $name]);
            $lockedConfiguration->steps()->delete();

            foreach ($validatedSteps as $index => $step) {
                $lockedConfiguration->steps()->create([
                    'stage_definition_id' => $step['stage_definition_id'],
                    'position' => $index + 1,
                    'rules' => $step['rules'] ?? [],
                    'is_enabled' => $step['is_enabled'] ?? true,
                ]);
            }

            return $lockedConfiguration->load(['loanType', 'steps.stageDefinition', 'creator']);
        });
    }
}
