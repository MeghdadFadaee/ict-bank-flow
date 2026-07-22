<?php

use App\Domain\Loan\Enums\StageResultType;
use App\Domain\Loan\Workflow\Stages\FraudCheckStage;
use App\Models\Loan;

it('maps customer prefixes to fraud outcomes', function (string $customerId, StageResultType $expected) {
    $result = (new FraudCheckStage)->execute(
        new Loan(['customer_id' => $customerId]),
        ['fraudPrefix' => 'FRAUD', 'manualReviewPrefix' => 'REVIEW'],
    );

    expect($result->type)->toBe($expected);
})->with([
    'fraud' => ['FRAUD-1001', StageResultType::Fail],
    'manual review' => ['REVIEW-1001', StageResultType::ManualReview],
    'pass' => ['C-1001', StageResultType::Pass],
]);
