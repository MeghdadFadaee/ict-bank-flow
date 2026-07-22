<?php

use Database\Seeders\BankFlowSeeder;

beforeEach(function () {
    $this->seed(BankFlowSeeder::class);
});

it('returns the complete loan representation', function () {
    $loanId = $this->postJson('/api/v1/loans', validLoanPayload())->json('loanId');

    $this->getJson("/api/v1/loans/{$loanId}")
        ->assertSuccessful()
        ->assertJson([
            'loanId' => $loanId,
            'customerId' => 'C-1001',
            'amount' => 400_000_000,
            'phone' => '09121234567',
            'loanType' => 'PERSONAL',
            'monthlyIncome' => 50_000_000,
            'creditScore' => 720,
            'hasGuarantor' => false,
            'status' => 'SUBMITTED',
            'currentStage' => 'VALIDATION',
        ])
        ->assertJsonStructure(['createdAt', 'updatedAt']);
});

it('returns the documented error for an unknown loan', function () {
    $this->getJson('/api/v1/loans/L-UNKNOWN')
        ->assertNotFound()
        ->assertExactJson(['error' => 'LOAN_NOT_FOUND']);
});
