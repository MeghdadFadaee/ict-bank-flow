<?php

namespace App\Actions\WorkflowConfiguration;

use App\Domain\Loan\Enums\LoanStage;
use App\Models\StageDefinition;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidateWorkflowStepDataAction
{
    /**
     * @param  array<int, array{stage_definition_id: int, rules?: array<string, mixed>, is_enabled?: bool}>  $steps
     * @return array<int, array{stage_definition_id: int, rules: array<string, mixed>, is_enabled: bool}>
     */
    public function handle(array $steps): array
    {
        $validatedSteps = Validator::make(['steps' => $steps], [
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.stage_definition_id' => ['required', 'integer', 'distinct', 'exists:stage_definitions,id'],
            'steps.*.rules' => ['present', 'array'],
            'steps.*.is_enabled' => ['required', 'boolean'],
        ])->validate()['steps'];

        $stageDefinitions = StageDefinition::query()
            ->whereKey(collect($validatedSteps)->pluck('stage_definition_id'))
            ->get()
            ->keyBy('id');

        $registeredStageCodes = collect(LoanStage::cases())->pluck('value')->all();
        $errors = [];

        foreach ($validatedSteps as $index => $step) {
            $stageDefinition = $stageDefinitions[$step['stage_definition_id']];

            if (! $stageDefinition->is_active) {
                $errors["steps.{$index}.stage_definition_id"] = "{$stageDefinition->code} is inactive.";
            }

            if (! in_array($stageDefinition->code, $registeredStageCodes, true)) {
                $errors["steps.{$index}.stage_definition_id"] = "{$stageDefinition->code} is not registered by the application.";
            }

            $rulesValidator = Validator::make($step['rules'], $this->rulesFor($stageDefinition->code));

            foreach ($rulesValidator->errors()->toArray() as $field => $messages) {
                $errors["steps.{$index}.rules.{$field}"] = Arr::first($messages);
            }

            $this->validateCreditRanges($stageDefinition->code, $step['rules'], $index, $errors);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return array_map(fn (array $step): array => [
            'stage_definition_id' => $step['stage_definition_id'],
            'rules' => $step['rules'],
            'is_enabled' => $step['is_enabled'],
        ], $validatedSteps);
    }

    /**
     * @return array<string, list<string>>
     */
    private function rulesFor(string $stageCode): array
    {
        return match ($stageCode) {
            LoanStage::FraudCheck->value => [
                'fraudPrefix' => ['required', 'string', 'max:50'],
                'manualReviewPrefix' => ['required', 'string', 'max:50', 'different:fraudPrefix'],
            ],
            LoanStage::GuarantorCheck->value => [
                'guarantorRequired' => ['required', 'boolean'],
            ],
            LoanStage::CreditCheck->value => [
                'rejectBelow' => ['required', 'integer', 'between:0,1000'],
                'manualReviewMin' => ['required', 'integer', 'between:0,1000'],
                'manualReviewMax' => ['required', 'integer', 'between:0,1000'],
                'approveFrom' => ['required', 'integer', 'between:0,1000'],
            ],
            LoanStage::ManagerApproval->value => [
                'activationThreshold' => ['required', 'integer', 'min:0'],
                'incomeMultiplier' => ['required', 'integer', 'min:1'],
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string|null>  $errors
     */
    private function validateCreditRanges(string $stageCode, array $rules, int $index, array &$errors): void
    {
        if ($stageCode !== LoanStage::CreditCheck->value
            || ! isset($rules['rejectBelow'], $rules['manualReviewMin'], $rules['manualReviewMax'], $rules['approveFrom'])) {
            return;
        }

        if ($rules['rejectBelow'] !== $rules['manualReviewMin']) {
            $errors["steps.{$index}.rules.rejectBelow"] = 'The rejection boundary must meet the manual-review minimum without a gap.';
        }

        if ($rules['manualReviewMin'] > $rules['manualReviewMax']) {
            $errors["steps.{$index}.rules.manualReviewMax"] = 'The manual-review maximum must be at least its minimum.';
        }

        if ($rules['approveFrom'] !== $rules['manualReviewMax'] + 1) {
            $errors["steps.{$index}.rules.approveFrom"] = 'The approval boundary must immediately follow the manual-review range.';
        }
    }
}
