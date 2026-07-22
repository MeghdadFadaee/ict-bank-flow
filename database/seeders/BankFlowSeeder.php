<?php

namespace Database\Seeders;

use App\Domain\Loan\Enums\LoanStage;
use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Models\LoanType;
use App\Models\StageDefinition;
use App\Models\WorkflowConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankFlowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $stageDefinitions = collect(LoanStage::cases())->mapWithKeys(
                function (LoanStage $stage): array {
                    $stageDefinition = StageDefinition::query()->firstOrCreate(
                        ['code' => $stage->value],
                        ['name' => str($stage->name)->headline()->toString(), 'is_active' => true],
                    );

                    return [$stage->value => $stageDefinition];
                },
            );

            $personalLoanType = LoanType::query()->firstOrCreate(
                ['code' => 'PERSONAL'],
                ['name' => 'Personal Loan', 'is_active' => true],
            );

            $businessLoanType = LoanType::query()->firstOrCreate(
                ['code' => 'BUSINESS'],
                ['name' => 'Business Loan', 'is_active' => true],
            );

            $this->createInitialWorkflow(
                $personalLoanType,
                'Personal Loan Workflow',
                [
                    [LoanStage::Validation, []],
                    [LoanStage::FraudCheck, $this->fraudRules()],
                    [LoanStage::CreditCheck, $this->creditRules()],
                    [LoanStage::ManagerApproval, $this->managerRules()],
                ],
                $stageDefinitions->all(),
            );

            $this->createInitialWorkflow(
                $businessLoanType,
                'Business Loan Workflow',
                [
                    [LoanStage::Validation, []],
                    [LoanStage::FraudCheck, $this->fraudRules()],
                    [LoanStage::GuarantorCheck, []],
                    [LoanStage::CreditCheck, $this->creditRules()],
                    [LoanStage::ManagerApproval, $this->managerRules()],
                ],
                $stageDefinitions->all(),
            );
        });
    }

    /**
     * @param  array<int, array{LoanStage, array<string, mixed>}>  $steps
     * @param  array<string, StageDefinition>  $stageDefinitions
     */
    private function createInitialWorkflow(
        LoanType $loanType,
        string $name,
        array $steps,
        array $stageDefinitions,
    ): void {
        $publishedWorkflowExists = $loanType->workflowConfigurations()
            ->where('status', WorkflowConfigurationStatus::Published)
            ->exists();

        if ($publishedWorkflowExists) {
            return;
        }

        $workflowConfiguration = WorkflowConfiguration::query()->create([
            'loan_type_id' => $loanType->getKey(),
            'name' => $name,
            'version' => 1,
            'status' => WorkflowConfigurationStatus::Published,
            'published_at' => now('UTC'),
        ]);

        foreach ($steps as $index => [$stage, $rules]) {
            $workflowConfiguration->steps()->create([
                'stage_definition_id' => $stageDefinitions[$stage->value]->getKey(),
                'position' => $index + 1,
                'rules' => $rules,
                'is_enabled' => true,
            ]);
        }
    }

    /**
     * @return array{fraudPrefix: string, manualReviewPrefix: string}
     */
    private function fraudRules(): array
    {
        return [
            'fraudPrefix' => 'FRAUD',
            'manualReviewPrefix' => 'REVIEW',
        ];
    }

    /**
     * @return array{rejectBelow: int, manualReviewMin: int, manualReviewMax: int, approveFrom: int}
     */
    private function creditRules(): array
    {
        return [
            'rejectBelow' => 500,
            'manualReviewMin' => 500,
            'manualReviewMax' => 649,
            'approveFrom' => 650,
        ];
    }

    /**
     * @return array{activationThreshold: int, incomeMultiplier: int}
     */
    private function managerRules(): array
    {
        return [
            'activationThreshold' => 500_000_000,
            'incomeMultiplier' => 20,
        ];
    }
}
