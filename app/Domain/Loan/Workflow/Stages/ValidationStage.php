<?php

namespace App\Domain\Loan\Workflow\Stages;

use App\Domain\Loan\Contracts\StageInterface;
use App\Domain\Loan\Data\ExecutionResult;
use App\Models\Loan;

class ValidationStage implements StageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function execute(Loan $loan, array $rules): ExecutionResult
    {
        if (trim($loan->customer_id) === '') {
            return ExecutionResult::fail('INVALID_CUSTOMER_ID');
        }

        if ($loan->amount <= 0) {
            return ExecutionResult::fail('INVALID_AMOUNT');
        }

        if (preg_match('/^09\d{9}$/', $loan->phone) !== 1) {
            return ExecutionResult::fail('INVALID_PHONE');
        }

        if (! in_array($loan->loanType->code, ['PERSONAL', 'BUSINESS'], true)) {
            return ExecutionResult::fail('INVALID_LOAN_TYPE');
        }

        if ($loan->monthly_income < 0) {
            return ExecutionResult::fail('INVALID_MONTHLY_INCOME');
        }

        if ($loan->credit_score < 0 || $loan->credit_score > 1000) {
            return ExecutionResult::fail('INVALID_CREDIT_SCORE');
        }

        return ExecutionResult::pass();
    }
}
