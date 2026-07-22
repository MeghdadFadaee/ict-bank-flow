<?php

namespace App\Actions\Loan;

use App\Domain\Loan\Enums\LoanStatus;
use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Domain\Loan\Exceptions\InvalidRequestException;
use App\Domain\Loan\Exceptions\InvalidWorkflowConfiguration;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\WorkflowConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateLoanAction
{
    /**
     * @param  array{
     *     customerId: string,
     *     amount: int,
     *     phone: string,
     *     loanType: string,
     *     monthlyIncome: int,
     *     creditScore: int,
     *     hasGuarantor: bool
     * }  $data
     */
    public function handle(array $data): Loan
    {
        return DB::transaction(function () use ($data): Loan {
            $loanType = LoanType::query()
                ->where('code', $data['loanType'])
                ->where('is_active', true)
                ->first();

            if ($loanType === null) {
                throw new InvalidRequestException('The requested Loan type is unavailable.');
            }

            $workflowConfiguration = $this->publishedWorkflowFor($loanType);
            $firstStep = $workflowConfiguration->steps->firstWhere('is_enabled', true);

            if ($firstStep === null) {
                throw new InvalidWorkflowConfiguration('The published Workflow has no enabled steps.');
            }

            $loan = Loan::query()->create([
                'public_id' => 'L-'.Str::ulid(),
                'customer_id' => $data['customerId'],
                'loan_type_id' => $loanType->getKey(),
                'workflow_configuration_id' => $workflowConfiguration->getKey(),
                'current_workflow_configuration_step_id' => $firstStep->getKey(),
                'amount' => $data['amount'],
                'phone' => $data['phone'],
                'monthly_income' => $data['monthlyIncome'],
                'credit_score' => $data['creditScore'],
                'has_guarantor' => $data['hasGuarantor'],
                'status' => LoanStatus::Submitted,
            ]);

            return $loan->setRelations([
                'loanType' => $loanType,
                'workflowConfiguration' => $workflowConfiguration,
                'currentStep' => $firstStep,
            ]);
        });
    }

    private function publishedWorkflowFor(LoanType $loanType): WorkflowConfiguration
    {
        $workflowConfiguration = $loanType->workflowConfigurations()
            ->where('status', WorkflowConfigurationStatus::Published)
            ->with('steps.stageDefinition')
            ->first();

        if ($workflowConfiguration === null) {
            throw new InvalidWorkflowConfiguration("Loan type {$loanType->code} has no published Workflow.");
        }

        return $workflowConfiguration;
    }
}
