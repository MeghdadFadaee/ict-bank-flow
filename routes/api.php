<?php

use App\Http\Controllers\Api\V1\LoanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/{loanId}/process', [LoanController::class, 'process'])->name('loans.process');
    Route::get('/loans/{loanId}', [LoanController::class, 'show'])->name('loans.show');
    Route::get('/loans/{loanId}/history', [LoanController::class, 'history'])->name('loans.history');
});
