<?php

namespace App\Domain\Loan\Contracts;

use App\Domain\Loan\Data\ExecutionResult;
use App\Models\Loan;

interface StageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function execute(Loan $loan, array $rules): ExecutionResult;
}
