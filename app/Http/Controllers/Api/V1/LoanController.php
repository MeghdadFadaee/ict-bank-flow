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
    public function store(StoreLoanRequest $request, CreateLoanAction $createLoan): JsonResponse
    {
        $loan = $createLoan->handle($request->validated());

        return (new LoanProcessResource($loan))->response()->setStatusCode(201);
    }

    public function process(string $loanId, ProcessLoanAction $processLoan): JsonResponse
    {
        return (new LoanProcessResource($processLoan->handle($loanId)))->response();
    }

    public function show(string $loanId, GetLoanAction $getLoan): JsonResponse
    {
        return (new LoanResource($getLoan->handle($loanId)))->response();
    }

    public function history(string $loanId, GetLoanHistoryAction $getLoanHistory): AnonymousResourceCollection
    {
        return LoanHistoryResource::collection($getLoanHistory->handle($loanId));
    }
}
