<?php

namespace App\Actions\WorkflowConfiguration;

use App\Domain\Loan\Enums\LoanStage;
use App\Models\WorkflowConfiguration;
use Illuminate\Validation\ValidationException;

class ValidateWorkflowConfigurationAction
{
    public function __construct(private ValidateWorkflowStepDataAction $validateWorkflowStepData) {}

    public function handle(WorkflowConfiguration $workflowConfiguration): void
    {
        $workflowConfiguration->loadMissing(['loanType', 'steps.stageDefinition']);

        $enabledSteps = $workflowConfiguration->steps
            ->where('is_enabled', true)
            ->values();

        $stepData = $enabledSteps
            ->map(fn ($step): array => [
                'stage_definition_id' => $step->stage_definition_id,
                'rules' => $step->rules,
                'is_enabled' => true,
            ])
            ->all();

        $this->validateWorkflowStepData->handle($stepData);

        $enabledCodes = $enabledSteps->pluck('stageDefinition.code')->all();
        $errors = [];

        foreach ($this->requiredStageCodes($workflowConfiguration->loanType->code) as $requiredStageCode) {
            if (! in_array($requiredStageCode, $enabledCodes, true)) {
                $errors['steps'] = "The {$requiredStageCode} stage is required for {$workflowConfiguration->loanType->name}.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return list<string>
     */
    private function requiredStageCodes(string $loanTypeCode): array
    {
        $required = [
            LoanStage::Validation->value,
            LoanStage::FraudCheck->value,
            LoanStage::CreditCheck->value,
            LoanStage::ManagerApproval->value,
        ];

        if ($loanTypeCode === 'BUSINESS') {
            $required[] = LoanStage::GuarantorCheck->value;
        }

        return $required;
    }
}
