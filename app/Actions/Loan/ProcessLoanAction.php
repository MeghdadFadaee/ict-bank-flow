<?php

namespace App\Actions\Loan;

use App\Domain\Loan\Exceptions\LoanNotFoundException;
use App\Domain\Loan\Workflow\WorkflowEngine;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class ProcessLoanAction
{
    public function __construct(private WorkflowEngine $workflowEngine) {}

    public function handle(string $loanId): Loan
    {
        return DB::transaction(function () use ($loanId): Loan {
            $loan = Loan::query()
                ->where('public_id', $loanId)
                ->lockForUpdate()
                ->first();

            if ($loan === null) {
                throw new LoanNotFoundException("Loan {$loanId} was not found.");
            }

            $loan->load([
                'loanType',
                'workflowConfiguration.steps.stageDefinition',
                'histories',
                'currentStep.stageDefinition',
            ]);

            $processedLoan = $this->workflowEngine->process($loan);

            return $processedLoan->load(['loanType', 'currentStep.stageDefinition']);
        }, 3);
    }
}
