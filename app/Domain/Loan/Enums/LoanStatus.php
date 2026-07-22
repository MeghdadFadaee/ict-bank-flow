<?php

namespace App\Domain\Loan\Enums;

enum LoanStatus: string
{
    case Submitted = 'SUBMITTED';
    case InProgress = 'IN_PROGRESS';
    case ManualReview = 'MANUAL_REVIEW';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::ManualReview, self::Approved, self::Rejected => true,
            self::Submitted, self::InProgress => false,
        };
    }
}
