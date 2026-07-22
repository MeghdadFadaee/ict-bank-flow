<?php

use App\Domain\Loan\Enums\StageResultType;
use App\Domain\Loan\Workflow\Stages\GuarantorCheckStage;
use App\Models\Loan;

it('requires a guarantor', function (bool $hasGuarantor, StageResultType $expected) {
    $result = (new GuarantorCheckStage)->execute(
        new Loan(['has_guarantor' => $hasGuarantor]),
        [],
    );

    expect($result->type)->toBe($expected);
})->with([
    'missing' => [false, StageResultType::Fail],
    'present' => [true, StageResultType::Pass],
]);
