<?php

namespace App\Domain\Loan\Workflow;

use App\Domain\Loan\Contracts\ConditionalStageInterface;
use App\Domain\Loan\Enums\LoanStage;
use App\Domain\Loan\Enums\LoanStatus;
use App\Domain\Loan\Enums\StageResultType;
use App\Domain\Loan\Exceptions\InvalidWorkflowConfiguration;
use App\Models\Loan;
use App\Models\WorkflowConfigurationStep;

class WorkflowEngine
{
    public function __construct(private WorkflowConfigurationResolver $resolver) {}

    public function process(Loan $loan): Loan
    {
        if ($loan->status->isTerminal()) {
            return $loan;
        }

        if (! $loan->relationLoaded('loanType') || ! $loan->relationLoaded('workflowConfiguration')) {
            throw new InvalidWorkflowConfiguration('The Loan workflow relationships must be loaded before processing.');
        }

        $loan->forceFill(['status' => LoanStatus::InProgress])->save();

        $executedStepIds = $loan->histories->pluck('workflow_configuration_step_id')->all();

        foreach ($loan->workflowConfiguration->steps as $step) {
            if (! $step->is_enabled || in_array($step->getKey(), $executedStepIds, true)) {
                continue;
            }

            $this->executeStep($loan, $step);

            if ($loan->status->isTerminal()) {
                return $loan;
            }
        }

        $loan->forceFill([
            'status' => LoanStatus::Approved,
            'current_workflow_configuration_step_id' => null,
        ])->save();

        return $loan;
    }

    private function executeStep(Loan $loan, WorkflowConfigurationStep $step): void
    {
        $handler = $this->resolver->resolve($step);
        $rules = $step->rules;

        if ($handler instanceof ConditionalStageInterface && ! $handler->appliesTo($loan, $rules)) {
            return;
        }

        $loan->forceFill([
            'current_workflow_configuration_step_id' => $step->getKey(),
        ])->save();

        $result = $handler->execute($loan, $rules);
        $stage = LoanStage::from($step->stageDefinition->code);

        $loan->histories()->create([
            'workflow_configuration_step_id' => $step->getKey(),
            'stage_code' => $stage,
            'rules_snapshot' => $rules,
            'result' => $result->type,
            'reason' => $result->reason,
            'executed_at' => now('UTC'),
        ]);

        if ($result->type === StageResultType::Fail) {
            $loan->forceFill([
                'status' => LoanStatus::Rejected,
                'current_workflow_configuration_step_id' => null,
            ])->save();
        }

        if ($result->type === StageResultType::ManualReview) {
            $loan->forceFill([
                'status' => LoanStatus::ManualReview,
                'current_workflow_configuration_step_id' => null,
            ])->save();
        }
    }
}
