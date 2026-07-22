<?php

use App\Domain\Loan\Enums\StageResultType;
use App\Domain\Loan\Workflow\Stages\ManagerApprovalStage;
use App\Models\Loan;

it('only applies above the activation threshold', function (int $amount, bool $expected) {
    $stage = new ManagerApprovalStage;

    expect($stage->appliesTo(
        new Loan(['amount' => $amount]),
        ['activationThreshold' => 500_000_000],
    ))->toBe($expected);
})->with([
    'below' => [499_999_999, false],
    'at threshold' => [500_000_000, false],
    'above' => [500_000_001, true],
]);

it('compares the requested amount with configured income capacity', function (
    int $amount,
    int $monthlyIncome,
    StageResultType $expected,
) {
    $result = (new ManagerApprovalStage)->execute(
        new Loan(['amount' => $amount, 'monthly_income' => $monthlyIncome]),
        ['incomeMultiplier' => 20],
    );

    expect($result->type)->toBe($expected);
})->with([
    'denied' => [600_000_000, 20_000_000, StageResultType::Fail],
    'approved' => [600_000_000, 50_000_000, StageResultType::Pass],
]);
