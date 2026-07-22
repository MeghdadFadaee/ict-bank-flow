<?php

use App\Domain\Loan\Enums\StageResultType;
use App\Domain\Loan\Workflow\Stages\ValidationStage;
use App\Models\Loan;
use App\Models\LoanType;

it('passes a valid application', function () {
    $loan = (new Loan([
        'customer_id' => 'C-1001',
        'amount' => 400_000_000,
        'phone' => '09121234567',
        'monthly_income' => 50_000_000,
        'credit_score' => 720,
        'has_guarantor' => false,
    ]))->setRelation('loanType', new LoanType(['code' => 'PERSONAL']));

    expect((new ValidationStage)->execute($loan, [])->type)->toBe(StageResultType::Pass);
});

it('returns the first validation failure', function (array $attributes, string $reason) {
    $loanType = new LoanType(['code' => $attributes['loan_type_code'] ?? 'PERSONAL']);
    unset($attributes['loan_type_code']);

    $loan = (new Loan(array_replace([
        'customer_id' => 'C-1001',
        'amount' => 400_000_000,
        'phone' => '09121234567',
        'monthly_income' => 50_000_000,
        'credit_score' => 720,
        'has_guarantor' => false,
    ], $attributes)))->setRelation('loanType', $loanType);

    $result = (new ValidationStage)->execute($loan, []);

    expect($result->type)->toBe(StageResultType::Fail)
        ->and($result->reason)->toBe($reason);
})->with([
    'customer ID' => [['customer_id' => ''], 'INVALID_CUSTOMER_ID'],
    'amount' => [['amount' => 0], 'INVALID_AMOUNT'],
    'phone' => [['phone' => '08121234567'], 'INVALID_PHONE'],
    'loan type' => [['loan_type_code' => 'OTHER'], 'INVALID_LOAN_TYPE'],
    'monthly income' => [['monthly_income' => -1], 'INVALID_MONTHLY_INCOME'],
    'credit score below range' => [['credit_score' => -1], 'INVALID_CREDIT_SCORE'],
    'credit score above range' => [['credit_score' => 1001], 'INVALID_CREDIT_SCORE'],
]);
