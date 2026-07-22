<?php

namespace App\Domain\Loan\Workflow\Stages;

use App\Domain\Loan\Contracts\StageInterface;
use App\Domain\Loan\Data\ExecutionResult;
use App\Domain\Loan\Exceptions\InvalidWorkflowConfiguration;
use App\Models\Loan;

class CreditCheckStage implements StageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function execute(Loan $loan, array $rules): ExecutionResult
    {
        $rejectBelow = $this->integerRule($rules, 'rejectBelow');
        $manualReviewMin = $this->integerRule($rules, 'manualReviewMin');
        $manualReviewMax = $this->integerRule($rules, 'manualReviewMax');
        $approveFrom = $this->integerRule($rules, 'approveFrom');

        if ($rejectBelow !== $manualReviewMin || $manualReviewMax + 1 !== $approveFrom) {
            throw new InvalidWorkflowConfiguration('Credit score ranges must be contiguous.');
        }

        if ($loan->credit_score < $rejectBelow) {
            return ExecutionResult::fail('CREDIT_SCORE_TOO_LOW');
        }

        if ($loan->credit_score >= $manualReviewMin && $loan->credit_score <= $manualReviewMax) {
            return ExecutionResult::manualReview('CREDIT_SCORE_REQUIRES_REVIEW');
        }

        if ($loan->credit_score >= $approveFrom) {
            return ExecutionResult::pass();
        }

        throw new InvalidWorkflowConfiguration('Credit score ranges contain a gap.');
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function integerRule(array $rules, string $key): int
    {
        $value = $rules[$key] ?? null;

        if (! is_int($value)) {
            throw new InvalidWorkflowConfiguration("The {$key} rule must be an integer.");
        }

        return $value;
    }
}
