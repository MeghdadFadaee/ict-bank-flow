<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Loan\CreateLoanAction;
use App\Actions\Loan\GetLoanAction;
use App\Actions\Loan\GetLoanHistoryAction;
use App\Actions\Loan\ProcessLoanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Resources\LoanHistoryResource;
use App\Http\Resources\LoanProcessResource;
use App\Http\Resources\LoanResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanController extends Controller
{
    public function store(StoreLoanRequest $request, CreateLoanAction $createLoan)
    {
        $loan = $createLoan->handle($request->validated());

        return LoanProcessResource::make($loan);
    }

    public function process(string $loanId, ProcessLoanAction $processLoan)
    {
        return LoanProcessResource::make($processLoan->handle($loanId));
    }

    public function show(string $loanId, GetLoanAction $getLoan)
    {
        return LoanResource::make($getLoan->handle($loanId));
    }

    public function history(string $loanId, GetLoanHistoryAction $getLoanHistory)
    {
        return LoanHistoryResource::collection($getLoanHistory->handle($loanId));
    }
}
