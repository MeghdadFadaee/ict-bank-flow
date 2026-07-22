<?php

use App\Domain\Loan\Enums\LoanStage;
use App\Models\Loan;
use Database\Seeders\BankFlowSeeder;

beforeEach(function () {
    $this->seed(BankFlowSeeder::class);
});

it('approves a valid personal loan and records applicable stages', function () {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload())->json('loanId');

    $this->postJson("/api/v1/loans/{$loanId}/process")
        ->assertSuccessful()
        ->assertExactJson([
            'loanId' => $loanId,
            'status' => 'APPROVED',
            'currentStage' => null,
        ]);

    $loan = Loan::query()->where('public_id', $loanId)->firstOrFail();

    expect($loan->histories()->orderBy('id')->pluck('stage_code')->all())->toBe([
        LoanStage::Validation,
        LoanStage::FraudCheck,
        LoanStage::CreditCheck,
    ]);
});

it('rejects a business loan without a guarantor', function () {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload([
        'loanType' => 'BUSINESS',
        'hasGuarantor' => false,
    ]))->json('loanId');

    $this->postJson("/api/v1/loans/{$loanId}/process")
        ->assertSuccessful()
        ->assertJsonPath('status', 'REJECTED');

    $loan = Loan::query()->where('public_id', $loanId)->firstOrFail();

    expect($loan->histories()->latest('id')->value('reason'))->toBe('GUARANTOR_REQUIRED');
});

it('approves a valid business loan with a guarantor', function () {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload([
        'loanType' => 'BUSINESS',
        'hasGuarantor' => true,
    ]))->json('loanId');

    $this->postJson("/api/v1/loans/{$loanId}/process")
        ->assertSuccessful()
        ->assertJsonPath('status', 'APPROVED');

    $loan = Loan::query()->where('public_id', $loanId)->firstOrFail();

    expect($loan->histories()->orderBy('id')->pluck('stage_code')->all())->toBe([
        LoanStage::Validation,
        LoanStage::FraudCheck,
        LoanStage::GuarantorCheck,
        LoanStage::CreditCheck,
    ]);
});

it('stops for fraud and credit manual review decisions', function (array $overrides, LoanStage $stage) {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload($overrides))->json('loanId');

    $this->postJson("/api/v1/loans/{$loanId}/process")
        ->assertSuccessful()
        ->assertJsonPath('status', 'MANUAL_REVIEW')
        ->assertJsonPath('currentStage', null);

    $loan = Loan::query()->where('public_id', $loanId)->firstOrFail();

    expect($loan->histories()->latest('id')->value('stage_code'))->toBe($stage);
})->with([
    'fraud review' => [['customerId' => 'REVIEW-1001'], LoanStage::FraudCheck],
    'credit review' => [['creditScore' => 600], LoanStage::CreditCheck],
]);

it('executes manager approval only above its threshold', function (
    int $amount,
    int $monthlyIncome,
    string $expectedStatus,
    int $expectedHistoryCount,
) {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload([
        'amount' => $amount,
        'monthlyIncome' => $monthlyIncome,
    ]))->json('loanId');

    $this->postJson("/api/v1/loans/{$loanId}/process")
        ->assertSuccessful()
        ->assertJsonPath('status', $expectedStatus);

    $loan = Loan::query()->where('public_id', $loanId)->firstOrFail();

    expect($loan->histories()->count())->toBe($expectedHistoryCount);
})->with([
    'skipped at threshold' => [500_000_000, 20_000_000, 'APPROVED', 3],
    'manager rejection' => [600_000_000, 20_000_000, 'REJECTED', 4],
    'manager approval' => [600_000_000, 50_000_000, 'APPROVED', 4],
]);

it('is idempotent after reaching a terminal status', function (array $overrides, string $status) {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload($overrides))->json('loanId');

    $this->postJson("/api/v1/loans/{$loanId}/process")->assertSuccessful();

    $loan = Loan::query()->where('public_id', $loanId)->firstOrFail();
    $historyCount = $loan->histories()->count();

    $this->postJson("/api/v1/loans/{$loanId}/process")
        ->assertSuccessful()
        ->assertJsonPath('status', $status);

    expect($loan->histories()->count())->toBe($historyCount);
})->with([
    'approved' => [[], 'APPROVED'],
    'rejected' => [['customerId' => 'FRAUD-1001'], 'REJECTED'],
    'manual review' => [['customerId' => 'REVIEW-1001'], 'MANUAL_REVIEW'],
]);

it('returns the documented error for an unknown loan', function () {
    $this->postJson('/api/v1/loans/L-UNKNOWN/process')
        ->assertNotFound()
        ->assertExactJson(['error' => 'LOAN_NOT_FOUND']);
});
