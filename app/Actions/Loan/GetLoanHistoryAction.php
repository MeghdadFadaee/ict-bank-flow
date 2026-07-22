<?php

namespace App\Actions\Loan;

use App\Domain\Loan\Exceptions\LoanNotFoundException;
use App\Models\Loan;
use App\Models\LoanHistory;
use Illuminate\Database\Eloquent\Collection;

class GetLoanHistoryAction
{
    /**
     * @return Collection<int, LoanHistory>
     */
    public function handle(string $loanId): Collection
    {
        $loan = Loan::query()
            ->select(['id', 'public_id'])
            ->where('public_id', $loanId)
            ->first();

        if ($loan === null) {
            throw new LoanNotFoundException("Loan {$loanId} was not found.");
        }

        return $loan->histories()
            ->orderBy('executed_at')
            ->orderBy('id')
            ->get();
    }
}
