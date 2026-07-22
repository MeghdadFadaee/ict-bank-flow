<?php

namespace App\Domain\Loan\Workflow\Stages;

use App\Domain\Loan\Contracts\StageInterface;
use App\Domain\Loan\Data\ExecutionResult;
use App\Domain\Loan\Exceptions\InvalidWorkflowConfiguration;
use App\Models\Loan;
use Illuminate\Support\Str;

class FraudCheckStage implements StageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function execute(Loan $loan, array $rules): ExecutionResult
    {
        $fraudPrefix = $this->stringRule($rules, 'fraudPrefix');
        $manualReviewPrefix = $this->stringRule($rules, 'manualReviewPrefix');

        if (Str::startsWith($loan->customer_id, $fraudPrefix)) {
            return ExecutionResult::fail('FRAUD_DETECTED');
        }

        if (Str::startsWith($loan->customer_id, $manualReviewPrefix)) {
            return ExecutionResult::manualReview('CUSTOMER_REQUIRES_REVIEW');
        }

        return ExecutionResult::pass();
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function stringRule(array $rules, string $key): string
    {
        $value = $rules[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidWorkflowConfiguration("The {$key} rule must be a non-empty string.");
        }

        return $value;
    }
}
