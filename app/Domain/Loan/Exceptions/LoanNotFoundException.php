<?php

namespace App\Domain\Loan\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LoanNotFoundException extends RuntimeException implements ShouldntReport
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['error' => 'LOAN_NOT_FOUND'], 404);
    }
}
