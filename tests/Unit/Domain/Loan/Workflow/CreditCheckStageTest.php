<?php

use App\Domain\Loan\Enums\StageResultType;
use App\Domain\Loan\Workflow\Stages\CreditCheckStage;
use App\Models\Loan;

it('handles credit score boundaries', function (int $creditScore, StageResultType $expected) {
    $result = (new CreditCheckStage)->execute(
        new Loan(['credit_score' => $creditScore]),
        [
            'rejectBelow' => 500,
            'manualReviewMin' => 500,
            'manualReviewMax' => 649,
            'approveFrom' => 650,
        ],
    );

    expect($result->type)->toBe($expected);
})->with([
    'below manual range' => [499, StageResultType::Fail],
    'manual lower boundary' => [500, StageResultType::ManualReview],
    'manual upper boundary' => [649, StageResultType::ManualReview],
    'approval boundary' => [650, StageResultType::Pass],
]);

it('uses overridden credit rules', function () {
    $result = (new CreditCheckStage)->execute(
        new Loan(['credit_score' => 699]),
        [
            'rejectBelow' => 650,
            'manualReviewMin' => 650,
            'manualReviewMax' => 699,
            'approveFrom' => 700,
        ],
    );

    expect($result->type)->toBe(StageResultType::ManualReview);
});
