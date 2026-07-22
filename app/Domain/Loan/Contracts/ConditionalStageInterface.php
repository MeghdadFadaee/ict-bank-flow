<?php

namespace App\Domain\Loan\Contracts;

use App\Models\Loan;

interface ConditionalStageInterface extends StageInterface
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function appliesTo(Loan $loan, array $rules): bool;
}
