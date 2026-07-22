<?php

namespace App\Domain\Loan\Enums;

enum StageResultType: string
{
    case Pass = 'PASS';
    case Fail = 'FAIL';
    case ManualReview = 'MANUAL_REVIEW';
}
