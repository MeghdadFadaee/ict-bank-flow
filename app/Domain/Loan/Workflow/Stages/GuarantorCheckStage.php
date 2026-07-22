<?php

namespace App\Domain\Loan\Workflow\Stages;

use App\Domain\Loan\Contracts\StageInterface;
use App\Domain\Loan\Data\ExecutionResult;
use App\Models\Loan;

class GuarantorCheckStage implements StageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function execute(Loan $loan, array $rules): ExecutionResult
    {
        if (! $loan->has_guarantor) {
            return ExecutionResult::fail('GUARANTOR_REQUIRED');
        }

        return ExecutionResult::pass();
    }
}
