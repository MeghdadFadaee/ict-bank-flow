<?php

use Database\Seeders\BankFlowSeeder;

beforeEach(function () {
    $this->seed(BankFlowSeeder::class);
});

it('returns unwrapped history in deterministic chronological order', function () {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload())->json('loanId');
    $this->postJson("/api/v1/loans/{$loanId}/process")->assertSuccessful();

    $this->getJson("/api/v1/loans/{$loanId}/history")
        ->assertSuccessful()
        ->assertJsonCount(3)
        ->assertJsonPath('0.stage', 'VALIDATION')
        ->assertJsonPath('1.stage', 'FRAUD_CHECK')
        ->assertJsonPath('2.stage', 'CREDIT_CHECK')
        ->assertJsonStructure([
            '*' => ['stage', 'result', 'timestamp', 'reason'],
        ]);
});

it('returns the documented error for an unknown loan', function () {
    $this->getJson('/api/v1/loans/L-UNKNOWN/history')
        ->assertNotFound()
        ->assertExactJson(['error' => 'LOAN_NOT_FOUND']);
});
