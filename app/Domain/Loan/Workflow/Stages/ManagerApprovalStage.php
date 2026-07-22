<?php

namespace App\Domain\Loan\Workflow\Stages;

use App\Domain\Loan\Contracts\ConditionalStageInterface;
use App\Domain\Loan\Data\ExecutionResult;
use App\Domain\Loan\Exceptions\InvalidWorkflowConfiguration;
use App\Models\Loan;

class ManagerApprovalStage implements ConditionalStageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function appliesTo(Loan $loan, array $rules): bool
    {
        return $loan->amount > $this->positiveIntegerRule($rules, 'activationThreshold');
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public function execute(Loan $loan, array $rules): ExecutionResult
    {
        $incomeMultiplier = $this->positiveIntegerRule($rules, 'incomeMultiplier');

        if ($loan->amount > $loan->monthly_income * $incomeMultiplier) {
            return ExecutionResult::fail('MANAGER_APPROVAL_DENIED');
        }

        return ExecutionResult::pass();
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function positiveIntegerRule(array $rules, string $key): int
    {
        $value = $rules[$key] ?? null;

        if (! is_int($value) || $value <= 0) {
            throw new InvalidWorkflowConfiguration("The {$key} rule must be a positive integer.");
        }

        return $value;
    }
}
