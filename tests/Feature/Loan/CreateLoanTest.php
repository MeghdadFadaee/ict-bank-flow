<?php

use App\Domain\Loan\Enums\LoanStatus;
use App\Models\Loan;
use Database\Seeders\BankFlowSeeder;

beforeEach(function () {
    $this->seed(BankFlowSeeder::class);
});

it('creates a submitted loan assigned to the published workflow', function () {
    $response = $this->postJson('/api/v1/loans', validLoanPayload());

    $response->assertCreated()->assertExactJson([
        'loanId' => $response->json('loanId'),
        'status' => 'SUBMITTED',
        'currentStage' => 'VALIDATION',
    ]);

    $loan = Loan::query()->where('public_id', $response->json('loanId'))->firstOrFail();

    expect($loan->status)->toBe(LoanStatus::Submitted)
        ->and($loan->workflow_configuration_id)->not->toBeNull()
        ->and($loan->current_workflow_configuration_step_id)->not->toBeNull();
});

it('defers semantic validation until processing', function (array $overrides, string $reason) {
    $createResponse = $this->postJson('/api/v1/loans', validLoanPayload($overrides));

    $createResponse->assertCreated();

    $this->postJson("/api/v1/loans/{$createResponse->json('loanId')}/process")
        ->assertSuccessful()
        ->assertJsonPath('status', 'REJECTED');

    $loan = Loan::query()->where('public_id', $createResponse->json('loanId'))->firstOrFail();

    expect($loan->histories()->value('reason'))->toBe($reason);
})->with([
    'customer ID' => [['customerId' => ''], 'INVALID_CUSTOMER_ID'],
    'amount' => [['amount' => 0], 'INVALID_AMOUNT'],
    'phone' => [['phone' => '08121234567'], 'INVALID_PHONE'],
    'monthly income' => [['monthlyIncome' => -1], 'INVALID_MONTHLY_INCOME'],
    'credit score' => [['creditScore' => 1001], 'INVALID_CREDIT_SCORE'],
]);

it('returns invalid request for malformed JSON', function () {
    $response = $this->call(
        'POST',
        '/api/v1/loans',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: '{"customerId":',
    );

    $response->assertBadRequest()->assertExactJson(['error' => 'INVALID_REQUEST']);
});

it('returns invalid request for an unavailable loan type', function () {
    $this->postJson('/api/v1/loans', validLoanPayload(['loanType' => 'UNKNOWN']))
        ->assertBadRequest()
        ->assertExactJson(['error' => 'INVALID_REQUEST']);
});
