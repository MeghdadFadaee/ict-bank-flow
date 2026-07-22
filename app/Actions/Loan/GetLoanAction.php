<?php

namespace App\Actions\Loan;

use App\Domain\Loan\Exceptions\LoanNotFoundException;
use App\Models\Loan;

class GetLoanAction
{
    public function handle(string $loanId): Loan
    {
        $loan = Loan::query()
            ->with(['loanType', 'currentStep.stageDefinition'])
            ->where('public_id', $loanId)
            ->first();

        if ($loan === null) {
            throw new LoanNotFoundException("Loan {$loanId} was not found.");
        }

        return $loan;
    }
}
