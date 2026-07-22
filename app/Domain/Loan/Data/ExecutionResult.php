<?php

namespace App\Domain\Loan\Data;

use App\Domain\Loan\Enums\StageResultType;

final readonly class ExecutionResult
{
    public function __construct(
        public StageResultType $type,
        public string $reason,
    ) {}

    public static function pass(): self
    {
        return new self(StageResultType::Pass, 'SUCCESS');
    }

    public static function fail(string $reason): self
    {
        return new self(StageResultType::Fail, $reason);
    }

    public static function manualReview(string $reason): self
    {
        return new self(StageResultType::ManualReview, $reason);
    }
}
