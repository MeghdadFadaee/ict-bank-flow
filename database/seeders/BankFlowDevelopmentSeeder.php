<?php

namespace Database\Seeders;

use App\Domain\Loan\Enums\LoanStage;
use App\Domain\Loan\Enums\LoanStatus;
use App\Domain\Loan\Enums\StageResultType;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\StageDefinition;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use App\Models\WorkflowConfigurationStep;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class BankFlowDevelopmentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::factory()->create([
            'name' => 'BankFlow Admin',
            'email' => 'admin@bankflow.test',
        ]);

        $personalLoanType = LoanType::factory()->create([
            'code' => 'PERSONAL',
            'name' => 'Personal Loan',
        ]);

        $businessLoanType = LoanType::factory()->create([
            'code' => 'BUSINESS',
            'name' => 'Business Loan',
        ]);

        /** @var Collection<int, StageDefinition> $stageDefinitions */
        $stageDefinitions = collect(LoanStage::cases())
            ->mapWithKeys(fn (LoanStage $stage): array => [
                $stage->value => StageDefinition::factory()->forStage($stage)->create(),
            ]);

        $personalWorkflow = WorkflowConfiguration::factory()
            ->for($personalLoanType, 'loanType')
            ->for($admin, 'creator')
            ->published()
            ->withStep($stageDefinitions[LoanStage::Validation->value], 1)
            ->withStep($stageDefinitions[LoanStage::FraudCheck->value], 2, [
                'fraudPrefix' => 'FRAUD',
                'manualReviewPrefix' => 'REVIEW',
            ])
            ->withStep($stageDefinitions[LoanStage::CreditCheck->value], 3, [
                'rejectBelow' => 500,
                'manualReviewMin' => 500,
                'manualReviewMax' => 649,
                'approveFrom' => 650,
            ])
            ->withStep($stageDefinitions[LoanStage::ManagerApproval->value], 4, [
                'activationThreshold' => 500_000_000,
                'incomeMultiplier' => 20,
            ])
            ->create([
                'name' => 'Personal Loan Workflow',
                'version' => 1,
            ]);

        $businessWorkflow = WorkflowConfiguration::factory()
            ->for($businessLoanType, 'loanType')
            ->for($admin, 'creator')
            ->published()
            ->withStep($stageDefinitions[LoanStage::Validation->value], 1)
            ->withStep($stageDefinitions[LoanStage::FraudCheck->value], 2, [
                'fraudPrefix' => 'FRAUD',
                'manualReviewPrefix' => 'REVIEW',
            ])
            ->withStep($stageDefinitions[LoanStage::GuarantorCheck->value], 3, [
                'guarantorRequired' => true,
            ])
            ->withStep($stageDefinitions[LoanStage::CreditCheck->value], 4, [
                'rejectBelow' => 500,
                'manualReviewMin' => 500,
                'manualReviewMax' => 649,
                'approveFrom' => 650,
            ])
            ->withStep($stageDefinitions[LoanStage::ManagerApproval->value], 5, [
                'activationThreshold' => 500_000_000,
                'incomeMultiplier' => 20,
            ])
            ->create([
                'name' => 'Business Loan Workflow',
                'version' => 1,
            ]);

        $personalSteps = $this->stepsByCode($personalWorkflow);
        $businessSteps = $this->stepsByCode($businessWorkflow);

        Loan::factory()
            ->count(8)
            ->atStep($personalSteps[LoanStage::Validation->value])
            ->create();

        Loan::factory()
            ->count(8)
            ->atStep($businessSteps[LoanStage::Validation->value])
            ->create();

        Loan::factory()
            ->forWorkflow($personalWorkflow)
            ->withHistory($personalSteps[LoanStage::Validation->value])
            ->withHistory($personalSteps[LoanStage::FraudCheck->value])
            ->withHistory($personalSteps[LoanStage::CreditCheck->value])
            ->create([
                'status' => LoanStatus::Approved,
                'current_workflow_configuration_step_id' => null,
                'amount' => 400_000_000,
                'credit_score' => 720,
            ]);

        Loan::factory()
            ->forWorkflow($personalWorkflow)
            ->withHistory($personalSteps[LoanStage::Validation->value])
            ->withHistory(
                $personalSteps[LoanStage::FraudCheck->value],
                StageResultType::ManualReview,
                'CUSTOMER_REQUIRES_REVIEW',
            )
            ->create([
                'customer_id' => 'REVIEW-CUSTOMER',
                'status' => LoanStatus::ManualReview,
                'current_workflow_configuration_step_id' => null,
            ]);

        Loan::factory()
            ->forWorkflow($businessWorkflow)
            ->withHistory($businessSteps[LoanStage::Validation->value])
            ->withHistory($businessSteps[LoanStage::FraudCheck->value])
            ->withHistory(
                $businessSteps[LoanStage::GuarantorCheck->value],
                StageResultType::Fail,
                'GUARANTOR_REQUIRED',
            )
            ->create([
                'has_guarantor' => false,
                'status' => LoanStatus::Rejected,
                'current_workflow_configuration_step_id' => null,
            ]);

        Loan::factory()
            ->forWorkflow($personalWorkflow)
            ->withHistory($personalSteps[LoanStage::Validation->value])
            ->withHistory($personalSteps[LoanStage::FraudCheck->value])
            ->withHistory($personalSteps[LoanStage::CreditCheck->value])
            ->withHistory($personalSteps[LoanStage::ManagerApproval->value])
            ->create([
                'amount' => 600_000_000,
                'monthly_income' => 50_000_000,
                'credit_score' => 750,
                'status' => LoanStatus::Approved,
                'current_workflow_configuration_step_id' => null,
            ]);
    }

    /**
     * @return Collection<string, WorkflowConfigurationStep>
     */
    private function stepsByCode(WorkflowConfiguration $workflowConfiguration): Collection
    {
        return $workflowConfiguration
            ->steps()
            ->with('stageDefinition')
            ->get()
            ->keyBy(fn (WorkflowConfigurationStep $step): string => $step->stageDefinition->code);
    }
}
